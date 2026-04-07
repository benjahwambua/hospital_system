<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$maternity_id = intval($_GET['id'] ?? 0);
if (!$maternity_id) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $visit_type = $conn->real_escape_string($_POST['visit_type']);
  $bp = $conn->real_escape_string($_POST['bp'] ?? '');
  $temp = $conn->real_escape_string($_POST['temp'] ?? '');
  $pulse = $conn->real_escape_string($_POST['pulse'] ?? '');
  $weight = $conn->real_escape_string($_POST['weight'] ?? '');
  $fhr = $conn->real_escape_string($_POST['fetal_heart_rate'] ?? '');
  $cervix = $conn->real_escape_string($_POST['cervix'] ?? '');
  $membrane = $conn->real_escape_string($_POST['membrane_status'] ?? '');
  $drugs = $conn->real_escape_string($_POST['drugs_given'] ?? '');
  $notes = $conn->real_escape_string($_POST['notes'] ?? '');

  $stmt = $conn->prepare("INSERT INTO maternity_visits (maternity_id, visit_type, bp, temp, pulse, weight, fetal_heart_rate, cervix, membrane_status, drugs_given, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
  $stmt->bind_param("issssssssss", $maternity_id, $visit_type, $bp, $temp, $pulse, $weight, $fhr, $cervix, $membrane, $drugs, $notes);
  $stmt->execute();
  $stmt->close();

  audit('maternity_visit', "maternity_id={$maternity_id},type={$visit_type}");
  header("Location: view.php?id={$maternity_id}");
  exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$m = $conn->query("SELECT m.*, p.full_name FROM maternity m JOIN patients p ON p.id=m.patient_id WHERE m.id={$maternity_id}")->fetch_assoc();
?>

<div class="main">
  <div class="page-title">Add Visit — <?= htmlspecialchars($m['full_name']) ?></div>
  <div class="card" style="max-width:900px;">
    <form method="post">
      <div style="display:flex;gap:8px;">
        <div style="flex:1"><label>Visit Type</label><select name="visit_type" class="form-control"><option>ANC</option><option>PNC</option><option>Labour</option></select></div>
        <div style="flex:1"><label>BP</label><input name="bp" class="form-control"></div>
        <div style="flex:1"><label>Temp</label><input name="temp" class="form-control"></div>
      </div>
      <div style="display:flex;gap:8px;margin-top:8px;">
        <div style="flex:1"><label>Pulse</label><input name="pulse" class="form-control"></div>
        <div style="flex:1"><label>Weight</label><input name="weight" class="form-control"></div>
        <div style="flex:1"><label>Fetal HR</label><input name="fetal_heart_rate" class="form-control"></div>
      </div>
      <label style="margin-top:8px">Cervix</label><input name="cervix" class="form-control">
      <label style="margin-top:8px">Membrane status</label><input name="membrane_status" class="form-control">
      <label style="margin-top:8px">Drugs given</label><textarea name="drugs_given" class="form-control"></textarea>
      <label style="margin-top:8px">Notes</label><textarea name="notes" class="form-control"></textarea>
      <div style="margin-top:12px"><button class="btn" type="submit">Save Visit</button></div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
