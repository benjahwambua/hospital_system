<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_module_access($conn, 'laboratory', 'view');

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token'];

// -------------------------
// Handle Lab Result Submission
// -------------------------
if (isset($_POST['save_lab_result'])) {
    $action = ($_POST['result_action'] ?? 'save');
    require_module_access($conn, 'laboratory', $action === 'complete' ? 'approve' : 'edit');
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) { $error='Invalid security token.'; }
    else {
        $record_id = intval($_POST['record_id']);
        $findings = $_POST['findings'] ?? '';
        $status = ($action === 'complete') ? 'Completed' : 'Pending';

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE patient_services SET results = ?, status = ? WHERE id = ? AND category = 'lab'");
            $stmt->bind_param("ssi", $findings, $status, $record_id);
            if (!$stmt->execute()) throw new Exception("Error updating laboratory record: " . $stmt->error);
            $stmt->close();

            if ($action === 'complete') {
                consume_lab_materials_for_service($conn, $record_id, (int)($_SESSION['user_id'] ?? 0));
            }

            $conn->commit();
            header('Location: lab_results.php?saved=1');
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Shared layout is rendered only after all POST processing and redirects.
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// -------------------------
// Fetch Lab Worklist
// -------------------------
$usageExists = $conn->query("SHOW TABLES LIKE 'lab_resource_usage'");
$usageSelect = ($usageExists && $usageExists->num_rows)
    ? ", (SELECT GROUP_CONCAT(CONCAT(li.item_name, ' × ', FORMAT(lru.quantity, 4), ' ', COALESCE(lru.unit, li.unit)) SEPARATOR ', ')
         FROM lab_resource_usage lru
         INNER JOIN lab_inventory li ON li.id = lru.inventory_id
         WHERE lru.patient_service_id = ps.id) AS materials_used"
    : ", NULL AS materials_used";

$query = "SELECT ps.*, p.full_name, p.patient_number, sm.service_name, v.visit_number{$usageSelect}
          FROM patient_services ps
          JOIN patients p ON ps.patient_id = p.id
          JOIN services_master sm ON ps.service_id = sm.id
          LEFT JOIN visits v ON v.id = ps.visit_id
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
    .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:10px 14px; border-radius:6px; margin-bottom:15px; }
    .alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; padding:10px 14px; border-radius:6px; margin-bottom:15px; }

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
    <div class="worklist-card">
        <h2 style="margin-top:0; color:#333;">🔬 Laboratory Worklist</h2>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert-success">Laboratory result saved successfully.</div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <table class="lab-table">
            <thead>
                <tr>
                    <th width="15%">Date & Time</th>
                    <th width="18%">Patient Details</th>
                    <th width="12%">Visit</th>
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
                        <td><?= htmlspecialchars($job['visit_number'] ?? 'Legacy') ?></td>
                        <td>
                            <strong><?= htmlspecialchars($job['service_name']) ?></strong><br>
                            <span class="status-badge <?= $is_done ? 'badge-completed' : 'badge-pending' ?>">
                                <?= htmlspecialchars($job['status'] ?? 'Pending') ?>
                            </span>
                        </td>
                        <td>
                            <form method="post">
                                <textarea name="findings" rows="2" placeholder="Enter results..."><?= htmlspecialchars($job['results'] ?? '') ?></textarea>
                        </td>
                        <td>
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="record_id" value="<?= $job['id'] ?>">
                                <input type="hidden" name="result_action" value="complete">
                                <button type="submit" name="save_lab_result" class="btn-action btn-save">
                                    <?= $is_done ? 'Update Result' : 'Save & Close' ?>
                                </button>
                                <?php if($is_done): ?>
                                    <a href="print_result.php?id=<?= $job['id'] ?>" target="_blank" class="btn-action btn-print">
                                        🖨️ Print Report
                                    </a>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px;">No lab requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>