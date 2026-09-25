<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin']);

$supplierId=max(0,(int)($_GET['supplier_id']??0));
$from=trim((string)($_GET['from']??date('Y-m-01')));
$to=trim((string)($_GET['to']??date('Y-m-d')));
if(!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$from))$from=date('Y-m-01');
if(!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$to))$to=date('Y-m-d');
if($from>$to){$tmp=$from;$from=$to;$to=$tmp;}

$suppliers=$conn->query("SELECT id,name,phone,email FROM suppliers ORDER BY name ASC");
$supplier=null;$opening=0.0;$transactions=[];$periodPayables=0.0;$periodPayments=0.0;

if($supplierId>0){
 $s=$conn->prepare("SELECT id,name,phone,email FROM suppliers WHERE id=? LIMIT 1");
 $s->bind_param('i',$supplierId);$s->execute();$supplier=$s->get_result()->fetch_assoc();$s->close();
 if($supplier){
  $s=$conn->prepare("SELECT COALESCE(SUM(amount),0) opening FROM supplier_payables WHERE supplier_id=? AND DATE(created_at) < ?");
  $s->bind_param('is',$supplierId,$from);$s->execute();$opening+=(float)($s->get_result()->fetch_assoc()['opening']??0);$s->close();
  $s=$conn->prepare("SELECT COALESCE(SUM(amount),0) opening FROM supplier_payments WHERE supplier_id=? AND DATE(paid_at) < ?");
  $s->bind_param('is',$supplierId,$from);$s->execute();$opening-=(float)($s->get_result()->fetch_assoc()['opening']??0);$s->close();

  $sql="SELECT sp.created_at txn_date,'GRN / Payable' txn_type,
               COALESCE(ir.grn_no,CONCAT('GRN-',sp.receipt_id)) reference_no,
               sp.supplier_invoice_no description,sp.amount debit,0.00 credit
        FROM supplier_payables sp
        LEFT JOIN inventory_receipts ir ON ir.id=sp.receipt_id
        WHERE sp.supplier_id=? AND DATE(sp.created_at) BETWEEN ? AND ?
        UNION ALL
        SELECT spp.paid_at txn_date,'Supplier Payment' txn_type,
               COALESCE(NULLIF(spp.reference,''),CONCAT('PAY-',spp.id)) reference_no,
               CONCAT(spp.payment_method,' payment') description,0.00 debit,spp.amount credit
        FROM supplier_payments spp
        WHERE spp.supplier_id=? AND DATE(spp.paid_at) BETWEEN ? AND ?
        ORDER BY txn_date ASC";
  $s=$conn->prepare($sql);
  $s->bind_param('ississ',$supplierId,$from,$to,$supplierId,$from,$to);
  $s->execute();$res=$s->get_result();
  $running=$opening;
  while($r=$res->fetch_assoc()){
   $running+=(float)$r['debit']-(float)$r['credit'];
   $r['running_balance']=$running;$transactions[]=$r;
   $periodPayables+=(float)$r['debit'];$periodPayments+=(float)$r['credit'];
  }
  $s->close();
 }
}
$closing=$opening+$periodPayables-$periodPayments;

include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<style>
.statement-wrap{max-width:1100px;margin:24px auto}.statement-card{background:#fff;border-radius:14px;box-shadow:0 6px 24px rgba(0,0,0,.08);padding:28px}
.statement-head{display:flex;justify-content:space-between;gap:20px;border-bottom:2px solid #e9ecef;padding-bottom:18px;margin-bottom:20px}
.statement-head h2{margin:0}.summary-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:18px 0}.summary-box{border:1px solid #e5e7eb;border-radius:10px;padding:14px}.summary-box small{display:block;color:#6b7280}.summary-box strong{font-size:1.15rem}
@media(max-width:768px){.summary-grid{grid-template-columns:1fr 1fr}.statement-head{display:block}}
@media print{
 @page{size:A4 portrait;margin:12mm}
 header,footer,nav,aside,.sidebar,.navbar,.no-print{display:none!important}
 html,body,.content{width:100%!important;margin:0!important;padding:0!important;background:#fff!important}
 .statement-wrap{max-width:none!important;margin:0!important}.statement-card{box-shadow:none!important;border:0!important;padding:0!important}
 .summary-grid{grid-template-columns:repeat(4,1fr)}.table{font-size:11px}
}
</style>
<div class="statement-wrap">
 <div class="no-print d-flex justify-content-between align-items-center mb-3">
  <h2 class="h4 mb-0">Supplier Statement</h2>
  <?php if($supplier):?><button onclick="window.print()" class="btn btn-primary btn-sm">Print Statement</button><?php endif;?>
 </div>
 <div class="card shadow mb-3 no-print"><div class="card-body">
  <form method="get" class="form-row align-items-end">
   <div class="col-md-5 mb-2"><label>Supplier</label><select name="supplier_id" class="form-control" required>
    <option value="">Select supplier</option>
    <?php if($suppliers):while($sp=$suppliers->fetch_assoc()):?>
     <option value="<?=$sp['id']?>" <?=$supplierId===(int)$sp['id']?'selected':''?>><?=htmlspecialchars($sp['name'])?></option>
    <?php endwhile;endif;?>
   </select></div>
   <div class="col-md-2 mb-2"><label>From</label><input type="date" name="from" value="<?=htmlspecialchars($from)?>" class="form-control"></div>
   <div class="col-md-2 mb-2"><label>To</label><input type="date" name="to" value="<?=htmlspecialchars($to)?>" class="form-control"></div>
   <div class="col-md-3 mb-2"><button class="btn btn-primary">View Statement</button> <a href="supplier_statement.php" class="btn btn-light">Reset</a></div>
  </form>
 </div></div>

 <?php if($supplier):?>
 <div class="statement-card">
  <div class="statement-head">
   <div><img src="/hospital_system/assets/img/logo.png" alt="Logo" style="max-height:65px" onerror="this.style.display='none'"></div>
   <div style="text-align:right"><h2>Emaqure Medical Centre</h2><div>Biashara Street, Opposite Old Naiwe School, Mlolongo</div><div>+254793069565 · emaquremedicalcentre@gmail.com</div></div>
  </div>
  <div class="statement-head">
   <div><h3>Supplier Statement</h3><div><strong><?=htmlspecialchars($supplier['name'])?></strong></div><div><?=htmlspecialchars($supplier['phone']??'')?></div><div><?=htmlspecialchars($supplier['email']??'')?></div></div>
   <div style="text-align:right"><div><strong>Period:</strong> <?=htmlspecialchars($from)?> to <?=htmlspecialchars($to)?></div><div><strong>Printed:</strong> <?=date('d M Y H:i')?></div></div>
  </div>
  <div class="summary-grid">
   <div class="summary-box"><small>Opening Balance</small><strong>KES <?=number_format($opening,2)?></strong></div>
   <div class="summary-box"><small>Purchases / GRN</small><strong>KES <?=number_format($periodPayables,2)?></strong></div>
   <div class="summary-box"><small>Supplier Payments</small><strong>KES <?=number_format($periodPayments,2)?></strong></div>
   <div class="summary-box"><small>Closing Balance</small><strong>KES <?=number_format($closing,2)?></strong></div>
  </div>
  <div class="table-responsive"><table class="table table-bordered table-sm">
   <thead class="thead-light"><tr><th>Date</th><th>Type</th><th>Reference</th><th>Description</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Balance</th></tr></thead>
   <tbody>
   <?php if($transactions):foreach($transactions as $r):?>
    <tr><td><?=htmlspecialchars(date('d M Y',strtotime($r['txn_date'])))?></td><td><?=htmlspecialchars($r['txn_type'])?></td><td><?=htmlspecialchars($r['reference_no'])?></td><td><?=htmlspecialchars($r['description']??'')?></td><td class="text-right"><?=number_format((float)$r['debit'],2)?></td><td class="text-right"><?=number_format((float)$r['credit'],2)?></td><td class="text-right font-weight-bold"><?=number_format((float)$r['running_balance'],2)?></td></tr>
   <?php endforeach;else:?><tr><td colspan="7" class="text-center text-muted py-4">No transactions for this period.</td></tr><?php endif;?>
   </tbody>
  </table></div>
  <div class="row mt-4">
   <div class="col-md-6"><small>Statement is based on posted GRNs/supplier payables and recorded supplier payments.</small></div>
   <div class="col-md-6 text-right"><strong>Amount Payable: KES <?=number_format($closing,2)?></strong></div>
  </div>
  <div class="row mt-5 pt-4">
   <div class="col-5 text-center"><div style="border-top:1px solid #333;padding-top:6px">Prepared By / Signature</div></div>
   <div class="col-2"></div>
   <div class="col-5 text-center"><div style="height:70px;border:2px dashed #aaa;padding-top:24px">Official Stamp</div></div>
  </div>
 </div>
 <?php elseif($supplierId>0):?><div class="alert alert-warning">Supplier not found.</div>
 <?php endif;?>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
