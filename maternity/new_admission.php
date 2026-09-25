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
    $ward = trim((string)($_POST['ward'] ?? ''));
    $notes = trim((string)($_POST['note'] ?? ''));

    if ($patient_id <= 0 || $ward === '') {
        $error = 'Patient and ward are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO maternity_admissions(patient_id, admission_date, ward, note) VALUES (?, NOW(), ?, ?)");
        $stmt->bind_param("iss", $patient_id, $ward, $notes);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: admissions.php?success=1");
            exit;
        }
        $error = 'Unable to save admission.';
        $stmt->close();
    }
}
$patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name ASC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header"><h1>New Maternity Admission</h1></div>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <label>Patient</label>
    <select name="patient_id" required><option value="">Select patient</option>
        <?php while ($p = $patients->fetch_assoc()): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option><?php endwhile; ?>
    </select>
    <label>Ward</label>
    <select name="ward" required><option value="">Select ward</option><option>Maternity Ward A</option><option>Maternity Ward B</option><option>Labour Ward</option></select>
    <label>Notes</label><textarea name="note"></textarea>
    <button class="btn btn-primary">Save Admission</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>