<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$id = intval($_GET['id'] ?? 0);
if (!$id) header("Location: /hospital_system/pharmacy/manage_stock.php");

$stmt = $conn->prepare("SELECT id, name, quantity FROM medications WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$med = $stmt->get_result()->fetch_assoc();
$stmt->close();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $change = intval($_POST['change']);
    $type = $_POST['type']; // in / out / adjust
    $note = $conn->real_escape_string($_POST['note'] ?? '');
    $user = $_SESSION['user']['id'] ?? null;

    // compute new balance
    if ($type === 'in') {
        $new = $med['quantity'] + $change;
        $qty_change = $change;
    } elseif ($type === 'out') {
        $new = $med['quantity'] - $change;
        $qty_change = -$change;
    } else { // adjust absolute
        $new = $change;
        $qty_change = $new - $med['quantity'];
    }

    $upd = $conn->prepare("UPDATE medications SET quantity=? WHERE id=?");
    $upd->bind_param("ii",$new,$id);
    $upd->execute();
    $upd->close();

    $ins = $conn->prepare("INSERT INTO stock_movements (med_id, change_type, qty_change, balance_after, note, user_id) VALUES (?,?,?,?,?,?)");
    $ins->bind_param("isissi",$id, $type, $qty_change, $new, $note, $user);
    $ins->execute();
    $ins->close();

    audit('stock_adjust',"med={$id} type={$type} change={$qty_change} new={$new}");
    header("Location: /hospital_system/pharmacy/manage_stock.php");
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main">
  <div class="page-title">Adjust Stock: <?php echo htmlspecialchars($med['name']); ?></div>
  <div class="card" style="max-width:600px;">
    <form method="post">
      <label>Type</label>
      <select name="type" class="form-control">
        <option value="in">Add stock (in)</option>
        <option value="out">Remove stock (out)</option>
        <option value="adjust">Set absolute quantity</option>
      </select>
      <label style="margin-top:8px;">Quantity (for adjust put absolute qty)</label>
      <input class="form-control" name="change" type="number" required value="0">
      <label>Note</label>
      <input class="form-control" name="note">
      <div style="margin-top:8px;"><button class="btn" type="submit">Apply</button></div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
