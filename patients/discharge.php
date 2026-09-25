<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin','doctor','nurse']);

$pid = intval($_GET['id'] ?? 0);
if (!$pid) header('Location: /hospital_system/patients.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); exit('Invalid security token.'); }
    $summary = trim((string)($_POST['summary'] ?? ''));
    $user = current_user_id();
    $stmt = $conn->prepare("INSERT INTO discharges (patient_id, discharged_by, summary) VALUES (?,?,?)");
    $stmt->bind_param("iis",$pid,$user,$summary);
    $stmt->execute();
    $stmt->close();
    // optionally mark current appointment as completed
    $updateAppointment = $conn->prepare("UPDATE appointments SET status='completed' WHERE patient_id=? AND status IN ('in_consultation','waiting') LIMIT 1");
    if ($updateAppointment) {
        $updateAppointment->bind_param('i', $pid);
        $updateAppointment->execute();
        $updateAppointment->close();
    }
    header("Location: /hospital_system/patients/history.php?id={$pid}");
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
$patientStmt = $conn->prepare("SELECT * FROM patients WHERE id=? LIMIT 1");
$patientStmt->bind_param('i', $pid);
$patientStmt->execute();
$patient = $patientStmt->get_result()->fetch_assoc();
$patientStmt->close();
?>
<div class="main">
  <div class="page-title">Discharge patient — <?= htmlspecialchars($patient['full_name'] ?? '') ?></div>
  <div class="card" style="max-width:800px;">
    <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <label>Discharge Summary</label>
      <textarea class="form-control" name="summary" required></textarea>
      <div style="margin-top:8px;"><button class="btn" type="submit">Discharge</button></div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
