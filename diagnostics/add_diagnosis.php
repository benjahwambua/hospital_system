<?php
require_once "../includes/session.php";
require_once "../config/config.php";
require_login();
require_role(['admin','pharmacist']);

include "../includes/header.php";
include "../includes/sidebar.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_SESSION['user_id'];
    $diagnosis = $_POST['diagnosis'];
    $recommendations = $_POST['recommendations'];

    $stmt = $conn->prepare("INSERT INTO diagnostics (patient_id, doctor_id, diagnosis, recommendations) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $patient_id, $doctor_id, $diagnosis, $recommendations);
    $stmt->execute();

    // save to history
    $history = $diagnosis . " | " . $recommendations;

    $stmt2 = $conn->prepare("INSERT INTO patient_history (patient_id, type, details) VALUES (?, 'diagnosis', ?)");
    $stmt2->bind_param("is", $patient_id, $history);
    $stmt2->execute();

    header("Location: view_diagnostics.php?success=1");
    exit();
}

// fetch patients
$patients = $conn->query("SELECT * FROM patients ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Diagnosis</title>
</head>
<body>

<h2>Add Diagnosis</h2>

<form method="POST">
    <label>Patient Name</label><br>
    <select name="patient_id" required>
        <option value="">Select patient</option>
        <?php while($p = $patients->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>"><?= $p['fullname'] ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Diagnosis</label><br>
    <textarea name="diagnosis" required></textarea><br><br>

    <label>Recommendations</label><br>
    <textarea name="recommendations"></textarea><br><br>

    <button type="submit">Save Diagnosis</button>
</form>

</body>
</html>
<?php include __DIR__.'/../includes/footer.php'; ?>