<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

$patient_id=(int)($_GET['patient_id']??0);
if($patient_id<=0){ http_response_code(400); exit('Invalid patient.'); }

$stmt=$conn->prepare("SELECT id,total,paid_amount,amount_paid,balance,status,invoice_number,created_at FROM invoices WHERE patient_id=? ORDER BY id DESC");
$stmt->bind_param('i',$patient_id); $stmt->execute(); $invoices=$stmt->get_result();
?>
<table class="table table-sm table-bordered">
<thead><tr><th>Invoice</th><th>Total (KES)</th><th>Paid</th><th>Balance</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php $grand=0; while($inv=$invoices->fetch_assoc()): $grand+=(float)$inv['total']; ?>
<tr><td><a href="/hospital_system/billing/view_invoice.php?id=<?= (int)$inv['id'] ?>"><?=htmlspecialchars($inv['invoice_number']??('#'.$inv['id']))?></a></td>
<td><?=number_format((float)$inv['total'],2)?></td><td><?=number_format((float)($inv['paid_amount']??$inv['amount_paid']??0),2)?></td>
<td><?=number_format((float)($inv['balance']??0),2)?></td><td><?=htmlspecialchars($inv['status']??'')?></td><td><?=htmlspecialchars($inv['created_at']??'')?></td></tr>
<?php endwhile; $stmt->close(); ?>
<tr class="table-primary"><td><strong>Total Invoiced</strong></td><td colspan="5"><strong><?=number_format($grand,2)?></strong></td></tr>
</tbody></table>