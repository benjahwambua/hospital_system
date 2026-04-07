<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

// patient_id via GET
$patient_id = intval($_GET['patient_id'] ?? 0);
$save_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pid = intval($_POST['patient_id']);

    // doctor from session
    if (!isset($_SESSION['user_id'])) {
        die("Error: Doctor ID not found in session.");
    }
    $doctor = intval($_SESSION['user_id']);

    // text fields
    $complaint = $conn->real_escape_string($_POST['complaint'] ?? '');
    $diag_text = $conn->real_escape_string($_POST['diagnosis_text'] ?? '');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    // vitals
    $temp = $conn->real_escape_string($_POST['temperature'] ?? '');
    $bp = $conn->real_escape_string($_POST['blood_pressure'] ?? '');
    $hr = $conn->real_escape_string($_POST['heart_rate'] ?? '');
    $resp = $conn->real_escape_string($_POST['resp_rate'] ?? '');

    // ---------- INSERT VITALS ----------
    $stmt = $conn->prepare("
        INSERT INTO vitals (patient_id, doctor_id, temperature, blood_pressure, heart_rate, respiratory_rate, notes)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmt->bind_param("iisssss", $pid, $doctor, $temp, $bp, $hr, $resp, $notes);
    $stmt->execute();
    $stmt->close();

    // ---------- INSERT DIAGNOSIS ----------
    $stmt = $conn->prepare("
        INSERT INTO diagnosis (patient_id, doctor_id, complaints, diagnosis_text, notes)
        VALUES (?,?,?,?,?)
    ");
    $stmt->bind_param("iisss", $pid, $doctor, $complaint, $diag_text, $notes);
    $stmt->execute();
    $stmt->close();

    // ---------- Insert into patient history ----------
    $hist_text = "Complaint: $complaint\nDiagnosis: $diag_text\nNotes: $notes";
    $stmt = $conn->prepare("
        INSERT INTO patient_history (patient_id, type, details)
        VALUES (?, 'diagnosis', ?)
    ");
    $stmt->bind_param("is", $pid, $hist_text);
    $stmt->execute();
    $stmt->close();

    // audit log
    audit('diagnosis_added', "patient={$pid}");

    // Redirect to patient history page
    header("Location: /hospital_system/patients/history.php?id={$pid}");
    exit;
}


// ---------- Load Patients ----------
$patients = $conn->query("SELECT id, hospital_number, full_name FROM patients ORDER BY full_name ASC");

// ---------- Load Previous Diagnosis ----------
$history_diag = [];
if ($patient_id) {
    $stmt = $conn->prepare("
        SELECT d.*, u.full_name AS doctor_name
        FROM diagnosis d
        LEFT JOIN users u ON u.id = d.doctor_id
        WHERE d.patient_id=?
        ORDER BY d.created_at DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $history_diag = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main">
  <div class="page-title">Diagnostics</div>

  <div class="card" style="max-width:900px">
    <form method="post">
      <label>Patient</label>
      <select name="patient_id" class="form-control" required onchange="if(this.value) window.location='?patient_id='+this.value">
        <option value="">-- select --</option>
        <?php while($p=$patients->fetch_assoc()): ?>
          <option value="<?php echo $p['id']; ?>" <?php if($p['id']==$patient_id) echo 'selected'; ?>>
              <?php echo htmlspecialchars($p['full_name'] . ' ['.$p['hospital_number'].']'); ?>
          </option>
        <?php endwhile; ?>
      </select>

      <label style="margin-top:8px;">Main Complaint</label>
      <textarea class="form-control" name="complaint" required></textarea>

      <label style="margin-top:8px;">Diagnosis</label>
      <textarea class="form-control" name="diagnosis_text"></textarea>

      <label style="margin-top:8px;">Notes</label>
      <textarea class="form-control" name="notes"></textarea>

      <div style="display:flex;gap:8px;margin-top:8px;">
        <div style="flex:1;"><label>Temp</label><input class="form-control" name="temperature"></div>
        <div style="flex:1;"><label>BP</label><input class="form-control" name="blood_pressure"></div>
        <div style="flex:1;"><label>Pulse</label><input class="form-control" name="heart_rate"></div>
        <div style="flex:1;"><label>Resp</label><input class="form-control" name="resp_rate"></div>
      </div>

      <div style="margin-top:10px;">
        <button class="btn" type="submit">Save Consultation</button>
      </div>
    </form>
  </div>

  <?php if (!empty($history_diag)): ?>
  <div class="card" style="margin-top:12px;">
    <h4>History</h4>
    <?php foreach($history_diag as $d): ?>
      <div style="padding:8px;border-bottom:1px solid #f2f6ff;">
        <strong><?php echo htmlspecialchars($d['complaints'] ?: $d['diagnosis_text']); ?></strong>
        <div style="font-size:12px;color:#777;"><?php echo nl2br(htmlspecialchars($d['notes'])); ?></div>
        <div style="font-size:12px;color:#999;">
           By <?php echo htmlspecialchars($d['doctor_name']); ?> — <?php echo $d['created_at']; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
