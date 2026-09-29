<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_module_access($conn, 'laboratory', 'create');
require_role(['admin','lab_tech']);

// Ensure walk-in registrations work even if the migration has not yet been run.
ensure_walkin_column($conn);

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
            $serviceStmt = $conn->prepare("SELECT id, service_name FROM services_master WHERE id = ? AND active = 1 AND category = 'lab' LIMIT 1");
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
                    $resolved = get_service_price($conn, $service_id, null, null);
                    $price = (float)$resolved['price'];
                    $conn->begin_transaction();
                    try {
                        // Create a separate walk-in patient for each laboratory visit so the
                        // patient's name, phone, test, invoice and payment remain traceable.
                        $walkinPatientNumber = 'TEMP-WLK-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
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

                        // Walk-in patient numbers use the short WLK format, e.g. WLK0011.
                        $walkinPatientNumber = 'WLK' . str_pad((string)$walkinPatientId, 4, '0', STR_PAD_LEFT);
                        $walkinNumberStmt = $conn->prepare('UPDATE patients SET patient_number = ? WHERE id = ?');
                        if (!$walkinNumberStmt) throw new Exception('Unable to assign walk-in patient number: ' . $conn->error);
                        $walkinNumberStmt->bind_param('si', $walkinPatientNumber, $walkinPatientId);
                        if (!$walkinNumberStmt->execute()) throw new Exception('Unable to assign walk-in patient number: ' . $walkinNumberStmt->error);
                        $walkinNumberStmt->close();

                        // Put the walk-in laboratory request under the same Visit/Encounter.
                        $visitId = get_or_create_current_visit($conn, $walkinPatientId, 'Walk-in', 'Laboratory');
                        add_patient_service($conn, $walkinPatientId, $service_id, $visitId, null, null, 1, 0, 'Pending', null);

                        $invoiceId = get_or_create_visit_invoice($conn, $walkinPatientId, $visitId);
                        $invoiceItemId = add_invoice_item($conn, $invoiceId, 'Lab: ' . $labService['service_name'], 1, $price, 'lab', $service_id);
                        post_invoice_journal($conn, $invoiceId, $walkinPatientId, $price, 'Walk-in laboratory', $invoiceItemId);
                        $conn->commit();
                        header('Location: lab_results.php?created=1&patient_id=' . $walkinPatientId . '&service_id=' . $service_id);
                        exit;
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
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Invalid security token.');
    }
    // Lab requests can already have a corresponding Central Billing line.
    // Never physically delete a clinical request and leave its financial record orphaned.
    http_response_code(409);
    exit('Laboratory requests are not deleted after submission. Use the clinical cancellation/reversal workflow so the clinical and financial records remain synchronized.');
}

// Laboratory requests are submitted here and processed in the Lab Results worklist.
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
$created = isset($_GET['created']);
$walkin_lab_services = $conn->query("SELECT id, service_name FROM services_master WHERE active = 1 AND category = 'lab' ORDER BY service_name ASC");
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

/* HMS unified operational workspace */
.main-content{background:#f5f7fb;min-height:calc(100vh - 72px)}
.main-content>.container-fluid{max-width:1500px}
.main-content h1,.main-content h2,.main-content h3{color:#25324a}
.main-content .card{border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 18px rgba(31,45,61,.05);overflow:hidden}
.main-content .card-header{background:#fff;border-bottom:1px solid #edf0f5;color:#25324a}
.main-content .table thead th{background:#f8fafc;border-top:0;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.35px}
.main-content .table td{border-color:#edf0f5;vertical-align:middle;font-size:13px}
.main-content .table tbody tr:hover{background:#f8fbff}
.main-content .form-control{border-color:#d7dee8;border-radius:9px}
.main-content .form-control:focus{border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}
.main-content .btn{border-radius:8px;font-weight:700}
.main-content .btn-primary{background:#075b9d;border-color:#075b9d}
.main-content .page-header,.main-content .d-flex.justify-content-between.align-items-center{margin-bottom:20px!important}
</style>

<div class="container">
    <div class="worklist-card" style="max-width:900px;margin:0 auto;border-left:5px solid #f39c12;">
        <div class="header-flex" style="margin-bottom:15px;">
            <h2 class="page-title" style="font-size:20px;">🔬 Laboratory Request</h2>
            <span style="font-size:12px;color:#7f8c8d;">Request only</span>
        </div>
        <?php if ($created): ?>
            <div style="background:#d4edda;color:#155724;padding:12px;border-radius:6px;margin-bottom:15px;">
                Laboratory request submitted and moved to Lab Results.
            </div>
        <?php endif; ?>
        <?php if ($walkin_error): ?>
            <div style="background:#f8d7da;color:#721c24;padding:12px;border-radius:6px;margin-bottom:15px;"><?= htmlspecialchars($walkin_error) ?></div>
        <?php endif; ?>
        <p style="color:#666;font-size:14px;margin-top:0;">
            Create a laboratory request here. Once submitted, it is handled from <strong>Lab Results</strong>.
        </p>
        <form method="post" style="display:grid;grid-template-columns:1fr 1fr;gap:15px;align-items:end;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div>
                <label style="font-size:12px;font-weight:600;">Patient Name</label>
                <input type="text" name="walkin_name" class="form-control" placeholder="Optional">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;">Phone</label>
                <input type="text" name="walkin_phone" class="form-control" placeholder="Optional">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;">Laboratory Test</label>
                <select name="walkin_service_id" class="form-control" required>
                    <option value="">Select test...</option>
                    <?php if ($walkin_lab_services): while ($ws = $walkin_lab_services->fetch_assoc()): ?>
                        <option value="<?= (int)$ws['id'] ?>">
                            <?= htmlspecialchars($ws['service_name']) ?> (KES <?= number_format((float)$ws['price'], 2) ?>)
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div style="padding:10px 12px;background:#fff8e1;border:1px solid #ffe082;border-radius:6px;font-size:12px;">
                <strong>Payment:</strong> Laboratory does not collect payment. Charges go to Central Cashier.
            </div>
            <button type="submit" name="create_walkin_lab" class="btn-filter" style="background:#f39c12;grid-column:1 / -1;">Submit Laboratory Request</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>