<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_module_access($conn, 'clinical', 'edit');
require_role(['admin','doctor']);

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$doctor_id = $_SESSION['user_id']; // logged-in doctor

//--------------------------------------------
// Load patient
//--------------------------------------------
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$patient = null;

if ($patient_id) {

    $q = $conn->prepare("
        SELECT d.*, u.full_name AS doctor_name
        FROM diagnosis d
        LEFT JOIN users u ON u.id = d.doctor_id
        WHERE d.patient_id = ?
        ORDER BY d.created_at DESC
    ");
    $q->bind_param('i', $patient_id);
    $q->execute();
    $result = $q->get_result();
    while ($r = $result->fetch_assoc()) $diagnoses[] = $r;
    $q->close();

    $q = $conn->prepare("SELECT * FROM vitals WHERE patient_id = ? ORDER BY created_at DESC");
    $q->bind_param('i', $patient_id);
    $q->execute();
    $result = $q->get_result();
    while ($r = $result->fetch_assoc()) $vitals[] = $r;
    $q->close();

    $q = $conn->prepare("SELECT * FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC");
    $q->bind_param('i', $patient_id);
    $q->execute();
    $result = $q->get_result();
    while ($r = $result->fetch_assoc()) $appointments[] = $r;
    $q->close();

    $q = $conn->prepare("SELECT * FROM lab_requests WHERE patient_id = ? ORDER BY created_at DESC");
    $q->bind_param('i', $patient_id);
    $q->execute();
    $result = $q->get_result();
    while ($r = $result->fetch_assoc()) $labs[] = $r;
    $q->close();
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

                    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

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
