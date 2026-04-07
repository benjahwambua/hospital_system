<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("INSERT INTO expenses (amount, category, description, date_incurred) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("dsss", $_POST['amount'], $_POST['category'], $_POST['description'], $_POST['date']);
    $stmt->execute();
    header("Location: add_expense.php?success=1");
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <h1 class="page-title">Record Expense</h1>
    <?php if(isset($_GET['success'])): ?>
        <div style="padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px;">Expense saved!</div>
    <?php endif; ?>

    <div class="card" style="max-width: 500px;">
        <form method="POST">
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Category</label>
                <select name="category" class="form-control" style="width:100%; padding: 8px;">
                    <option>Utilities</option>
                    <option>Supplies</option>
                    <option>Repairs</option>
                    <option>Other</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Amount (KSH)</label>
                <input type="number" name="amount" class="form-control" style="width:100%; padding: 8px;" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Description</label>
                <textarea name="description" class="form-control" style="width:100%; padding: 8px;"></textarea>
            </div>
            <input type="hidden" name="date" value="<?= date('Y-m-d'); ?>">
            <button type="submit" class="btn btn-primary">Save Expense</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>