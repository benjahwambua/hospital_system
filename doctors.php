<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_login();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$doctor_id = $_SESSION['user_id']; // logged-in doctor

//--------------------------------------------
// Load patient
//--------------------------------------------
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$patient = null;

if ($patient_id) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$save_msg = "";

//--------------------------------------------
// SAVE CONSULTATION
//--------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['patient_id'])) {

    $pid = intval($_POST['patient_id']);

    $complaint      = trim($_POST['complaint']);
    $diagnosis_text = trim($_POST['diagnosis_text']);
    $notes          = trim($_POST['notes']);

    // vitals (NULL if empty)
    $temperature = $_POST['temperature'] === "" ? NULL : (float)$_POST['temperature'];
    $bp          = $_POST['blood_pressure'] === "" ? NULL : $_POST['blood_pressure'];
    $pulse       = $_POST['heart_rate'] === "" ? NULL : (int)$_POST['heart_rate'];
    $resp        = $_POST['resp_rate'] === "" ? NULL : (int)$_POST['resp_rate'];
    $oxygen      = $_POST['oxygen_saturation'] === "" ? NULL : (int)$_POST['oxygen_saturation'];
    $weight      = $_POST['weight'] === "" ? NULL : (float)$_POST['weight'];
    $height      = $_POST['height'] === "" ? NULL : (float)$_POST['height'];

    //--------------------------------------------
    // 1. Save VITALS
    //--------------------------------------------
    $stmt = $conn->prepare("
        INSERT INTO vitals 
        (patient_id, recorded_by_user_id, temperature, blood_pressure, heart_rate, respiratory_rate, oxygen_saturation, weight, height, notes)
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "iidsiiidds",
        $pid, $doctor_id, $temperature, $bp, $pulse, $resp, $oxygen, $weight, $height, $notes
    );

    $stmt->execute();
    $stmt->close();

    //--------------------------------------------
    // 2. Save DIAGNOSIS
    //--------------------------------------------
    $stmt = $conn->prepare("
        INSERT INTO diagnosis (patient_id, doctor_id, complaints, diagnosis_text, notes)
        VALUES (?,?,?,?,?)
    ");

    $stmt->bind_param("iisss", $pid, $doctor_id, $complaint, $diagnosis_text, $notes);
    $stmt->execute();
    $stmt->close();

    //--------------------------------------------
    // 3. Save LAB REQUESTS
    //--------------------------------------------
    $lab_text = trim($_POST['lab_tests_text']);
    if ($lab_text !== "") {
        $tests = preg_split("/\r\n|\n|\r/", $lab_text);

        $stmt = $conn->prepare("
            INSERT INTO lab_requests (patient_id, recorded_by_user_id, tests, notes)
            VALUES (?,?,?,?)
        ");

        foreach ($tests as $t) {
            $t = trim($t);
            if ($t === "") continue;
            $stmt->bind_param("iiss", $pid, $doctor_id, $t, $notes);
            $stmt->execute();
        }
        $stmt->close();
    }

    $save_msg = "Consultation saved successfully.";
}

//--------------------------------------------
// LOAD HISTORY
//--------------------------------------------
$diagnoses = [];
$vitals = [];
$appointments = [];
$labs = [];

if ($patient_id) {

    $q = $conn->query("
        SELECT d.*, u.full_name AS doctor_name
        FROM diagnosis d
        LEFT JOIN users u ON u.id = d.doctor_id
        WHERE d.patient_id = $patient_id
        ORDER BY d.created_at DESC
    ");
    while ($r = $q->fetch_assoc()) $diagnoses[] = $r;

    $q = $conn->query("SELECT * FROM vitals WHERE patient_id=$patient_id ORDER BY created_at DESC");
    while ($r = $q->fetch_assoc()) $vitals[] = $r;

    $q = $conn->query("SELECT * FROM appointments WHERE patient_id=$patient_id ORDER BY appointment_date DESC");
    while ($r = $q->fetch_assoc()) $appointments[] = $r;

    $q = $conn->query("SELECT * FROM lab_requests WHERE patient_id=$patient_id ORDER BY created_at DESC");
    while ($r = $q->fetch_assoc()) $labs[] = $r;
}
?>

<div class="main">
    <div class="page-title">Doctor — Patient Clinical View</div>

    <?php if ($save_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($save_msg) ?></div>
    <?php endif; ?>

    <?php if (!$patient): ?>
        <div class="card">Select a patient from the Patients page.</div>
    <?php else: ?>

    <div class="card" style="display:flex;justify-content:space-between;">
        <div>
            <strong><?= htmlspecialchars($patient['full_name']) ?></strong>
            <div>HN: <?= htmlspecialchars($patient['hospital_number']) ?></div>
            <div>Phone: <?= htmlspecialchars($patient['phone']) ?></div>
        </div>
        <a class="btn" href="patients/view_patient.php?id=<?= $patient['id'] ?>">Full Record</a>
    </div>

    <!-- Layout -->
    <div style="display:grid;grid-template-columns:1fr 350px;gap:20px;margin-top:20px;">

        <!-- Consultation Form -->
        <div>
            <div class="card">
                <h4>New Consultation</h4>
                <form method="post">

                    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

                    <label>Main Complaint</label>
                    <textarea name="complaint" class="form-control" required></textarea>

                    <label style="margin-top:10px;">Diagnosis</label>
                    <textarea name="diagnosis_text" class="form-control"></textarea>

                    <label style="margin-top:10px;">Notes</label>
                    <textarea name="notes" class="form-control"></textarea>

                    <div style="display:flex;gap:8px;margin-top:10px;">
                        <input name="temperature" class="form-control" placeholder="Temp °C">
                        <input name="blood_pressure" class="form-control" placeholder="BP">
                        <input name="heart_rate" class="form-control" placeholder="Pulse">
                        <input name="resp_rate" class="form-control" placeholder="Resp">
                    </div>

                    <div style="display:flex;gap:8px;margin-top:10px;">
                        <input name="oxygen_saturation" class="form-control" placeholder="O2 Sat %">
                        <input name="weight" class="form-control" placeholder="Weight kg">
                        <input name="height" class="form-control" placeholder="Height cm">
                    </div>

                    <label style="margin-top:10px;">Lab Requests (one per line)</label>
                    <textarea name="lab_tests_text" class="form-control"></textarea>

                    <button class="btn" style="margin-top:15px;">Save Consultation</button>
                </form>
            </div>

            <!-- History -->
            <div class="card" style="margin-top:20px;">
                <h4>Diagnosis History</h4>
                <?php if (!$diagnoses): ?>
                    <div class="muted">None recorded.</div>
                <?php else: foreach ($diagnoses as $d): ?>
                    <div style="padding:10px;border-bottom:1px solid #eef;">
                        <strong><?= htmlspecialchars($d['complaints']) ?></strong>
                        <div><?= nl2br(htmlspecialchars($d['diagnosis_text'])) ?></div>
                        <small>By <?= htmlspecialchars($d['doctor_name']) ?> — <?= $d['created_at'] ?></small>
                    </div>
                <?php endforeach; endif; ?>
            </div>

        </div>

        <!-- Right Column: vitals + labs -->
        <div>

            <div class="card">
                <h4>Vitals History</h4>
                <?php foreach ($vitals as $v): ?>
                    <div style="padding:10px;border-bottom:1px solid #eef;">
                        Temp: <?= $v['temperature'] ?>°C<br>
                        BP: <?= $v['blood_pressure'] ?><br>
                        Pulse: <?= $v['heart_rate'] ?><br>
                        <small><?= $v['created_at'] ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card" style="margin-top:20px;">
                <h4>Lab Requests</h4>
                <?php foreach ($labs as $l): ?>
                    <div style="padding:10px;border-bottom:1px solid #eef;">
                        <?= htmlspecialchars($l['tests']) ?><br>
                        <small><?= $l['created_at'] ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
