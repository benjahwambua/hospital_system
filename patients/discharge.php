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
    $summary
    $summary = $conn->real_escape_string($_POST['summary'] ?? '');
    $user = current_user_id();
    $stmt = $conn->prepare("INSERT INTO discharges (patient_id, discharged_by, summary) VALUES (?,?,?)");
    $stmt->bind_param("iis",$pid,$user,$summary);
    $stmt->execute();
    $stmt->close();
    // optionally mark current appointment as completed
    $conn->query("UPDATE appointments SET status='completed' WHERE patient_id=$pid AND status IN ('in_consultation','waiting') LIMIT 1");
    header("Location: /hospital_system/patients/history.php?id={$pid}");
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
$patient = $conn->query("SELECT * FROM patients WHERE id=$pid")->fetch_assoc();
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
