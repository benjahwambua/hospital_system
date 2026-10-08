<?php
ob_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'pharmacy', 'edit');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: view_stock.php"); exit; }

$stmt = $conn->prepare("SELECT id, drug_name, quantity FROM pharmacy_stock WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$med = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$med) { http_response_code(404); exit('Medicine not found.'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Invalid security token.';
    } else {
        $change = (int)($_POST['change'] ?? 0);
        $note = trim((string)($_POST['note'] ?? ''));
        if ($change === 0) {
            $error = 'Enter a quantity change greater than zero or less than zero.';
        } else {
            $conn->begin_transaction();
            try {
                $lock = $conn->prepare("SELECT quantity FROM pharmacy_stock WHERE id=? FOR UPDATE");
                $lock->bind_param("i", $id);
                $lock->execute();
                $locked = $lock->get_result()->fetch_assoc();
                $lock->close();
                if (!$locked) throw new Exception('Stock item no longer exists.');

                $currentQty = (int)$locked['quantity'];
                if ($change < 0 && abs($change) > $currentQty) throw new Exception('Insufficient stock.');
                $newQty = $currentQty + $change;
                $movementType = $change > 0 ? 'in' : 'out';
                $uid = (int)($_SESSION['user_id'] ?? 0);

                $update = $conn->prepare("UPDATE pharmacy_stock SET quantity=?, updated_at=NOW() WHERE id=?");
                $update->bind_param("ii", $newQty, $id);
                if (!$update->execute()) throw new Exception('Unable to update stock.');
                $update->close();

                $movement = $conn->prepare("INSERT INTO stock_movements (stock_id,movement_type,quantity_change,balance_after,note,user_id) VALUES (?,?,?,?,?,?)");
                if (!$movement) throw new Exception('Unable to prepare stock movement log.');
                $movement->bind_param("isissi", $id, $movementType, $change, $newQty, $note, $uid);
                if (!$movement->execute()) throw new Exception('Unable to log stock movement.');
                $movement->close();

                if (function_exists('audit')) audit('pharmacy_stock_adjusted', 'stock_id='.$id.',change='.$change.',balance='.$newQty);
                $conn->commit();
                header("Location: view_stock.php");
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('HMS pharmacy stock adjustment error: '.$e->getMessage());
                $error = 'Unable to update stock right now. Please verify the movement details and try again.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-area">
    <h2>Manage Stock</h2>
    <p><strong>Medicine:</strong> <?= htmlspecialchars($med['drug_name']) ?></p>
    <p><strong>Current Quantity:</strong> <?= (int)$med['quantity'] ?></p>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <label>Change Quantity (+ add / − deduct)</label>
        <input type="number" name="change" required class="form-control">
        <label style="margin-top:8px">Reason / Note</label>
        <input type="text" name="note" class="form-control">
        <button type="submit" class="btn btn-primary" style="margin-top:10px">Apply Changes</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ob_end_flush(); ?>