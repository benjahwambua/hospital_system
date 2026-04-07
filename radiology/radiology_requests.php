<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $enc_id = intval($_POST['encounter_id']);
    $procedure = $conn->real_escape_string($_POST['procedure']);
    $stmt = $conn->prepare("INSERT INTO procedures (encounter_id, procedure_name, requested_by, requested_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("isi", $enc_id, $procedure, $_SESSION['user_id']);
    $stmt->execute(); $stmt->close();
}

$encounters = $conn->query("SELECT e.id, p.full_name FROM encounters e JOIN patients p ON p.id=e.patient_id ORDER BY e.created_at DESC");
$procedures = $conn->query("SELECT pr.*, p.full_name FROM procedures pr JOIN encounters e ON e.id=pr.encounter_id JOIN patients p ON p.id=e.patient_id ORDER BY pr.requested_at DESC");

$all_procedures = ['X-Ray','Ultrasound','CT Scan','ECG','Minor Surgery'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
<h3>Radiology / Procedures</h3>
<form method="post">
<label>Encounter</label>
<select name="encounter_id" class="form-control">
<?php while($e=$encounters->fetch_assoc()): ?>
<option value="<?=$e['id']?>"><?=htmlspecialchars($e['full_name'])?> (<?=$e['id']?>)</option>
<?php endwhile; ?>
</select>
<label>Procedure / Radiology</label>
<select name="procedure" class="form-control">
<?php foreach($all_procedures as $p): ?><option><?=htmlspecialchars($p)?></option><?php endforeach; ?>
</select>
<div style="margin-top:10px;"><button class="btn" type="submit">Request Procedure</button></div>
</form>

<hr>
<h4>All Procedures</h4>
<table class="table">
<thead><tr><th>Patient</th><th>Procedure</th><th>Requested At</th></tr></thead>
<tbody>
<?php while($pr=$procedures->fetch_assoc()): ?>
<tr>
<td><?=htmlspecialchars($pr['full_name'])?></td>
<td><?=htmlspecialchars($pr['procedure_name'])?></td>
<td><?=$pr['requested_at']?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
