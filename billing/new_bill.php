<?php
require_once __DIR__ . '/../config/config.php'; require_once __DIR__ . '/../includes/session.php'; require_login();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
$patients = $conn->query("SELECT id, full_name FROM patients");
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $patient_id = intval($_POST['patient_id']); $total=floatval($_POST['total']); $inv_no='INV-'.date('YmdHis').'-'.rand(100,999);
  $stmt=$conn->prepare("INSERT INTO invoices (patient_id, invoice_number, total) VALUES (?,?,?)"); $stmt->bind_param("isd",$patient_id,$inv_no,$total); $stmt->execute(); $invoice_id=$stmt->insert_id; $stmt->close();
  // simple accounting
  $conn->query("INSERT INTO accounting_entries (invoice_id, account, debit, credit, note) VALUES ($invoice_id,'Accounts Receivable',$total,0,'Invoice $inv_no')");
  $conn->query("INSERT INTO accounting_entries (invoice_id, account, debit, credit, note) VALUES ($invoice_id,'Sales',0,$total,'Invoice $inv_no')");
  header("Location: /hospital_system/billing/view_bills.php"); exit;
}
?>
<div class="card"><h3>New Bill</h3>
<form method="post">
<label>Patient</label><select name="patient_id" class="form-control"><?php while($p=$patients->fetch_assoc()): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['full_name'])?></option><?php endwhile; ?></select>
<label>Total</label><input name="total" type="number" step="0.01" class="form-control">
<div style="margin-top:8px"><button class="btn" type="submit">Create Invoice</button></div>
</form></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
