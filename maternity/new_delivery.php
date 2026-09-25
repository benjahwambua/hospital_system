<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin','doctor','nurse']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Invalid security token.');
    }
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $type = trim((string)($_POST['type'] ?? ''));
    $comp = trim((string)($_POST['complications'] ?? ''));
    $weight = (float)($_POST['baby_weight'] ?? 0);

    if ($patient_id <= 0 || $type === '' || $weight <= 0) {
        $error = 'Patient, delivery type and valid baby weight are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO maternity_deliveries(patient_id, delivery_date, type, complications, baby_weight) VALUES (?, NOW(), ?, ?, ?)");
        $stmt->bind_param("issd", $patient_id, $type, $comp, $weight);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: delivery_records.php?success=1");
            exit;
        }
        $error = 'Unable to save delivery record.';
        $stmt->close();
    }
}
$patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name ASC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header"><h1>Record Delivery</h1></div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <label>Patient</label>
    <select name="patient_id" required><option value="">Select Patient</option>
        <?php while ($p = $patients->fetch_assoc()): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option><?php endwhile; ?>
    </select>
    <label>Delivery Type</label>
    <select name="type" required><option>Normal</option><option>Cesarean Section</option><option>Assisted Delivery</option></select>
    <label>Baby Weight (kg)</label><input type="number" step="0.01" min="0.01" name="baby_weight" required>
    <label>Complications</label><textarea name="complications"></textarea>
    <button class="btn btn-primary">Save Delivery Record</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>