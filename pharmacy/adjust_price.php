<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

/* Get medicine ID */
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die("Invalid medicine ID.");
}

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selling_price = floatval($_POST['selling_price']);
    if ($selling_price <= 0) {
        $error = "Selling price must be greater than zero.";
    } else {
        $stmt = $conn->prepare("UPDATE pharmacy_stock SET selling_price = ? WHERE id = ?");
        $stmt->bind_param("di", $selling_price, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "Selling price updated successfully.";
        header("Location: view_stock.php");
        exit;
    }
}

/* Fetch medicine details */
$stmt = $conn->prepare("SELECT id, drug_name, selling_price FROM pharmacy_stock WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$medicine = $stmt->get_result()->fetch_assoc();
$stmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="content-area">
    <h2><i class="fa fa-edit"></i> Adjust Selling Price</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="form-card form-compact">
        <label>Medicine Name</label>
        <input type="text" value="<?= htmlspecialchars($medicine['drug_name']) ?>" readonly>

        <label>Current Selling Price (KES)</label>
        <input type="number" step="0.01" value="<?= number_format($medicine['selling_price'], 2) ?>" readonly>

        <label>New Selling Price (KES)</label>
        <input type="number" step="0.01" name="selling_price" required>

        <button class="btn btn-primary">Update Price</button>
        <a href="view_stock.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>