<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();


$enc = $conn->query("
 SELECT e.*, p.full_name
 FROM encounters e
 JOIN patients p ON p.id=e.patient_id
 WHERE e.id=$encounter_id
")->fetch_assoc();

if (!$enc) die("Invalid encounter");
if ($enc['status'] === 'closed') die("Encounter closed");

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $stmt = $conn->prepare("
        INSERT INTO consultations
        (encounter_id, complaints, diagnosis, notes, doctor_id)
        VALUES (?,?,?,?,?)
    ");
    $stmt->bind_param(
        "isssi",
        $encounter_id,
        $_POST['complaints'],
        $_POST['diagnosis'],
        $_POST['notes'],
        $_SESSION['user_id']
    );
    $stmt->execute();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main">
<h2>Consultation — <?= htmlspecialchars($enc['full_name']) ?></h2>

<form method="post" class="card">
<label>Complaints</label>
<textarea name="complaints" required></textarea>

<label>Diagnosis</label>
<textarea name="diagnosis"></textarea>

<label>Notes</label>
<textarea name="notes"></textarea>

<button class="btn">Save Consultation</button>
<a class="btn" href="/hospital_system/billing/index.php?encounter_id=<?= $encounter_id ?>">Proceed to Billing</a>
</form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
