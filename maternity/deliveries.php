<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$success = false;
$error = '';
$printMode = isset($_GET['print']) && $_GET['print'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $postedToken)) {
        $error = 'Security token mismatch. Please refresh and try again.';
    } else {
        $conn->begin_transaction();
        try {
            $maternityId = max(0, (int)($_POST['maternity_id'] ?? 0));
            $deliveryMode = trim((string)($_POST['delivery_mode'] ?? 'SVD'));
            $deliveryTime = trim((string)($_POST['delivery_time'] ?? ''));
            $primaryDoctor = trim((string)($_POST['primary_doctor'] ?? ''));
            $motherCondition = trim((string)($_POST['mother_condition'] ?? ''));
            $complications = trim((string)($_POST['complications'] ?? ''));
            $bloodLoss = trim((string)($_POST['blood_loss'] ?? ''));
            $notes = trim((string)($_POST['notes'] ?? ''));

            $babyNumber = trim((string)($_POST['baby_number'] ?? '1'));
            $babyWeight = trim((string)($_POST['baby_weight'] ?? ''));
            $apgarValue = trim((string)($_POST['apgar'] ?? ''));
            $resuscitation = trim((string)($_POST['resuscitation'] ?? 'None'));
            $gender = trim((string)($_POST['gender'] ?? 'Male'));
            $alive = max(0, min(1, (int)($_POST['alive'] ?? 1)));

            if ($maternityId <= 0) {
                throw new RuntimeException('A valid maternity patient is required.');
            }
            if ($deliveryTime === '') {
                throw new RuntimeException('Delivery date/time is required.');
            }

            $stmt = $conn->prepare('INSERT INTO maternity_delivery (maternity_id, delivery_time, delivery_mode, primary_doctor, mother_condition, complications, blood_loss, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->bind_param('isssssss', $maternityId, $deliveryTime, $deliveryMode, $primaryDoctor, $motherCondition, $complications, $bloodLoss, $notes);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare('INSERT INTO maternity_baby (maternity_id, baby_number, gender, weight, apgar, resuscitation, notes, created_at, alive) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)');
            $stmt->bind_param('issssssi', $maternityId, $babyNumber, $gender, $babyWeight, $apgarValue, $resuscitation, $notes, $alive);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            header('Location: deliveries.php?saved=1');
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $error = 'System Error: ' . $e->getMessage();
        }
    }
}

$matList = $conn->query('SELECT m.id, p.full_name, m.anc_number FROM maternity m JOIN patients p ON p.id = m.patient_id ORDER BY p.full_name ASC');
$deliveries = $conn->query("SELECT d.*, b.weight AS baby_weight, b.apgar, b.gender, b.alive, b.resuscitation, b.baby_number, p.full_name, m.anc_number FROM maternity_delivery d JOIN maternity m ON m.id = d.maternity_id JOIN patients p ON p.id = m.patient_id LEFT JOIN maternity_baby b ON b.maternity_id = m.id AND b.created_at >= d.created_at ORDER BY d.created_at DESC");
$stats = $conn->query("SELECT COUNT(*) AS total, SUM(CASE WHEN delivery_mode='CS' THEN 1 ELSE 0 END) AS cs_count, (SELECT COUNT(*) FROM maternity_baby WHERE alive=1) AS live_births FROM maternity_delivery")->fetch_assoc();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
:root{--primary:#2c3e50;--success:#27ae60;--info:#3498db;--danger:#e74c3c;--bg:#f4f7f6}.content-wrapper{padding:30px;background:var(--bg);min-height:100vh}.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:24px}.stat-card{background:#fff;padding:20px;border-radius:12px;border-left:5px solid var(--info);box-shadow:0 4px 6px rgba(0,0,0,.02)}.stat-card h3{margin:0;font-size:24px;color:var(--primary)}.stat-card p{margin:5px 0 0;color:#7f8c8d;font-size:13px;text-transform:uppercase;font-weight:600}.glass-card{background:#fff;border-radius:15px;border:1px solid #e0e6ed;box-shadow:0 10px 25px rgba(0,0,0,.05);overflow:hidden;margin-bottom:30px}.card-header{background:#fff;padding:20px 25px;border-bottom:1px solid #f0f2f5;display:flex;align-items:center;justify-content:space-between;gap:10px}.card-body{padding:25px}.form-label{display:block;font-size:12px;font-weight:700;color:#5a67d8;text-transform:uppercase;margin-bottom:8px}.form-input{width:100%;padding:12px;border:1px solid #d1d9e0;border-radius:8px}.custom-table{width:100%;border-collapse:collapse}.custom-table th{background:#f8fafc;padding:15px;text-align:left;font-size:12px;text-transform:uppercase;color:#64748b;border-bottom:2px solid #edf2f7}.custom-table td{padding:15px;border-bottom:1px solid #edf2f7;font-size:14px;vertical-align:middle}.badge{padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;display:inline-block}.badge-success{background:#dcfce7;color:#166534}.badge-danger{background:#fee2e2;color:#991b1b}.badge-info{background:#e0f2fe;color:#075985}.btn-submit{background:var(--success);color:#fff;border:none;padding:12px 30px;border-radius:8px;font-weight:700;cursor:pointer;width:100%}.top-actions{display:flex;justify-content:flex-end;gap:10px;margin-bottom:12px}.btn-print{background:#343a40;color:#fff;text-decoration:none;border:none;padding:8px 12px;border-radius:6px;cursor:pointer}
@media print{.top-actions,.card-header input,.btn-submit,form{display:none!important}.content-wrapper{padding:0;background:#fff}}
</style>
<div class="content-wrapper">
<div class="top-actions"><a class="btn-print" href="?print=1" target="_blank" rel="noopener">🖨️ Print Delivery Report</a><a class="btn-print" href="/hospital_system/maternity/maternity_visit.php" style="background:#007bff;">Open Maternity Visits</a></div>
<div class="stats-grid"><div class="stat-card"><h3><?= (int)($stats['total'] ?? 0) ?></h3><p>Total Deliveries</p></div><div class="stat-card" style="border-left-color:var(--success)"><h3><?= (int)($stats['live_births'] ?? 0) ?></h3><p>Successful Live Births</p></div><div class="stat-card" style="border-left-color:var(--danger)"><h3><?= (int)($stats['cs_count'] ?? 0) ?></h3><p>C-Section Count</p></div></div>
<?php if (isset($_GET['saved'])): ?><div style="background:var(--success);color:#fff;padding:15px;border-radius:10px;margin-bottom:20px;">🎉 Delivery record has been successfully cataloged.</div><?php endif; ?>
<?php if ($error): ?><div style="background:var(--danger);color:#fff;padding:15px;border-radius:10px;margin-bottom:20px;">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="glass-card"><div class="card-header"><h4>🆕 Record New Delivery</h4></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><div class="row" style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:15px;"><div style="flex:2;min-width:300px;"><label class="form-label">Patient (ANC Record)</label><select name="maternity_id" class="form-input" required><option value="">Search patient...</option><?php if($matList): while($m=$matList->fetch_assoc()): ?><option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?> (ANC: <?= htmlspecialchars($m['anc_number']) ?>)</option><?php endwhile; endif; ?></select></div><div style="flex:1;"><label class="form-label">Delivery Mode</label><select name="delivery_mode" class="form-input"><option value="SVD">Normal (SVD)</option><option value="CS">Cesarean Section (C-S)</option><option value="Vacuum">Vacuum Extraction</option></select></div><div style="flex:1;"><label class="form-label">Date/Time of Birth</label><input type="datetime-local" name="delivery_time" class="form-input" required value="<?= date('Y-m-d\TH:i') ?>"></div><div style="flex:1;"><label class="form-label">Primary Doctor</label><input type="text" name="primary_doctor" class="form-input" placeholder="Name of Doctor"></div></div><div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px;background:#f8fafc;padding:15px;border-radius:10px;"><div><label class="form-label">Mother Condition</label><input type="text" name="mother_condition" class="form-input" placeholder="e.g., Stable"></div><div><label class="form-label">Complications</label><input type="text" name="complications" class="form-input" placeholder="e.g., None or PPH"></div><div><label class="form-label">Blood Loss (ml)</label><input type="text" name="blood_loss" class="form-input" placeholder="e.g., 250ml"></div></div><div style="background:#fcfdff;border:1px solid #eef2f7;padding:20px;border-radius:12px;margin-top:10px;"><label class="form-label" style="color:var(--info)">👶 Neonatal Information</label><div class="row" style="display:flex;gap:15px;flex-wrap:wrap;"><div style="flex:1;min-width:100px;"><label class="form-label">Baby No.</label><input name="baby_number" type="text" class="form-input" placeholder="1"></div><div style="flex:1;min-width:120px;"><label class="form-label">Weight (kg)</label><input name="baby_weight" step="0.01" type="number" class="form-input" placeholder="0.00"></div><div style="flex:1;min-width:120px;"><label class="form-label">APGAR Score</label><input name="apgar" type="text" class="form-input" placeholder="e.g. 9/10"></div><div style="flex:1;min-width:150px;"><label class="form-label">Resuscitation</label><select name="resuscitation" class="form-input"><option value="None">None</option><option value="Oxygen">Oxygen</option><option value="Bag & Mask">Bag & Mask</option><option value="Drugs">Drugs</option></select></div><div style="flex:1;min-width:120px;"><label class="form-label">Gender</label><select name="gender" class="form-input"><option>Male</option><option>Female</option></select></div><div style="flex:1;min-width:120px;"><label class="form-label">Status</label><select name="alive" class="form-input"><option value="1">Live Birth</option><option value="0">Stillbirth</option></select></div></div></div><div style="margin-top:20px;"><label class="form-label">Clinical Observations</label><textarea name="notes" class="form-input" rows="3" placeholder="Notes on labor progress, complications, or immediate postpartum care..."></textarea></div><button type="submit" class="btn-submit">Complete Delivery Entry</button></form></div></div>
<div class="glass-card"><div class="card-header"><h4>📜 Delivery Registry</h4><input type="text" id="tableSearch" placeholder="Filter records..." style="padding:8px;border-radius:6px;border:1px solid #ddd;font-size:13px;"></div><div style="overflow-x:auto;"><table class="custom-table" id="deliveryTable"><thead><tr><th>Date/Time</th><th>Mother</th><th>Mode/Doctor</th><th>Baby (kg)</th><th>APGAR/Resusc</th><th>Maternal State</th><th>Outcome</th><th>Blood Loss</th></tr></thead><tbody><?php if($deliveries && $deliveries->num_rows>0): while($row=$deliveries->fetch_assoc()): ?><tr><td style="font-weight:600;color:var(--primary)"><?= date('d M Y, H:i', strtotime($row['delivery_time'])) ?></td><td><div style="font-weight:700;"><?= htmlspecialchars($row['full_name']) ?></div><small style="color:#94a3b8;">ANC: <?= htmlspecialchars($row['anc_number']) ?></small></td><td><span class="badge badge-info"><?= htmlspecialchars($row['delivery_mode']) ?></span><br><small style="color:#64748b;">Dr. <?= htmlspecialchars($row['primary_doctor']) ?></small></td><td><strong><?= htmlspecialchars((string)$row['baby_weight']) ?></strong> <small>(#<?= htmlspecialchars((string)$row['baby_number']) ?>)</small></td><td><span style="color:#64748b;"><?= htmlspecialchars((string)$row['apgar']) ?></span><br><small><?= htmlspecialchars($row['resuscitation'] ?? 'None') ?></small></td><td><small>Cond: <?= htmlspecialchars($row['mother_condition']) ?></small><br><small style="color:var(--danger);">Comp: <?= htmlspecialchars($row['complications']) ?></small></td><td><span class="badge <?= !empty($row['alive']) ? 'badge-success' : 'badge-danger' ?>"><?= !empty($row['alive']) ? 'Live Birth' : 'Stillbirth' ?></span></td><td><small><?= htmlspecialchars($row['blood_loss']) ?></small></td></tr><?php endwhile; else: ?><tr><td colspan="8" style="text-align:center;padding:30px;color:#94a3b8;">No delivery records found.</td></tr><?php endif; ?></tbody></table></div></div>
</div>
<script>
document.getElementById('tableSearch').addEventListener('keyup',function(){let val=this.value.toLowerCase();document.querySelectorAll('#deliveryTable tbody tr').forEach(row=>{row.style.display=row.innerText.toLowerCase().includes(val)?'':'none';});});
<?php if ($printMode): ?>window.print();<?php endif; ?>
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
