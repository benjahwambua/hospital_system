<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$msg = "";

if (isset($_POST['update_profile'])) {
    $enc_id = intval($_POST['encounter_id']);
    $diagnosis = $conn->real_escape_string($_POST['diagnosis']);
    $notes     = $conn->real_escape_string($_POST['notes']);
    $allergies = $conn->real_escape_string($_POST['allergies']);
    $next_appt = $_POST['next_appointment'] ?: null;

    $stmt = $conn->prepare("UPDATE encounters SET diagnosis=?, doctor_notes=?, allergies=?, next_appointment=? WHERE id=?");
    $stmt->bind_param("ssssi", $diagnosis, $notes, $allergies, $next_appt, $enc_id);
    $stmt->execute();
    $stmt->close();
    $msg = "Patient profile updated.";
}

$encounters = $conn->query("SELECT e.id, p.full_name FROM encounters e JOIN patients p ON p.id=e.patient_id WHERE e.status='open' ORDER BY e.created_at DESC");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
<h3>Doctor Patient Profile</h3>
<?php if($msg): ?><div class="alert alert-success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<form method="post">
<label>Encounter</label>
<select name="encounter_id" class="form-control">
<?php while($e=$encounters->fetch_assoc()): ?>
<option value="<?=$e['id']?>"><?=htmlspecialchars($e['full_name'])?> (<?=$e['id']?>)</option>
<?php endwhile; ?>
</select>
<label>Diagnosis</label><input name="diagnosis" class="form-control">
<label>Doctor Notes</label><textarea name="notes" class="form-control"></textarea>
<label>Allergies</label><textarea name="allergies" class="form-control"></textarea>
<label>Next Appointment</label><input type="date" name="next_appointment" class="form-control">
<div style="margin-top:10px;"><button class="btn" name="update_profile">Save Profile</button></div>
</form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
