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
    $quantity = intval($_POST['quantity']);
    if ($quantity < 0) {
        $error = "Quantity cannot be negative.";
    } else {
        $stmt = $conn->prepare("UPDATE pharmacy_stock SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "Stock quantity updated successfully.";
        header("Location: view_stock.php");
        exit;
    }
}

/* Fetch medicine details */
$stmt = $conn->prepare("SELECT id, drug_name, quantity FROM pharmacy_stock WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$medicine = $stmt->get_result()->fetch_assoc();
$stmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="content-area">
    <h2><i class="fa fa-boxes"></i> Stock Take</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="form-card form-compact">
        <label>Medicine Name</label>
        <input type="text" value="<?= htmlspecialchars($medicine['drug_name']) ?>" readonly>

        <label>Current Quantity</label>
        <input type="number" value="<?= (int)$medicine['quantity'] ?>" readonly>

        <label>New Quantity</label>
        <input type="number" name="quantity" required>

        <button class="btn btn-primary">Update Quantity</button>
        <a href="view_stock.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>