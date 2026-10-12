<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'maternity', 'view');

$total=(int)($conn->query("SELECT COUNT(*) c FROM maternity")?->fetch_assoc()['c']??0);
$deliveries=(int)($conn->query("SELECT COUNT(*) c FROM maternity_delivery")?->fetch_assoc()['c']??0);
$babies=(int)($conn->query("SELECT COUNT(*) c FROM maternity_baby")?->fetch_assoc()['c']??0);
$monthlyLabels=[];$monthlyValues=[];
$monthly=$conn->query("SELECT DATE_FORMAT(created_at,'%b %Y') label,DATE_FORMAT(created_at,'%Y-%m') sort_key,COUNT(*) total FROM maternity_delivery GROUP BY sort_key,label ORDER BY sort_key DESC LIMIT 12");
if($monthly){$rows=[];while($row=$monthly->fetch_assoc())$rows[]=$row;foreach(array_reverse($rows) as $row){$monthlyLabels[]=$row['label'];$monthlyValues[]=(int)$row['total'];}}
$modeLabels=[];$modeValues=[];
$modes=$conn->query("SELECT COALESCE(NULLIF(delivery_mode,''),'Unspecified') label,COUNT(*) total FROM maternity_delivery GROUP BY label ORDER BY total DESC LIMIT 8");
if($modes)while($row=$modes->fetch_assoc()){$modeLabels[]=$row['label'];$modeValues[]=(int)$row['total'];}
$recent=$conn->query("SELECT d.*,p.full_name,m.anc_number FROM maternity_delivery d JOIN maternity m ON m.id=d.maternity_id JOIN patients p ON p.id=m.patient_id WHERE p.clinic_category IN ('ANC','PNC','Maternity') ORDER BY d.created_at DESC LIMIT 20");
$e=static fn($value)=>htmlspecialchars((string)($value??''),ENT_QUOTES,'UTF-8');
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.maternity-report{padding:26px 24px 48px;background:#f3f6fb;min-height:calc(100vh - 75px);color:#243247}.maternity-report-wrap{max-width:1500px;margin:auto}.report-hero{background:linear-gradient(125deg,#083d7b,#1264bd 60%,#13a8b8);color:#fff;border-radius:18px;padding:25px 28px;margin-bottom:18px;box-shadow:0 14px 34px rgba(14,72,139,.18);display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}.report-hero small{color:#b9e5ff;text-transform:uppercase;letter-spacing:.15em;font-weight:800}.report-hero h1{font-size:1.7rem;font-weight:800;margin:6px 0}.report-hero p{margin:0;color:#e1efff}.report-hero .btn{background:#fff;color:#0b4e8b;border:0;border-radius:9px;font-weight:800;padding:10px 14px}.report-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:18px}.report-metric,.report-panel{background:#fff;border:1px solid #e1e8f0;border-radius:15px;box-shadow:0 5px 18px rgba(20,40,70,.045);overflow:hidden}.report-metric{padding:18px}.report-metric span{display:block;color:#697586;text-transform:uppercase;font-size:.68rem;letter-spacing:.07em;font-weight:800}.report-metric strong{display:block;color:#172b45;font-size:1.65rem;margin-top:7px}.report-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:18px}.report-head{padding:16px 19px;border-bottom:1px solid #e9eef4;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}.report-head strong{color:#172b45}.report-body{padding:18px}.report-chart{height:280px;position:relative}.report-table-wrap{overflow-x:auto}.report-table{width:100%;border-collapse:collapse}.report-table th,.report-table td{padding:12px 14px;border-bottom:1px solid #edf1f5;text-align:left;vertical-align:middle}.report-table th{font-size:.66rem;text-transform:uppercase;letter-spacing:.06em;color:#68778b;background:#f7f9fc}.report-table td{font-size:.84rem;color:#35465d}.report-table tr:last-child td{border-bottom:0}.report-empty{text-align:center;padding:34px;color:#77869a}@media(max-width:800px){.maternity-report{padding:18px 12px 35px}.report-grid{grid-template-columns:1fr}.report-metrics{grid-template-columns:1fr}.report-hero{padding:22px}.report-hero h1{font-size:1.4rem}}
</style>
<div class="maternity-report"><div class="maternity-report-wrap">
<section class="report-hero"><div><small>Maternal & newborn care · Analytics</small><h1>Maternity Reports</h1><p>Delivery trends, delivery modes and recent delivery records.</p></div><a href="index.php" class="btn"><i class="fas fa-arrow-left mr-1"></i> Maternity Dashboard</a></section>
<section class="report-metrics"><div class="report-metric"><span>Maternity records</span><strong><?=number_format($total)?></strong></div><div class="report-metric"><span>Deliveries recorded</span><strong><?=number_format($deliveries)?></strong></div><div class="report-metric"><span>Newborn records</span><strong><?=number_format($babies)?></strong></div></section>
<section class="report-grid">
<div class="report-panel"><div class="report-head"><strong>Monthly deliveries</strong><span class="text-muted small">Up to 12 months with records</span></div><div class="report-body"><div class="report-chart"><canvas id="monthlyDeliveriesChart" role="img" aria-label="Monthly delivery counts"></canvas></div></div></div>
<div class="report-panel"><div class="report-head"><strong>Delivery mode distribution</strong><span class="text-muted small">Recorded modes</span></div><div class="report-body"><div class="report-chart"><canvas id="deliveryModesChart" role="img" aria-label="Distribution of delivery modes"></canvas></div></div></div>
</section>
<section class="report-panel"><div class="report-head"><strong>Recent deliveries</strong><a class="btn btn-sm btn-outline-primary" href="stats_export_csv.php"><i class="fas fa-file-export mr-1"></i> Export CSV</a></div><div class="report-table-wrap"><table class="report-table"><thead><tr><th>Date</th><th>Patient</th><th>ANC number</th><th>Mode</th><th>Baby weight</th></tr></thead><tbody>
<?php if($recent&&$recent->num_rows):while($r=$recent->fetch_assoc()):?><tr><td><?= $e(date('d M Y H:i',strtotime($r['delivery_time']??$r['created_at']))) ?></td><td><?= $e($r['full_name']) ?></td><td><?= $e($r['anc_number']) ?></td><td><?= $e($r['delivery_mode']??'—') ?></td><td><?= $e($r['baby_weight']??'—') ?></td></tr><?php endwhile;else:?><tr><td colspan="5" class="report-empty">No delivery records are available yet.</td></tr><?php endif;?>
</tbody></table></div></section>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function(){if(typeof Chart==='undefined')return;
const monthlyLabels=<?=json_encode($monthlyLabels,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
const monthlyValues=<?=json_encode($monthlyValues,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
const modeLabels=<?=json_encode($modeLabels,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
const modeValues=<?=json_encode($modeValues,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
const monthlyCanvas=document.getElementById('monthlyDeliveriesChart');
if(monthlyCanvas)new Chart(monthlyCanvas,{type:'bar',data:{labels:monthlyLabels,datasets:[{label:'Deliveries',data:monthlyValues,backgroundColor:'rgba(18,100,189,.82)',borderRadius:6,maxBarThickness:34}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{grid:{display:false}}}}});
const modesCanvas=document.getElementById('deliveryModesChart');
if(modesCanvas)new Chart(modesCanvas,{type:'doughnut',data:{labels:modeLabels,datasets:[{data:modeValues,backgroundColor:['#1264bd','#13a8b8','#4c8ed9','#7cc8cf','#083d7b','#82a6d8','#a8dce0','#c8d9ef'],borderColor:'#fff',borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:12,padding:14}}}}});
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>