<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name ASC");
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    // build invoice number
    $inv_no = 'INV-' . date('YmdHis') . '-' . rand(100,999);
    // start tx
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO invoices (patient_id, invoice_number, total, status) VALUES (?,?,0,'unpaid')");
        $total = 0;
        $stmt->bind_param("is", $patient_id, $inv_no);
        $stmt->execute();
        $invoice_id = $conn->insert_id;
        $stmt->close();

        // items posted as arrays: desc[], qty[], unit[]
        $descs = $_POST['desc'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        $units = $_POST['unit_price'] ?? [];
        for ($i = 0; $i < count($descs); $i++) {
            $d = trim($descs[$i]);
            if ($d === '') continue;
            $q = intval($qtys[$i]);
            $u = floatval($units[$i]);
            $t = $q * $u;
            $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, qty, unit_price, total) VALUES (?,?,?,?,?)");
            $stmt->bind_param("isidd", $invoice_id, $d, $q, $u, $t);
            $stmt->execute();
            $stmt->close();
            $total += $t;
        }
        // update invoice total
        $stmt = $conn->prepare("UPDATE invoices SET total = ? WHERE id = ?");
        $stmt->bind_param("di", $total, $invoice_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $success = "Invoice created.";
        header("Location: /hospital_system/invoices/print_invoice.php?id={$invoice_id}");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $success = "Error: " . $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main">
  <div class="page-title">Create Invoice</div>
  <div class="card" style="max-width:900px">
    <?php if ($success): ?><div class="alert alert-info"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <form method="post">
      <label>Patient</label>
      <select name="patient_id" class="form-control" required>
        <?php while ($p = $patients->fetch_assoc()): ?>
          <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option>
        <?php endwhile; ?>
      </select>

      <div id="items">
        <div class="item-row" style="display:flex;gap:8px;margin-top:8px;">
          <input name="desc[]" class="form-control" placeholder="Description">
          <input name="qty[]" class="form-control" placeholder="Qty" type="number">
          <input name="unit_price[]" class="form-control" placeholder="Unit price" type="number" step="0.01">
        </div>
      </div>

      <div style="margin-top:8px;">
        <button type="button" class="btn btn-secondary" onclick="addItem()">Add another item</button>
      </div>

      <div style="margin-top:8px;">
        <button class="btn" type="submit">Create Invoice</button>
      </div>
    </form>
  </div>
</div>

<script>
function addItem(){
  const container = document.getElementById('items');
  const row = document.createElement('div');
  row.className = 'item-row';
  row.style = 'display:flex;gap:8px;margin-top:8px;';
  row.innerHTML = '<input name="desc[]" class="form-control" placeholder="Description">\
                   <input name="qty[]" class="form-control" placeholder="Qty" type="number">\
                   <input name="unit_price[]" class="form-control" placeholder="Unit price" type="number" step="0.01">\
                   <button onclick="this.parentNode.remove()" type="button" class="btn btn-secondary">Remove</button>';
  container.appendChild(row);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
