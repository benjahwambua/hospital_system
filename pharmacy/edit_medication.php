<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: /hospital_system/pharmacy/manage_stock.php"); exit;
}

// fetch
$stmt = $conn->prepare("SELECT * FROM medications WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$med = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $desc = $conn->real_escape_string($_POST['description']);
    $buy = floatval($_POST['buy_price']);
    $sell = floatval($_POST['sell_price']);
    $qty = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $stmt = $conn->prepare("UPDATE medications SET name=?, description=?, buy_price=?, sell_price=?, quantity=?, price=? WHERE id=?");
    $stmt->bind_param("ssddidi",$name,$desc,$buy,$sell,$qty,$price,$id);
    $stmt->execute();
    $stmt->close();
    audit('med_update',"id={$id}");
    header("Location: /hospital_system/pharmacy/manage_stock.php");
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main">
  <div class="page-title">Edit Medication</div>
  <div class="card" style="max-width:700px;">
    <form method="post">
      <label>Name</label><input class="form-control" name="name" required value="<?php echo htmlspecialchars($med['name']); ?>">
      <label>Description</label><textarea class="form-control" name="description"><?php echo htmlspecialchars($med['description']); ?></textarea>
      <label>Buy price</label><input class="form-control" name="buy_price" type="number" step="0.01" value="<?php echo htmlspecialchars($med['buy_price']); ?>">
      <label>Sell price</label><input class="form-control" name="sell_price" type="number" step="0.01" value="<?php echo htmlspecialchars($med['sell_price']); ?>">
      <label>Quantity</label><input class="form-control" name="quantity" type="number" value="<?php echo htmlspecialchars($med['quantity']); ?>">
      <label>Price (alt)</label><input class="form-control" name="price" type="number" step="0.01" value="<?php echo htmlspecialchars($med['price']); ?>">
      <div style="margin-top:8px;"><button class="btn" type="submit">Save</button></div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
