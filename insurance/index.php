<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'insurance', 'view');

$tablesReady = true;
$required = ['payers','payer_plans','patient_coverages','preauthorizations','claim_headers','invoice_financial_allocations','remittances'];
foreach ($required as $table) {
    $check = $conn->query("SHOW TABLES LIKE '".$conn->real_escape_string($table)."'");
    if (!$check || $check->num_rows === 0) { $tablesReady = false; break; }
}
$stats = ['payers'=>0,'plans'=>0,'verified'=>0,'pending'=>0,'preauth'=>0,'claims'=>0,'open_claim_value'=>0.0];
$recentClaims = [];
if ($tablesReady) {
    foreach ([
        'payers'=>"SELECT COUNT(*) c FROM payers WHERE active=1",
        'plans'=>"SELECT COUNT(*) c FROM payer_plans WHERE active=1",
        'verified'=>"SELECT COUNT(*) c FROM patient_coverages WHERE eligibility_status='Verified'",
        'pending'=>"SELECT COUNT(*) c FROM patient_coverages WHERE eligibility_status='Pending'",
        'preauth'=>"SELECT COUNT(*) c FROM preauthorizations WHERE status IN ('Draft','Submitted','Partially Approved')",
        'claims'=>"SELECT COUNT(*) c FROM claim_headers WHERE claim_status IN ('Draft','Submitted','Under Review','Partially Approved')",
    ] as $k=>$sql) {
        $r=$conn->query($sql); $stats[$k]=(int)($r?($r->fetch_assoc()['c']??0):0);
    }
    $r=$conn->query("SELECT COALESCE(SUM(total_claim_amount-total_paid_amount),0) v FROM claim_headers WHERE claim_status NOT IN ('Paid','Closed','Rejected')");
    if($r) $stats['open_claim_value']=(float)($r->fetch_assoc()['v']??0);
    $r=$conn->query("SELECT ch.id,ch.claim_number,ch.claim_status,ch.total_claim_amount,ch.total_approved_amount,ch.created_at,p.full_name,py.payer_name
        FROM claim_headers ch JOIN patients p ON p.id=ch.patient_id JOIN payers py ON py.id=ch.payer_id
        ORDER BY ch.created_at DESC,ch.id DESC LIMIT 8");
    if($r) while($row=$r->fetch_assoc()) $recentClaims[]=$row;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.ins-page{background:#f4f7fb;min-height:calc(100vh - 70px);padding:28px 0 48px}.ins-shell{max-width:1450px;margin:auto}
.ins-hero{background:linear-gradient(135deg,#062f57,#0a679f 60%,#249ed6);color:#fff;border-radius:20px;padding:28px 30px;margin-bottom:20px;box-shadow:0 16px 34px rgba(6,47,87,.18)}
.ins-hero h1{margin:0 0 6px;font-size:27px;font-weight:800}.ins-hero p{margin:0;color:#d9efff}.ins-actions{margin-top:18px;display:flex;gap:9px;flex-wrap:wrap}.ins-actions a{color:#fff;border:1px solid rgba(255,255,255,.35);background:rgba(255,255,255,.1);border-radius:10px;padding:9px 13px;text-decoration:none;font-weight:700;font-size:13px}
.ins-card{background:#fff;border:1px solid #e3e9f0;border-radius:15px;box-shadow:0 7px 22px rgba(20,40,70,.06);height:100%;padding:19px}.ins-label{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#6b7686;font-weight:800}.ins-value{font-size:25px;font-weight:850;color:#16243a;margin-top:5px}.ins-sub{font-size:12px;color:#7a8493;margin-top:3px}.ins-table{background:#fff;border:1px solid #e3e9f0;border-radius:15px;box-shadow:0 7px 22px rgba(20,40,70,.06);overflow:hidden}.ins-table h3{font-size:16px;font-weight:800;margin:0}.ins-table .head{padding:17px 19px;border-bottom:1px solid #e8edf3}.ins-table table{margin:0}.ins-table th{font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:#6c7787;background:#f8fafc;border:0}.ins-table td,.ins-table th{padding:13px 16px;border-color:#edf1f5;vertical-align:middle}.badge-soft{display:inline-block;padding:5px 9px;border-radius:999px;background:#edf6fc;color:#126ba5;font-size:11px;font-weight:800}.notice{background:#fff8e8;border:1px solid #f4dfad;color:#765b17;border-radius:13px;padding:16px 18px;font-weight:600}
</style>
<div class="main-content"><div class="container-fluid ins-page"><div class="ins-shell">
<section class="ins-hero"><div style="font-size:11px;letter-spacing:.15em;text-transform:uppercase;font-weight:800;opacity:.72">Revenue Cycle • Payer Management</div><h1>Insurance &amp; SHA</h1><p>Manage coverage, eligibility, preauthorizations and claims from one operational workspace.</p>
<div class="ins-actions"><a href="coverage.php"><i class="fas fa-id-card mr-1"></i> Patient Coverage</a><a href="/hospital_system/patients/patient_list.php"><i class="fas fa-user-injured mr-1"></i> Find Patient</a></div></section>
<?php if(!$tablesReady): ?><div class="notice mb-4"><i class="fas fa-info-circle mr-1"></i> Insurance/SHA tables are not installed yet. Run <strong>database/insurance_migration.sql</strong> before using this workspace.</div><?php endif; ?>
<div class="row mb-4">
<?php foreach([['Active Payers','payers','fas fa-building'],['Active Plans','plans','fas fa-layer-group'],['Verified Coverage','verified','fas fa-check-circle'],['Pending Eligibility','pending','fas fa-hourglass-half'],['Open Preauthorizations','preauth','fas fa-file-signature'],['Open Claims','claims','fas fa-file-invoice-dollar']] as $s): ?><div class="col-xl-2 col-md-4 col-sm-6 mb-3"><div class="ins-card"><div class="ins-label"><i class="<?=$s[2]?> mr-1"></i><?=$s[0]?></div><div class="ins-value"><?=number_format($stats[$s[1]])?></div></div></div><?php endforeach; ?>
</div>
<div class="row mb-4"><div class="col-lg-4 mb-3"><div class="ins-card"><div class="ins-label">Outstanding claim value</div><div class="ins-value">KES <?=number_format($stats['open_claim_value'],2)?></div><div class="ins-sub">Claimed but not yet fully settled</div></div></div><div class="col-lg-8 mb-3"><div class="ins-card"><div class="ins-label">Operational chain</div><div class="ins-sub" style="font-size:14px;line-height:1.7;margin-top:8px"><strong>Coverage → Eligibility → Preauthorization → Invoice allocation → Claim → Remittance → Reconciliation</strong></div></div></div></div>
<div class="ins-table"><div class="head d-flex justify-content-between align-items-center"><div><h3>Recent Claims</h3><div class="ins-sub">Latest payer claims requiring operational attention</div></div><span class="badge-soft">Claims workspace</span></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Claim</th><th>Patient</th><th>Payer</th><th>Status</th><th>Claimed</th><th>Approved</th></tr></thead><tbody>
<?php if(!$recentClaims): ?><tr><td colspan="6" class="text-center text-muted py-4">No claims found.</td></tr><?php else: foreach($recentClaims as $c): ?><tr><td><strong><?=htmlspecialchars($c['claim_number'])?></strong></td><td><?=htmlspecialchars($c['full_name'])?></td><td><?=htmlspecialchars($c['payer_name'])?></td><td><span class="badge-soft"><?=htmlspecialchars($c['claim_status'])?></span></td><td>KES <?=number_format((float)$c['total_claim_amount'],2)?></td><td>KES <?=number_format((float)$c['total_approved_amount'],2)?></td></tr><?php endforeach; endif; ?>
</tbody></table></div></div>
</div></div></div>
