<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';

$doctor_id = $_SESSION['user_id'];
$patient_id = intval($_GET['patient_id'] ?? 0);
if (!$patient_id) { echo "<div class='card'>No patient selected</div>"; include __DIR__ . '/../includes/footer.php'; exit; }
$patient = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $complaint = $conn->real_escape_string($_POST['complaint'] ?? '');
    $diagnosis = $conn->real_escape_string($_POST['diagnosis_text'] ?? '');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    // save consultation
    $stmt = $conn->prepare("INSERT INTO vitals (patient_id, recorded_by_user_id, temperature, blood_pressure, heart_rate, notes) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("iidsis", $patient_id, $doctor_id, $temp, $bp, $pulse, $notes);
    // vitals (optional)
    $temp = $_POST['temperature'] === '' ? null : (float)$_POST['temperature'];
    if ($temp !== null){
        $bp = $conn->real_escape_string($_POST['blood_pressure'] ?? '');
        $pulse = $_POST['heart_rate'] === '' ? null : intval($_POST['heart_rate']);
        $stmt = $conn->prepare("INSERT INTO vitals (id, patient_id, recorded_by_user_id, temperature, blood_pressure, heart_rate, notes) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("iidsis",$id,$doctor_id,$temp,$bp,$pulse,$notes); @$stmt->execute(); $stmt->close();
    }
    audit('consultation_saved',"patient:$patient_id doctor:$doctor_id");
    header("Location: /hospital_system/patients/patient_history.php?id=$patient_id"); exit;
}
?>
<div class="card">
  <h3>Consultation — <?php echo htmlspecialchars($patient['full_name']); ?></h3>
  <form method="post">
    <label>Complaint</label><textarea name="complaint" class="form-control" required></textarea>
    <label style="margin-top:8px">Diagnosis</label><textarea name="diagnosis_text" class="form-control"></textarea>
    <label style="margin-top:8px">Notes</label><textarea name="notes" class="form-control"></textarea>
    <div style="display:flex;gap:8px;margin-top:8px">
      <input name="temperature" placeholder="Temp °C" class="form-control">
      <input name="blood_pressure" placeholder="BP" class="form-control">
      <input name="heart_rate" placeholder="Pulse" class="form-control">
    </div>
    <div style="margin-top:12px"><button class="btn">Save Consultation</button></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
