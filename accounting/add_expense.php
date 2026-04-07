<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("INSERT INTO expenses (category_id, amount, description, expense_date, payment_method, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idsssi", $_POST['category_id'], $_POST['amount'], $_POST['description'], $_POST['expense_date'], $_POST['method'], $_SESSION['user_id']);
    $stmt->execute();
    echo "<div class='alert alert-info'>Expense Logged</div>";
}

$cats = $conn->query("SELECT * FROM expense_categories");
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<div class="card p-4">
    <h3>Log General Expense</h3>
    <form method="POST">
        <select name="category_id" class="form-control mb-2">
            <?php while($c = $cats->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>"><?= $c['category_name'] ?></option>
            <?php endwhile; ?>
        </select>
        <input type="number" step="0.01" name="amount" class="form-control mb-2" placeholder="Amount" required>
        <input type="date" name="expense_date" class="form-control mb-2" value="<?= date('Y-m-d') ?>">
        <textarea name="description" class="form-control mb-2" placeholder="Description"></textarea>
        <select name="method" class="form-control mb-2">
            <option>Cash</option><option>Bank Transfer</option><option>M-Pesa</option>
        </select>
        <button class="btn btn-danger">Save Expense</button>
    </form>
</div>