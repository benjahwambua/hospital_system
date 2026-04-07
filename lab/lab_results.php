<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// -------------------------
// Handle Lab Result Submission
// -------------------------
if (isset($_POST['save_lab_result'])) {
    $record_id = intval($_POST['record_id']);
    $findings = $_POST['findings'] ?? '';
    $status = 'Completed'; 

    $stmt = $conn->prepare("UPDATE patient_services SET results = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $findings, $status, $record_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Results saved successfully!'); window.location.href='lab_requests.php';</script>";
        exit;
    } else {
        $error = "Error updating record: " . $conn->error;
    }
    $stmt->close();
}

// -------------------------
// Fetch Lab Worklist
// -------------------------
// Primary Sort: Pending items first. Secondary Sort: Newest requests first.
$query = "SELECT ps.*, p.full_name, p.patient_number, sm.service_name 
          FROM patient_services ps 
          JOIN patients p ON ps.patient_id = p.id 
          JOIN services_master sm ON ps.service_id = sm.id 
          WHERE ps.category = 'lab' 
          ORDER BY (ps.status = 'Pending') DESC, ps.created_at DESC";

$lab_jobs = $conn->query($query);
?>

<style>
    :root { --primary-blue: #007bff; --success-green: #28a745; --light-gray: #f8f9fa; }
    body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; }
    .container { padding: 25px; max-width: 1400px; margin: auto; }
    .worklist-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
    
    .lab-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .lab-table th { background: var(--light-gray); color: #444; padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; font-size: 13px; }
    .lab-table td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: top; }
    
    /* Highlight pending rows */
    .row-pending { background: #fff; }
    .row-completed { background: #fcfcfc; color: #777; }

    .patient-box { display: flex; flex-direction: column; }
    .patient-name { font-weight: bold; color: var(--primary-blue); }
    .patient-id { font-size: 11px; color: #888; }

    textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; font-family: inherit; transition: 0.2s; }
    textarea:focus { border-color: var(--primary-blue); outline: none; background: #fff; }

    .btn-action { padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; font-weight: bold; font-size: 12px; text-decoration: none; display: inline-block; transition: 0.2s; }
    .btn-save { background: var(--success-green); color: white; width: 100%; margin-bottom: 5px; }
    .btn-print { background: #6c757d; color: white; width: 100%; text-align: center; }
    .btn-action:hover { opacity: 0.8; }

    .status-badge { font-size: 10px; padding: 3px 8px; border-radius: 12px; font-weight: bold; text-transform: uppercase; }
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-completed { background: #d4edda; color: #155724; }
</style>

<div class="container">
    <div class="worklist-card">
        <h2 style="margin-top:0; color: #333;">🔬 Laboratory Worklist</h2>
        <p style="color:#666; font-size: 14px;">Update findings to sync with Doctor's Dashboard. Use the print icon for completed reports.</p>

        <table class="lab-table">
            <thead>
                <tr>
                    <th width="15%">Date & Time</th>
                    <th width="20%">Patient Details</th>
                    <th width="20%">Investigation</th>
                    <th width="30%">Results/Findings</th>
                    <th width="15%">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($lab_jobs && $lab_jobs->num_rows > 0): ?>
                    <?php while($job = $lab_jobs->fetch_assoc()): 
                        $is_done = ($job['status'] == 'Completed');
                    ?>
                    <tr class="<?= $is_done ? 'row-completed' : 'row-pending' ?>">
                        <td>
                            <strong><?= date('d M, Y', strtotime($job['created_at'])) ?></strong><br>
                            <small><?= date('H:i', strtotime($job['created_at'])) ?></small>
                        </td>
                        <td>
                            <div class="patient-box">
                                <span class="patient-name"><?= htmlspecialchars($job['full_name']) ?></span>
                                <span class="patient-id">ID: <?= htmlspecialchars($job['patient_number']) ?></span>
                            </div>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($job['service_name']) ?></strong><br>
                            <span class="status-badge <?= $is_done ? 'badge-completed' : 'badge-pending' ?>">
                                <?= $job['status'] ?? 'Pending' ?>
                            </span>
                        </td>
                        <form method="post">
                            <td>
                                <textarea name="findings" rows="2" placeholder="Enter results..."><?= htmlspecialchars($job['results'] ?? '') ?></textarea>
                            </td>
                            <td>
                                <input type="hidden" name="record_id" value="<?= $job['id'] ?>">
                                <button type="submit" name="save_lab_result" class="btn-action btn-save">
                                    <?= $is_done ? 'Update Result' : 'Save & Close' ?>
                                </button>
                                
                                <?php if($is_done): ?>
                                    <a href="print_result.php?id=<?= $job['id'] ?>" target="_blank" class="btn-action btn-print">
                                        🖨️ Print Report
                                    </a>
                                <?php endif; ?>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:30px;">No lab requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>