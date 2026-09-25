<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';

$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); exit('Invalid security token.'); }
  $name=$conn->real_escape_string($_POST['name']); $desc=$conn->real_escape_string($_POST['description']);
  $buy=floatval($_POST['buy_price']); $sell=floatval($_POST['sell_price']); $qty=intval($_POST['quantity']);
  $stmt=$conn->prepare("INSERT INTO medications (name,description,buy_price,sell_price,quantity) VALUES (?,?,?,?,?)");
  $stmt->bind_param("ssddi",$name,$desc,$buy,$sell,$qty); $stmt->execute(); $msg="Medication added"; $stmt->close();
}
?>
<div class="card"><h3>Add Medication</h3>
<?php if($msg) echo "<div class='alert'>$msg</div>"; ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<label>Name</label><input name="name" class="form-control" required>
<label>Description</label><textarea name="description" class="form-control"></textarea>
<label>Buy Price</label><input name="buy_price" class="form-control" type="number" step="0.01">
<label>Sell Price</label><input name="sell_price" class="form-control" type="number" step="0.01">
<label>Quantity</label><input name="quantity" class="form-control" type="number" value="0">
<div style="margin-top:8px"><button class="btn" type="submit">Add</button></div>
</form></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
