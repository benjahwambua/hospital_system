<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

// Get patient
$patient_id = intval($_GET['id'] ?? 0);
if ($patient_id <= 0) {
    die("Invalid patient ID");
}

// Fetch patient info
$patient = $conn->query("SELECT id, full_name FROM patients WHERE id=$patient_id")->fetch_assoc();

// Fetch medications
$meds = $conn->query("SELECT id, name, quantity, selling_price FROM medications ORDER BY name");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $med_id = intval($_POST['med_id']);
    $qty = intval($_POST['qty']);
    $notes = $conn->real_escape_string($_POST['notes']);

    // Save prescription
    $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, med_id, qty, notes, prescribed_by) VALUES (?,?,?,?,?)");
    $stmt->bind_param("iiisi", $patient_id, $med_id, $qty, $notes, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();

    $msg = "Prescription saved successfully.";
}
?>

<div class="main-content">
<h3>Prescribe Medicine: <?= htmlspecialchars($patient['full_name']); ?></h3>
<?php if($msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($msg); ?></div>
<?php endif; ?>

<form method="post">
    <label>Medicine</label>
    <select name="med_id" class="form-control" required>
        <?php while($m = $meds->fetch_assoc()): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> - Stock: <?= $m['quantity'] ?> - KES <?= number_format($m['selling_price'],2) ?></option>
        <?php endwhile; ?>
    </select>

    <label>Quantity</label>
    <input type="number" name="qty" min="1" value="1" class="form-control" required>

    <label>Notes</label>
    <textarea name="notes" class="form-control"></textarea>

    <div style="margin-top:10px;">
        <button class="btn btn-primary">Save Prescription</button>
    </div>
</form>
</div>
