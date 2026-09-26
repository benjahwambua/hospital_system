<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_module_access($conn, 'finance_admin', 'create');
require_role(['admin','accountant']);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Invalid security token.');
    }
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $description = trim((string)($_POST['description'] ?? ''));
    $expenseDate = (string)($_POST['expense_date'] ?? date('Y-m-d'));
    $method = trim((string)($_POST['method'] ?? 'Cash'));
    if ($categoryId <= 0 || $amount <= 0) {
        $message = "<div class='alert alert-danger'>A valid category and amount are required.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO expenses (category_id, amount, description, expense_date, payment_method, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $uid = current_user_id();
            $stmt->bind_param("idsssi", $categoryId, $amount, $description, $expenseDate, $method, $uid);
            if ($stmt->execute()) $message = "<div class='alert alert-info'>Expense Logged</div>";
            else $message = "<div class='alert alert-danger'>Unable to log expense.</div>";
            $stmt->close();
        }
    }
}
$cats = $conn->query("SELECT * FROM expense_categories");
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<div class="card p-4">
    <h3>Log General Expense</h3>
    <?= $message ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <select name="category_id" class="form-control mb-2" required>
            <?php while($c = $cats->fetch_assoc()): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
            <?php endwhile; ?>
        </select>
        <input type="number" step="0.01" min="0.01" name="amount" class="form-control mb-2" placeholder="Amount" required>
        <input type="date" name="expense_date" class="form-control mb-2" value="<?= date('Y-m-d') ?>" required>
        <textarea name="description" class="form-control mb-2" placeholder="Description"></textarea>
        <select name="method" class="form-control mb-2"><option>Cash</option><option>Bank Transfer</option><option>M-Pesa</option></select>
        <button class="btn btn-danger">Save Expense</button>
    </form>
</div>