<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_role(['admin','accountant']);

$invoiceIssues=[]; $accountingIssues=[]; $summary=['invoice_items'=>0,'invoice_mismatches'=>0,'payment_mismatches'=>0,'unbalanced_refs'=>0];

$r=$conn->query("SELECT i.id,i.invoice_number,i.total,COALESCE(x.items_total,0) items_total FROM invoices i LEFT JOIN (SELECT invoice_id,SUM(total) items_total FROM invoice_items GROUP BY invoice_id) x ON x.invoice_id=i.id WHERE ABS(COALESCE(i.total,0)-COALESCE(x.items_total,0))>0.01 ORDER BY i.id DESC LIMIT 100");
if($r) while($row=$r->fetch_assoc()){ $invoiceIssues[]=$row; $summary['invoice_mismatches']++; }

$r=$conn->query("SELECT i.id,i.invoice_number,i.total,i.paid_amount,COALESCE(p.net_paid,0) net_paid FROM invoices i LEFT JOIN (SELECT p.invoice_id,SUM(p.amount)-COALESCE(SUM(CASE WHEN r.status='Approved' THEN r.amount ELSE 0 END),0) net_paid FROM payments p LEFT JOIN payment_refunds r ON r.payment_id=p.id GROUP BY p.invoice_id) p ON p.invoice_id=i.id WHERE ABS(COALESCE(i.paid_amount,0)-COALESCE(p.net_paid,0))>0.01 ORDER BY i.id DESC LIMIT 100");
if($r) while($row=$r->fetch_assoc()){ $invoiceIssues[]=['id'=>$row['id'],'invoice_number'=>$row['invoice_number'],'total'=>$row['total'],'items_total'=>'Payment mismatch: '.number_format((float)$row['net_paid'],2),'paid_amount'=>$row['paid_amount']]; $summary['payment_mismatches']++; }

$r=$conn->query("SELECT reference_id,ROUND(SUM(debit),2) debit,ROUND(SUM(credit),2) credit,COUNT(*) lines FROM accounting_entries WHERE reference_id IS NOT NULL AND reference_id<>'' GROUP BY reference_id HAVING ABS(SUM(debit)-SUM(credit))>0.01 ORDER BY MAX(created_at) DESC LIMIT 100");
if($r) while($row=$r->fetch_assoc()){ $accountingIssues[]=$row; $summary['unbalanced_refs']++; }

$healthy=($summary['invoice_mismatches']===0 && $summary['payment_mismatches']===0 && $summary['unbalanced_refs']===0);
include __DIR__.'/../includes/header.php'; include __DIR__.'/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="h3 mb-1 text-gray-800"><i class="fas fa-balance-scale"></i> Financial Reconciliation</h2><p class="text-muted mb-0">Invoice, payment and accounting integrity checks.</p></div><a href="/hospital_system/accounting/ledger.php" class="btn btn-outline-primary">Ledger</a></div>
<div class="alert alert-<?= $healthy?'success':'warning' ?>"><i class="fas fa-<?= $healthy?'check-circle':'exclamation-triangle' ?>"></i> <?= $healthy?'No current financial integrity mismatches were detected.':'Exceptions were detected. Review the affected records before closing the accounting period.' ?></div>
<div class="row mb-4">
<div class="col-md-4"><div class="card shadow"><div class="card-body"><small>Invoice total mismatches</small><div class="h4"><?= (int)$summary['invoice_mismatches'] ?></div></div></div></div>
<div class="col-md-4"><div class="card shadow"><div class="card-body"><small>Payment balance mismatches</small><div class="h4"><?= (int)$summary['payment_mismatches'] ?></div></div></div></div>
<div class="col-md-4"><div class="card shadow"><div class="card-body"><small>Unbalanced accounting references</small><div class="h4"><?= (int)$summary['unbalanced_refs'] ?></div></div></div></div>
</div>
<div class="card shadow mb-4"><div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Invoice Exceptions</h6></div><div class="card-body">
<table class="table table-bordered table-sm"><thead><tr><th>Invoice</th><th>Invoice Total</th><th>Items / Payment Check</th><th>Recorded Paid</th></tr></thead><tbody>
<?php foreach($invoiceIssues as $row): ?><tr><td><a href="/hospital_system/billing/view_invoice.php?id=<?= (int)$row['id'] ?>"><?=htmlspecialchars($row['invoice_number']??('#'.$row['id']))?></a></td><td>KSH <?=is_numeric($row['total'])?number_format((float)$row['total'],2):htmlspecialchars($row['total'])?></td><td><?=is_numeric($row['items_total'])?'KSH '.number_format((float)$row['items_total'],2):htmlspecialchars($row['items_total'])?></td><td><?=isset($row['paid_amount'])?'KSH '.number_format((float)$row['paid_amount'],2):'—'?></td></tr><?php endforeach; ?>
<?php if(!$invoiceIssues): ?><tr><td colspan="4" class="text-center text-muted">No invoice/payment exceptions.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="card shadow"><div class="card-header"><h6 class="m-0 font-weight-bold text-danger">Unbalanced Accounting References</h6></div><div class="card-body">
<table class="table table-bordered table-sm"><thead><tr><th>Reference</th><th>Debit</th><th>Credit</th><th>Lines</th></tr></thead><tbody>
<?php foreach($accountingIssues as $row): ?><tr><td><?=htmlspecialchars($row['reference_id'])?></td><td>KSH <?=number_format((float)$row['debit'],2)?></td><td>KSH <?=number_format((float)$row['credit'],2)?></td><td><?= (int)$row['lines']?></td></tr><?php endforeach; ?>
<?php if(!$accountingIssues): ?><tr><td colspan="4" class="text-center text-muted">All referenced accounting entries are balanced.</td></tr><?php endif; ?>
</tbody></table></div></div>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>