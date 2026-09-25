<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_role(['admin','lab_tech']);

// Ensure walk-in registrations work even if the migration has not yet been run.
ensure_walkin_column($conn);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$walkin_message = '';
$walkin_error = '';

/* --- WALK-IN LABORATORY REGISTRATION ---
 * A walk-in is represented as a patient record flagged is_walkin=1 so the
 * existing laboratory worklist/results workflow can be reused. No consultation
 * charge is created for this patient.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_walkin_lab'])) {
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $walkin_error = 'Security token mismatch. Please refresh the page and try again.';
    } else {
        $name = trim((string)($_POST['walkin_name'] ?? ''));
        $phone = trim((string)($_POST['walkin_phone'] ?? ''));
        $service_id = (int)($_POST['walkin_service_id'] ?? 0);
        // Laboratory staff create charges only. All patient payments are collected by Central Cashier.

        if ($name === '') $name = 'Walk-in Lab ' . date('Hi');
        if ($service_id <= 0) $walkin_error = 'Please select a laboratory test.';

        if ($walkin_error === '') {
            $serviceStmt = $conn->prepare("SELECT id, service_name, price FROM services_master WHERE id = ? AND active = 1 AND category = 'lab' LIMIT 1");
            if (!$serviceStmt) {
                $walkin_error = 'Unable to load laboratory service: ' . $conn->error;
            } else {
                $serviceStmt->bind_param('i', $service_id);
                $serviceStmt->execute();
                $labService = $serviceStmt->get_result()->fetch_assoc();
                $serviceStmt->close();

                if (!$labService) {
                    $walkin_error = 'Selected laboratory service is invalid.';
                } else {
                    $price = (float)$labService['price'];
                    $conn->begin_transaction();
                    try {
                        // Create a separate walk-in patient for each laboratory visit so the
                        // patient's name, phone, test, invoice and payment remain traceable.
                        $walkinPatientNumber = 'W-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
                        $walkinName = $name !== '' ? $name : 'Walk-in Patient';
                        $walkinGender = '';
                        $walkinFlag = 1;
                        $walkinPatientStmt = $conn->prepare(
                            'INSERT INTO patients (patient_number, full_name, gender, phone, is_walkin, created_at)
                             VALUES (?, ?, ?, ?, ?, NOW())'
                        );
                        if (!$walkinPatientStmt) {
                            throw new Exception('Unable to create walk-in patient: ' . $conn->error);
                        }
                        $walkinPatientStmt->bind_param('ssssi', $walkinPatientNumber, $walkinName, $walkinGender, $phone, $walkinFlag);
                        if (!$walkinPatientStmt->execute()) {
                            throw new Exception('Unable to create walk-in patient: ' . $walkinPatientStmt->error);
                        }
                        $walkinPatientId = (int)$walkinPatientStmt->insert_id;
                        $walkinPatientStmt->close();

                        // Put the walk-in laboratory request under the same Visit/Encounter.
                        $visitId = get_or_create_current_visit($conn, $walkinPatientId, 'Walk-in', 'Laboratory');
                        if ($visitId > 0) {
                            $serviceInsert = $conn->prepare("INSERT INTO patient_services (patient_id, service_id, category, price, visit_id, created_at, status) VALUES (?, ?, 'lab', ?, ?, NOW(), 'Pending')");
                            if (!$serviceInsert) throw new Exception('Unable to prepare walk-in laboratory request: ' . $conn->error);
                            $serviceInsert->bind_param('iidi', $walkinPatientId, $service_id, $price, $visitId);
                        } else {
                            $serviceInsert = $conn->prepare("INSERT INTO patient_services (patient_id, service_id, category, price, created_at, status) VALUES (?, ?, 'lab', ?, NOW(), 'Pending')");
                            if (!$serviceInsert) throw new Exception('Unable to prepare walk-in laboratory request: ' . $conn->error);
                            $serviceInsert->bind_param('iid', $walkinPatientId, $service_id, $price);
                        }
                        if (!$serviceInsert->execute()) throw new Exception('Unable to create walk-in laboratory request: ' . $serviceInsert->error);
                        $serviceInsert->close();

                        $invoiceId = get_or_create_visit_invoice($conn, $walkinPatientId, $visitId);
                        $invoiceItemId = add_invoice_item($conn, $invoiceId, 'Lab: ' . $labService['service_name'], 1, $price, 'lab', $service_id);
                        post_invoice_journal($conn, $invoiceId, $walkinPatientId, $price, 'Walk-in laboratory', $invoiceItemId);

                        // No payment is recorded here. Central Cashier is the single collection point.
                        $conn->commit();
                        $walkin_message = 'Walk-in laboratory request created successfully. Invoice #' . $invoiceId . '.';
                    } catch (Throwable $e) {
                        $conn->rollback();
                        $walkin_error = $e->getMessage();
                    }
                }
            }
        }
    }
}

// --- 1. HANDLE DATE RANGE (Defaults to today) ---
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// --- 2. HANDLE DELETE ACTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); exit('Invalid security token.'); }
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM patient_services WHERE id = ? AND category = 'lab'");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header('Location: lab_requests.php?start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date) . '&deleted=1');
    exit;
}

// --- 3. HANDLE LAB RESULT SUBMISSION ---
if (isset($_POST['save_lab_result'])) {
    $record_id = intval($_POST['record_id']);
    $findings = $_POST['findings'] ?? '';
    $status = 'Completed'; 

    $stmt = $conn->prepare("UPDATE patient_services SET results = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $findings, $status, $record_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Results saved successfully!'); window.location.href='lab_requests.php?start_date=$start_date&end_date=$end_date';</script>";
        exit;
    }
    $stmt->close();
}

// --- 4. FETCH LAB WORKLIST ---
$query = "SELECT ps.*, p.full_name, p.patient_number, sm.service_name, sm.price 
          FROM patient_services ps 
          JOIN patients p ON ps.patient_id = p.id 
          JOIN services_master sm ON ps.service_id = sm.id 
          WHERE ps.category = 'lab' 
          AND DATE(ps.created_at) BETWEEN ? AND ?
          ORDER BY (ps.status = 'Pending') DESC, ps.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$lab_jobs = $stmt->get_result();

$walkin_lab_services = $conn->query("SELECT id, service_name, price FROM services_master WHERE active = 1 AND category = 'lab' ORDER BY service_name ASC");

$total_revenue = 0;
$total_count = 0;
$rows = [];
while($row = $lab_jobs->fetch_assoc()) {
    $total_revenue += floatval($row['price']);
    $total_count++;
    $rows[] = $row;
}
?>

<style>
    /* Keeping your original styling */
    body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; margin: 0; }
    .container { padding: 30px; max-width: 1400px; margin: auto; }
    .filter-bar { background: white; padding: 15px 25px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 15px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 15px; }
    .filter-form { display: flex; align-items: center; gap: 10px; }
    .input-date { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; }
    
    /* New Search Input Styling */
    .search-input { 
        padding: 8px 15px; 
        border: 2px solid #3498db; 
        border-radius: 20px; 
        width: 300px; 
        outline: none; 
        transition: 0.3s;
    }
    .search-input:focus { box-shadow: 0 0 8px rgba(52, 152, 219, 0.3); }

    .btn-filter { background: #34495e; color: white; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-weight: 600; }
    .stats-flex { display: flex; gap: 20px; margin-bottom: 25px; }
    .stat-card { background: white; padding: 20px; border-radius: 12px; flex: 1; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid #2ecc71; }
    .stat-label { font-size: 12px; color: #7f8c8d; text-transform: uppercase; letter-spacing: 1px; }
    .stat-value { font-size: 24px; font-weight: 700; color: #2c3e50; }

    .worklist-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
    .header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0f2f5; padding-bottom: 15px; margin-bottom: 20px; }
    .page-title { color: #2c3e50; margin: 0; display: flex; align-items: center; gap: 10px; font-size: 24px; }
    
    .lab-table { width: 100%; border-collapse: collapse; }
    .lab-table th { background: #f8f9fa; color: #636e72; padding: 15px; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #eee; }
    .lab-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    
    .patient-info { font-weight: 700; color: #007bff; display: block; }
    .patient-no { font-size: 11px; background: #eee; padding: 2px 6px; border-radius: 3px; color: #666; }
    .test-name { font-weight: 600; color: #2d3436; }
    .test-cost { color: #27ae60; font-weight: 700; font-family: monospace; font-size: 15px; }
    
    textarea { width: 100%; padding: 10px; border: 1px solid #dcdde1; border-radius: 8px; font-family: inherit; }
    .btn-save { background: #2ecc71; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; width: 100%; margin-bottom: 5px;}
    
    .btn-view { background: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: block; text-align: center; font-size: 11px; margin-bottom: 4px; font-weight: 600;}
    .btn-receipt { background: #8e44ad; color: white; border: none; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: block; text-align: center; font-size: 11px; margin-bottom: 4px; font-weight: 600;}
    .btn-delete { background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: block; text-align: center; font-size: 11px; font-weight: 600;}

    .status-badge { padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700; display: inline-block; margin-top: 5px; }
    .badge-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .badge-completed { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    
    .action-group { display: flex; flex-direction: column; gap: 2px; }

    @media print {
        .filter-bar, .sidebar, .header, .btn-save, .btn-delete, .btn-view, .btn-receipt, .btn-filter, textarea, .search-input { display: none !important; }
        .container { padding: 0; max-width: 100%; }
        .worklist-card { box-shadow: none; padding: 0; }
        .lab-table th, .lab-table td { font-size: 10px; padding: 8px; border: 1px solid #eee; }
    }
</style>

<div class="container">
    <div class="worklist-card" style="margin-bottom:20px; border-left:5px solid #f39c12;">
        <div class="header-flex" style="margin-bottom:15px;">
            <h2 class="page-title" style="font-size:20px;">🚶 Walk-in Laboratory</h2>
            <span style="font-size:12px;color:#7f8c8d;">No consultation fee</span>
        </div>
        <?php if ($walkin_message): ?>
            <div style="background:#d4edda;color:#155724;padding:12px;border-radius:6px;margin-bottom:15px;"><?= htmlspecialchars($walkin_message) ?></div>
        <?php endif; ?>
        <?php if ($walkin_error): ?>
            <div style="background:#f8d7da;color:#721c24;padding:12px;border-radius:6px;margin-bottom:15px;"><?= htmlspecialchars($walkin_error) ?></div>
        <?php endif; ?>
        <form method="post" style="display:grid;grid-template-columns:1.3fr 1fr 1.5fr 1fr 1fr auto;gap:10px;align-items:end;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div>
                <label style="font-size:12px;font-weight:600;">Patient Name</label>
                <input type="text" name="walkin_name" class="form-control" placeholder="Optional" />
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;">Phone</label>
                <input type="text" name="walkin_phone" class="form-control" placeholder="Optional" />
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;">Laboratory Test</label>
                <select name="walkin_service_id" class="form-control" required>
                    <option value="">Select test...</option>
                    <?php if ($walkin_lab_services): while ($ws = $walkin_lab_services->fetch_assoc()): ?>
                        <option value="<?= (int)$ws['id'] ?>" data-price="<?= (float)$ws['price'] ?>">
                            <?= htmlspecialchars($ws['service_name']) ?> (KES <?= number_format((float)$ws['price'], 2) ?>)
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div style="grid-column:span 2;padding:10px 12px;background:#fff8e1;border:1px solid #ffe082;border-radius:6px;font-size:12px;">
                <strong>Payment:</strong> No payment is collected in Laboratory. The charge is sent to Central Cashier after the request is created.
            </div>
            <button type="submit" name="create_walkin_lab" class="btn-filter" style="background:#f39c12;">Create Request</button>
        </form>
    </div>

    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <label style="font-weight: 600; color: #2c3e50;">From:</label>
            <input type="date" name="start_date" class="input-date" value="<?= $start_date ?>">
            <label style="font-weight: 600; color: #2c3e50;">To:</label>
            <input type="date" name="end_date" class="input-date" value="<?= $end_date ?>">
            <button type="submit" class="btn-filter">Filter Range</button>
        </form>

        <input type="text" id="patientSearch" class="search-input" placeholder="🔍 Search Patient Name or ID...">
        
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-filter" style="background:#7f8c8d;">Print List</button>
            <a href="export_lab.php?start=<?= $start_date ?>&end=<?= $end_date ?>" class="btn-filter" style="background:#27ae60; text-decoration:none;">Download Excel</a>
        </div>
    </div>

    <div class="stats-flex">
        <div class="stat-card">
            <div class="stat-label">Total Revenue (Selected Range)</div>
            <div class="stat-value">KES <?= number_format($total_revenue, 2) ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #3498db;">
            <div class="stat-label">Total Tests</div>
            <div class="stat-value"><?= $total_count ?></div>
        </div>
    </div>

    <div class="worklist-card">
        <div class="header-flex">
            <h2 class="page-title">🔬 Laboratory Worklist</h2>
            <div style="font-size: 13px; color: #95a5a6;">Period: <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?></div>
        </div>

        <table class="lab-table" id="labTable">
            <thead>
                <tr>
                    <th width="12%">Date/Time</th>
                    <th width="18%">Patient</th>
                    <th width="18%">Investigation</th>
                    <th width="10%">Cost</th>
                    <th width="27%">Findings / Results</th>
                    <th width="15%">Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($rows) > 0): ?>
                    <?php foreach($rows as $job): ?>
                    <tr class="lab-row" <?php if($job['status'] == 'Completed') echo 'style="background:#fafafa;"'; ?>>
                        <td>
                            <strong style="font-size:13px;"><?= date('d-M-y', strtotime($job['created_at'])) ?></strong><br>
                            <span style="color:#999; font-size:11px;"><?= date('H:i A', strtotime($job['created_at'])) ?></span>
                        </td>
                        <td class="patient-cell">
                            <span class="patient-info"><?= htmlspecialchars($job['full_name']) ?></span>
                            <span class="patient-no">ID: <?= htmlspecialchars($job['patient_number']) ?></span>
                        </td>
                        <td>
                            <span class="test-name"><?= htmlspecialchars($job['service_name']) ?></span><br>
                            <span class="status-badge <?= ($job['status'] == 'Completed') ? 'badge-completed' : 'badge-pending' ?>">
                                <?= $job['status'] ?? 'Pending' ?>
                            </span>
                        </td>
                        <td><span class="test-cost">KES <?= number_format($job['price'], 2) ?></span></td>
                        
                        <form method="post">
                            <td>
                                <textarea name="findings" rows="2" placeholder="Enter findings..."><?= htmlspecialchars($job['results'] ?? '') ?></textarea>
                            </td>
                            <td>
                                <div class="action-group">
                                    <input type="hidden" name="record_id" value="<?= $job['id'] ?>">
                                    <button type="submit" name="save_lab_result" class="btn-save" style="padding: 5px;">
                                        <?= ($job['status'] == 'Completed') ? 'Update' : 'Submit' ?>
                                    </button>
                                    <?php if($job['status'] == 'Completed'): ?>
                                        <a href="lab_results.php?id=<?= $job['id'] ?>" target="_blank" class="btn-view">View Report</a>
                                    <?php endif; ?>
                                    <a href="lab_receipt.php?id=<?= $job['id'] ?>" target="_blank" class="btn-receipt">Receipt</a>
                                    <a href="javascript:void(0)" 
                                       class="btn-delete" 
                                       onclick="return confirm('Are you sure?')">Delete</a>
                                </div>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="noResultsRow">
                        <td colspan="6" style="text-align:center; padding:60px; color:#bdc3c7;">No laboratory requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('patientSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('.lab-row');
    
    rows.forEach(row => {
        // Search within the patient cell (Name and ID)
        let patientData = row.querySelector('.patient-cell').textContent.toLowerCase();
        
        if (patientData.indexOf(filter) > -1) {
            row.style.display = ""; // Show
        } else {
            row.style.display = "none"; // Hide
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>