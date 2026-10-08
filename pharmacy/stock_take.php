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

$stmt = $conn->prepare("SELECT id, drug_name, quantity FROM pharmacy_stock WHERE id = ? LIMIT 1");
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
        $quantity = (int)($_POST['quantity'] ?? -1);
        if ($quantity < 0) {
            $error = 'Quantity cannot be negative.';
        } else {
            $conn->begin_transaction();
            try {
                $lock = $conn->prepare("SELECT quantity FROM pharmacy_stock WHERE id = ? FOR UPDATE");
                $lock->bind_param("i", $id);
                $lock->execute();
                $locked = $lock->get_result()->fetch_assoc();
                $lock->close();
                if (!$locked) throw new Exception('Medicine no longer exists.');

                $oldQty = (int)$locked['quantity'];
                $update = $conn->prepare("UPDATE pharmacy_stock SET quantity = ?, updated_at = NOW() WHERE id = ?");
                $update->bind_param("ii", $quantity, $id);
                if (!$update->execute()) throw new Exception('Unable to update stock quantity.');
                $update->close();

                $change = $quantity - $oldQty;
                $movementType = $change >= 0 ? 'in' : 'out';
                $uid = (int)($_SESSION['user_id'] ?? 0);
                $note = 'Stock take adjustment';

                $movement = $conn->prepare("INSERT INTO stock_movements (stock_id, movement_type, quantity_change, balance_after, note, user_id) VALUES (?,?,?,?,?,?)");
                if ($movement) {
                    $movement->bind_param("isissi", $id, $movementType, $change, $quantity, $note, $uid);
                    if (!$movement->execute()) throw new Exception('Unable to log stock take movement.');
                    $movement->close();
                }

                if (function_exists('audit')) {
                    audit('pharmacy_stock_take', 'stock_id='.$id.',old_quantity='.$oldQty.',new_quantity='.$quantity);
                }
                $conn->commit();
                $_SESSION['success'] = 'Stock quantity updated successfully.';
                header("Location: view_stock.php");
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('HMS pharmacy stock take error: '.$e->getMessage());
                $error = 'Unable to update stock quantity right now.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-area">
    <h2><i class="fa fa-boxes"></i> Stock Take</h2>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="form-card form-compact">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <label>Medicine Name</label>
        <input type="text" value="<?= htmlspecialchars($medicine['drug_name']) ?>" readonly>
        <label>Current Quantity</label>
        <input type="number" value="<?= (int)$medicine['quantity'] ?>" readonly>
        <label>New Quantity</label>
        <input type="number" name="quantity" min="0" required>
        <button class="btn btn-primary">Update Quantity</button>
        <a href="view_stock.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>