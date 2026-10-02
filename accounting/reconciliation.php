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
.recon-page{background:radial-gradient(circle at 8% 0%,rgba(35,132,198,.08),transparent 30%),#f4f7fb;min-height:calc(100vh - 70px);padding:30px 0 52px}
.recon-shell{max-width:1450px;margin:auto}
.recon-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#082f55 0%,#0d5f91 48%,#2196d2 100%);color:#fff;border-radius:22px;padding:30px 32px;margin-bottom:20px;box-shadow:0 18px 40px rgba(8,47,85,.2)}
.recon-hero:after{content:"";position:absolute;width:230px;height:230px;border:1px solid rgba(255,255,255,.13);border-radius:50%;right:-70px;top:-95px;box-shadow:0 0 0 30px rgba(255,255,255,.025),0 0 0 60px rgba(255,255,255,.018)}
.recon-hero .eyebrow{font-size:.7rem;text-transform:uppercase;letter-spacing:.16em;font-weight:800;opacity:.72;margin-bottom:7px}
.recon-hero h2{font-weight:800;margin:0 0 7px;font-size:1.8rem;letter-spacing:-.02em}
.recon-hero p{margin:0;opacity:.82;font-size:.93rem}
.recon-hero .btn{position:relative;z-index:1;border:1px solid rgba(255,255,255,.35);color:#fff;background:rgba(255,255,255,.1);border-radius:11px;font-weight:700;padding:10px 15px;backdrop-filter:blur(8px)}
.recon-alert{border:0;border-radius:14px;padding:15px 18px;box-shadow:0 7px 20px rgba(20,40,70,.06);font-weight:600}
.recon-stats{margin-bottom:20px}
.recon-stat{height:100%;background:#fff;border:1px solid #e4eaf1;border-radius:16px;padding:20px 21px;box-shadow:0 7px 22px rgba(20,40,70,.06);position:relative;overflow:hidden;transition:transform .18s ease,box-shadow .18s ease}
.recon-stat:hover{transform:translateY(-3px);box-shadow:0 13px 28px rgba(20,40,70,.1)}
.recon-stat:before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;background:linear-gradient(#1769aa,#35a7df)}
.recon-stat .stat-icon{width:40px;height:40px;border-radius:12px;background:#edf6fc;color:#1769aa;display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:16px}
.recon-stat small{display:block;color:#697586;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em}
.recon-stat .value{font-size:1.85rem;font-weight:850;color:#152033;margin-top:4px;line-height:1.1}
.recon-card{background:#fff;border:1px solid #e2e8f0;border-radius:17px;box-shadow:0 8px 25px rgba(20,40,70,.065);overflow:hidden;margin-bottom:20px}
.recon-card .card-header{background:linear-gradient(180deg,#fff,#fbfcfe);border-bottom:1px solid #e9eef4;padding:18px 21px}
.recon-card .card-header h6{font-size:.96rem;margin:0;font-weight:800}
.recon-card .card-body{padding:0}
.recon-table{margin:0}
.recon-table thead th{background:#f7f9fc;color:#687386;border:0;border-bottom:1px solid #e4eaf1;padding:13px 17px;font-size:.69rem;text-transform:uppercase;letter-spacing:.07em;font-weight:800}
.recon-table tbody td{padding:15px 17px;border-color:#edf1f5;vertical-align:middle;color:#374151}
.recon-table tbody tr{transition:background .15s ease}
.recon-table tbody tr:hover{background:#f7fbff}
.recon-table a{font-weight:800;color:#126ba5;text-decoration:none}
.recon-table a:hover{text-decoration:underline}
.recon-empty{padding:42px!important;color:#7b8494!important}
.recon-ref{font-weight:800;color:#243247}
.recon-money{font-weight:750;font-variant-numeric:tabular-nums}
.recon-danger{color:#b42318!important}
.recon-success{color:#087443!important}
.recon-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;background:#edf6fc;color:#126ba5;font-size:.72rem;font-weight:800}
@media(max-width:767px){.recon-page{padding:18px 0 35px}.recon-hero{padding:23px 20px;border-radius:16px}.recon-hero h2{font-size:1.4rem}.recon-hero .btn{margin-top:16px}.recon-table{min-width:700px}.recon-card .card-body{overflow-x:auto}}
</style><div class="main-content"><div class="container-fluid recon-page"><div class="recon-shell">
<div class="recon-hero"><div class="d-flex justify-content-between align-items-center flex-wrap"><div><div class="eyebrow">Finance &amp; Controls</div><h2><i class="fas fa-balance-scale mr-2"></i>Financial Reconciliation</h2><p>Review invoice totals, payments and accounting references for integrity.</p></div><a href="/hospital_system/accounting/ledger.php" class="btn"><i class="fas fa-book mr-1"></i> Open Ledger</a></div></div>
<div class="alert alert-<?= $healthy?'success':'warning' ?> recon-alert"><i class="fas fa-<?= $healthy?'check-circle':'exclamation-triangle' ?>"></i> <?= $healthy?'No current financial integrity mismatches were detected.':'Exceptions were detected. Review the affected records before closing the accounting period.' ?></div>
<div class="row recon-stats">
<div class="col-md-4 mb-3"><div class="recon-stat"><div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div><small>Invoice total mismatches</small><div class="value"><?= (int)$summary['invoice_mismatches'] ?></div></div></div>
<div class="col-md-4 mb-3"><div class="recon-stat"><div class="stat-icon"><i class="fas fa-money-check-alt"></i></div><small>Payment balance mismatches</small><div class="value"><?= (int)$summary['payment_mismatches'] ?></div></div></div>
<div class="col-md-4 mb-3"><div class="recon-stat"><div class="stat-icon"><i class="fas fa-scale-unbalanced"></i></div><small>Unbalanced accounting references</small><div class="value"><?= (int)$summary['unbalanced_refs'] ?></div></div></div>
</div>
<div class="recon-card mb-4"><div class="card-header"><h6 class="font-weight-bold text-primary"><i class="fas fa-file-invoice-dollar mr-2"></i>Invoice Exceptions</h6></div><div class="card-body">
<table class="table table-sm recon-table"><thead><tr><th>Invoice</th><th>Invoice Total</th><th>Items / Payment Check</th><th>Recorded Paid</th></tr></thead><tbody>
<?php foreach($invoiceIssues as $row): ?><tr><td><span class="recon-pill"><i class="fas fa-file-invoice"></i><a href="/hospital_system/billing/view_invoice.php?id=<?= (int)$row['id'] ?>"><?=htmlspecialchars($row['invoice_number']??('#'.$row['id']))?></a></span></td><td class="recon-money">KSH <?=is_numeric($row['total'])?number_format((float)$row['total'],2):htmlspecialchars($row['total'])?></td><td class="recon-money"><?=is_numeric($row['items_total'])?'KSH '.number_format((float)$row['items_total'],2):htmlspecialchars($row['items_total'])?></td><td class="recon-money"><?=isset($row['paid_amount'])?'KSH '.number_format((float)$row['paid_amount'],2):'—'?></td></tr><?php endforeach; ?>
<?php if(!$invoiceIssues): ?><tr><td colspan="4" class="text-center recon-empty">No invoice/payment exceptions.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="recon-card"><div class="card-header"><h6 class="font-weight-bold recon-balance-title"><i class="fas fa-exclamation-circle mr-2"></i>Unbalanced Accounting References</h6></div><div class="card-body">
<table class="table table-sm recon-table"><thead><tr><th>Reference</th><th>Debit</th><th>Credit</th><th>Lines</th></tr></thead><tbody>
<?php foreach($accountingIssues as $row): ?><tr><td><span class="recon-ref"><?=htmlspecialchars($row['reference_id'])?></span></td><td class="recon-money recon-danger">KSH <?=number_format((float)$row['debit'],2)?></td><td class="recon-money recon-success">KSH <?=number_format((float)$row['credit'],2)?></td><td><?= (int)$row['lines']?></td></tr><?php endforeach; ?>
<?php if(!$accountingIssues): ?><tr><td colspan="4" class="text-center text-muted">All referenced accounting entries are balanced.</td></tr><?php endif; ?>
</tbody></table></div></div>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>