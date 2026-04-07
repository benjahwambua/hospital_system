<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$maternity_id = intval($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $item = $conn->real_escape_string($_POST['item']);
  $amount = floatval($_POST['amount']);
  $stmt = $conn->prepare("INSERT INTO maternity_billing (maternity_id, item, amount) VALUES (?,?,?)");
  $stmt->bind_param("isd", $_POST['maternity_id'], $item, $amount);
  $stmt->execute();
  audit('maternity_bill', "maternity_id={$_POST['maternity_id']},item={$item},amount={$amount}");
  header("Location: view.php?id=".$_POST['maternity_id']);
  exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main">
  <div class="page-title">Add Maternity Charge</div>
  <div class="card" style="max-width:700px">
    <form method="post">
      <input type="hidden" name="maternity_id" value="<?= $maternity_id ?>">
      <label>Item</label><input name="item" class="form-control" required>
      <label style="margin-top:8px">Amount</label><input name="amount" type="number" step="0.01" class="form-control" required>
      <div style="margin-top:12px"><button class="btn">Add</button></div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
