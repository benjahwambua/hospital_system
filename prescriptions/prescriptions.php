<?php
include "../includes/db.php";
include "../includes/header.php";
$patient_id = $_GET['patient_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $drug_name = $_POST['drug_name'];
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $duration = $_POST['duration'];
    $prescribed_by = $_POST['prescribed_by'];

    $stmt = $conn->prepare("INSERT INTO prescriptions 
        (patient_id, drug_name, dosage, frequency, duration, prescribed_by) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $patient_id, $drug_name, $dosage, $frequency, $duration, $prescribed_by);
    $stmt->execute();

    echo "<script>alert('Prescription added'); window.location='view_prescriptions.php?patient_id=$patient_id';</script>";
}
?>

<h2>Add Prescription</h2>

<form method="POST">
    <label>Drug Name</label>
    <input type="text" name="drug_name" required>

    <label>Dosage</label>
    <input type="text" name="dosage" required>

    <label>Frequency</label>
    <input type="text" name="frequency" required>

    <label>Duration</label>
    <input type="text" name="duration" required>

    <label>Prescribed By</label>
    <input type="text" name="prescribed_by" required>

    <button type="submit">Save Prescription</button>
</form>

<?php include "../includes/footer.php"; ?>
