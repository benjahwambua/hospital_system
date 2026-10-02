<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_module_access($conn, 'finance_admin', 'view');
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
<style>
.recon-page{background:#f5f7fb;min-height:calc(100vh - 70px);padding:28px 0 48px}
.recon-hero{background:linear-gradient(135deg,#0f4c81 0%,#1769aa 55%,#2384c6 100%);color:#fff;border-radius:18px;padding:26px 28px;margin-bottom:22px;box-shadow:0 12px 30px rgba(15,76,129,.16)}
.recon-hero .eyebrow{font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;font-weight:700;opacity:.8;margin-bottom:5px}
.recon-hero h2{font-weight:800;margin:0 0 6px;font-size:1.65rem}
.recon-hero p{margin:0;opacity:.86}
.recon-hero .btn{border-color:rgba(255,255,255,.5);color:#fff;background:rgba(255,255,255,.08);border-radius:9px;font-weight:600}
.recon-alert{border:0;border-radius:12px;padding:14px 17px;box-shadow:0 5px 18px rgba(0,0,0,.05)}
.recon-stat{height:100%;background:#fff;border:1px solid #e7ebf0;border-radius:14px;padding:19px 20px;box-shadow:0 5px 18px rgba(20,40,70,.06);position:relative;overflow:hidden}
.recon-stat:before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:#1769aa}
.recon-stat small{display:block;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.recon-stat .value{font-size:1.7rem;font-weight:800;color:#172033;margin-top:5px}
.recon-card{background:#fff;border:1px solid #e5e9ef;border-radius:15px;box-shadow:0 6px 22px rgba(20,40,70,.06);overflow:hidden}
.recon-card .card-header{background:#fff;border-bottom:1px solid #edf0f4;padding:17px 20px}
.recon-card .card-header h6{font-size:.95rem;margin:0}
.recon-card .card-body{padding:0}
.recon-table{margin:0}
.recon-table thead th{background:#f8fafc;color:#596579;border:0;border-bottom:1px solid #e7ebf0;padding:13px 16px;font-size:.76rem;text-transform:uppercase;letter-spacing:.04em}
.recon-table tbody td{padding:14px 16px;border-color:#edf0f4;vertical-align:middle;color:#374151}
.recon-table tbody tr:hover{background:#fafcff}
.recon-table a{font-weight:700;color:#1769aa}
.recon-empty{padding:34px!important;color:#7b8494!important}
.recon-balance-title{color:#b42318!important}
@media(max-width:767px){.recon-page{padding:18px 0 35px}.recon-hero{padding:21px;border-radius:14px}.recon-hero h2{font-size:1.35rem}.recon-hero .btn{margin-top:14px}.recon-table{min-width:680px}.recon-card .card-body{overflow-x:auto}}
</style><div class="main-content"><div class="container-fluid recon-page">
<div class="recon-hero"><div class="d-flex justify-content-between align-items-center flex-wrap"><div><div class="eyebrow">Finance &amp; Controls</div><h2><i class="fas fa-balance-scale mr-2"></i>Financial Reconciliation</h2><p>Review invoice totals, payments and accounting references for integrity.</p></div><a href="/hospital_system/accounting/ledger.php" class="btn"><i class="fas fa-book mr-1"></i> Open Ledger</a></div></div>
<div class="alert alert-<?= $healthy?'success':'warning' ?> recon-alert"><i class="fas fa-<?= $healthy?'check-circle':'exclamation-triangle' ?>"></i> <?= $healthy?'No current financial integrity mismatches were detected.':'Exceptions were detected. Review the affected records before closing the accounting period.' ?></div>
<div class="row mb-4">
<div class="col-md-4 mb-3"><div class="recon-stat"><small>Invoice total mismatches</small><div class="value"><?= (int)$summary['invoice_mismatches'] ?></div></div></div>
<div class="col-md-4 mb-3"><div class="recon-stat"><small>Payment balance mismatches</small><div class="value"><?= (int)$summary['payment_mismatches'] ?></div></div></div>
<div class="col-md-4 mb-3"><div class="recon-stat"><small>Unbalanced accounting references</small><div class="value"><?= (int)$summary['unbalanced_refs'] ?></div></div></div>
</div>
<div class="recon-card mb-4"><div class="card-header"><h6 class="font-weight-bold text-primary"><i class="fas fa-file-invoice-dollar mr-2"></i>Invoice Exceptions</h6></div><div class="card-body">
<table class="table table-sm recon-table"><thead><tr><th>Invoice</th><th>Invoice Total</th><th>Items / Payment Check</th><th>Recorded Paid</th></tr></thead><tbody>
<?php foreach($invoiceIssues as $row): ?><tr><td><a href="/hospital_system/billing/view_invoice.php?id=<?= (int)$row['id'] ?>"><?=htmlspecialchars($row['invoice_number']??('#'.$row['id']))?></a></td><td>KSH <?=is_numeric($row['total'])?number_format((float)$row['total'],2):htmlspecialchars($row['total'])?></td><td><?=is_numeric($row['items_total'])?'KSH '.number_format((float)$row['items_total'],2):htmlspecialchars($row['items_total'])?></td><td><?=isset($row['paid_amount'])?'KSH '.number_format((float)$row['paid_amount'],2):'—'?></td></tr><?php endforeach; ?>
<?php if(!$invoiceIssues): ?><tr><td colspan="4" class="text-center recon-empty">No invoice/payment exceptions.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="recon-card"><div class="card-header"><h6 class="font-weight-bold recon-balance-title"><i class="fas fa-exclamation-circle mr-2"></i>Unbalanced Accounting References</h6></div><div class="card-body">
<table class="table table-sm recon-table"><thead><tr><th>Reference</th><th>Debit</th><th>Credit</th><th>Lines</th></tr></thead><tbody>
<?php foreach($accountingIssues as $row): ?><tr><td><?=htmlspecialchars($row['reference_id'])?></td><td>KSH <?=number_format((float)$row['debit'],2)?></td><td>KSH <?=number_format((float)$row['credit'],2)?></td><td><?= (int)$row['lines']?></td></tr><?php endforeach; ?>
<?php if(!$accountingIssues): ?><tr><td colspan="4" class="text-center text-muted">All referenced accounting entries are balanced.</td></tr><?php endif; ?>
</tbody></table></div></div>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>