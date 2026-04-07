<?php
require_once "../includes/config.php";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $type = $_POST['type'];
    $comp = $_POST['complications'];
    $weight = $_POST['baby_weight'];

    $sql = "INSERT INTO maternity_deliveries(patient_id, delivery_date, type, complications, baby_weight)
            VALUES (?, NOW(), ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssd", $patient_id, $type, $comp, $weight);

    if ($stmt->execute()) {
        header("Location: delivery_records.php?success=1");
        exit;
    }
}

$patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name ASC");
?>

<div class="page-header"><h1>Record Delivery</h1></div>

<form method="POST">
    <label>Patient</label>
    <select name="patient_id" required>
        <option value="">Select Patient</option>
        <?php while ($p = $patients->fetch_assoc()): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
        <?php endwhile; ?>
    </select>

    <label>Delivery Type</label>
    <select name="type" required>
        <option>Normal</option>
        <option>Cesarean Section</option>
        <option>Assisted Delivery</option>
    </select>

    <label>Baby Weight (kg)</label>
    <input type="number" step="0.01" name="baby_weight" required>

    <label>Complications</label>
    <textarea name="complications"></textarea>

    <button class="btn btn-primary">Save Delivery Record</button>
</form>

<?php require_once "../includes/footer.php"; ?>
