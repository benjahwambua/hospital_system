<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$patient_id=(int)($_GET['patient_id']??0);
if($patient_id<=0){ http_response_code(400); exit('Invalid patient.'); }
$stmt=$conn->prepare("SELECT i.id,i.invoice_number,i.total,i.paid_amount,i.balance,i.status,i.created_at FROM invoices i WHERE i.patient_id=? ORDER BY i.id DESC");
$stmt->bind_param('i',$patient_id); $stmt->execute(); $res=$stmt->get_result();
echo "<table class='table-blue'><tr><th>Invoice</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Date</th></tr>";
while($row=$res->fetch_assoc()){
 echo "<tr><td>".htmlspecialchars($row['invoice_number']??('#'.$row['id']))."</td><td>".number_format((float)$row['total'],2)."</td><td>".number_format((float)$row['paid_amount'],2)."</td><td>".number_format((float)$row['balance'],2)."</td><td>".htmlspecialchars($row['status'])."</td><td>".htmlspecialchars($row['created_at'])."</td></tr>";
}
echo "</table>"; $stmt->close();