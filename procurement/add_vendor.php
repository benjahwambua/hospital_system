<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_module_access($conn, 'procurement', 'create');
require_role(['admin']);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Invalid security token.');
    }
    $name = trim((string)($_POST['name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    if ($name === '') {
        $message = "<div class='alert alert-danger'>Supplier name is required.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO vendors (name, phone, category) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $name, $phone, $category);
            if ($stmt->execute()) $message = "<div class='alert alert-success'>Supplier Registered!</div>";
            else $message = "<div class='alert alert-danger'>Unable to register supplier.</div>";
            $stmt->close();
        }
    }
}
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<div class="card p-4">
    <h3>Register New Supplier</h3>
    <?= $message ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="text" name="name" class="form-control mb-2" placeholder="Supplier Name" required>
        <input type="text" name="phone" class="form-control mb-2" placeholder="Phone">
        <select name="category" class="form-control mb-2">
            <option>Medicines</option><option>Equipment</option><option>General</option>
        </select>
        <button class="btn btn-primary">Save Vendor</button>
    </form>
</div>