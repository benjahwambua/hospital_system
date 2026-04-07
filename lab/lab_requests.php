<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// --- 1. HANDLE DATE RANGE (Defaults to today) ---
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// --- 2. HANDLE DELETE ACTION ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM patient_services WHERE id = ? AND category = 'lab'");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        echo "<script>alert('Record deleted successfully'); window.location.href='lab_requests.php?start_date=$start_date&end_date=$end_date';</script>";
    }
    $stmt->close();
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
                                    <a href="lab_requests.php?delete_id=<?= $job['id'] ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
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