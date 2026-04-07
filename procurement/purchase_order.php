<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("INSERT INTO purchase_orders (vendor_id, created_by, status) VALUES (?, ?, 'Pending')");
    $stmt->bind_param("ii", $_POST['vendor_id'], $_SESSION['user_id']);
    $stmt->execute();
    header("Location: purchase_order_items.php?id=" . $conn->insert_id);
    exit;
}

$vendors = $conn->query("SELECT * FROM vendors ORDER BY name");
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<div class="card p-4">
    <h3>New Purchase Order</h3>
    <form method="POST">
        <label>Select Vendor</label>
        <select name="vendor_id" class="form-control mb-3">
            <?php while($v = $vendors->fetch_assoc()): ?>
                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
            <?php endwhile; ?>
        </select>
        <button class="btn btn-primary">Create PO & Add Items</button>
    </form>
</div>