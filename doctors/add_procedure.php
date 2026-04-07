<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patient_id=intval($_GET['patient_id']);

if($_SERVER['REQUEST_METHOD']==='POST'){
  $stmt=$conn->prepare("
    INSERT INTO patient_procedures (patient_id, procedure_id, doctor_id)
    VALUES (?,?,?)
  ");
  $stmt->bind_param("iii",$patient_id,$_POST['procedure_id'],$_SESSION['user_id']);
  $stmt->execute();
  header("Location: /hospital_system/patients/patient_history.php?id=$patient_id");
  exit;
}

$procs=$conn->query("SELECT * FROM procedures ORDER BY name");
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<div class="card">
<h3>Add Procedure</h3>
<form method="post">
<select name="procedure_id" class="form-control">
<?php while($p=$procs->fetch_assoc()): ?>
<option value="<?= $p['id'] ?>"><?= $p['name'] ?> (<?= number_format($p['price'],2) ?>)</option>
<?php endwhile; ?>
</select>
<button class="btn">Add Procedure</button>
</form>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
