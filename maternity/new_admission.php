<?php
require_once "../includes/config.php";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $ward = $_POST['ward'];
    $notes = $_POST['note'];

    $sql = "INSERT INTO maternity_admissions(patient_id, admission_date, ward, note)
            VALUES (?, NOW(), ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $patient_id, $ward, $notes);

    if ($stmt->execute()) {
        header("Location: admissions.php?success=1");
        exit;
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Load patients
$patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name ASC");
?>

<div class="page-header"><h1>New Maternity Admission</h1></div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
    <label>Patient</label>
    <select name="patient_id" required>
        <option value="">Select patient</option>
        <?php while ($p = $patients->fetch_assoc()): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
        <?php endwhile; ?>
    </select>

    <label>Ward</label>
    <select name="ward" required>
        <option value="">Select ward</option>
        <option>Maternity Ward A</option>
        <option>Maternity Ward B</option>
        <option>Labour Ward</option>
    </select>

    <label>Notes</label>
    <textarea name="note"></textarea>

    <button class="btn btn-primary">Save Admission</button>
</form>

<?php require_once "../includes/footer.php"; ?>
