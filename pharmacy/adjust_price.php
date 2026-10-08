<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'pharmacy', 'edit');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
$id = (int)($_GET['id'] ?? 0);
$error = '';

if ($id <= 0) {
    http_response_code(400);
    exit('Invalid medicine ID.');
}

$stmt = $conn->prepare("SELECT id, drug_name, selling_price FROM pharmacy_stock WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$medicine = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$medicine) {
    http_response_code(404);
    exit('Medicine not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Invalid security token.';
    } else {
        $sellingPrice = round((float)($_POST['selling_price'] ?? 0), 2);
        if ($sellingPrice <= 0) {
            $error = 'Selling price must be greater than zero.';
        } else {
            $conn->begin_transaction();
            try {
                $lock = $conn->prepare("SELECT selling_price FROM pharmacy_stock WHERE id = ? FOR UPDATE");
                $lock->bind_param("i", $id);
                $lock->execute();
                $locked = $lock->get_result()->fetch_assoc();
                $lock->close();
                if (!$locked) throw new Exception('Medicine no longer exists.');

                $oldPrice = (float)$locked['selling_price'];
                $update = $conn->prepare("UPDATE pharmacy_stock SET selling_price = ?, updated_at = NOW() WHERE id = ?");
                $update->bind_param("di", $sellingPrice, $id);
                if (!$update->execute()) throw new Exception('Unable to update selling price.');
                $update->close();

                if (function_exists('audit')) {
                    audit('pharmacy_price_adjusted', 'stock_id='.$id.',old_price='.number_format($oldPrice,2,'.','').',new_price='.number_format($sellingPrice,2,'.',''));
                }
                $conn->commit();
                $_SESSION['success'] = 'Selling price updated successfully.';
                header("Location: view_stock.php");
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('HMS pharmacy price adjustment error: '.$e->getMessage());
                $error = 'Unable to update the selling price right now.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-area">
    <h2><i class="fa fa-edit"></i> Adjust Selling Price</h2>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="form-card form-compact">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <label>Medicine Name</label>
        <input type="text" value="<?= htmlspecialchars($medicine['drug_name']) ?>" readonly>
        <label>Current Selling Price (KES)</label>
        <input type="number" step="0.01" value="<?= number_format((float)$medicine['selling_price'], 2, '.', '') ?>" readonly>
        <label>New Selling Price (KES)</label>
        <input type="number" step="0.01" min="0.01" name="selling_price" required>
        <button class="btn btn-primary">Update Price</button>
        <a href="view_stock.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>