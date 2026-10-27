<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'maternity', 'view');

$canCreate = can_module_action($conn, 'maternity', 'create');
$canEdit = can_module_action($conn, 'maternity', 'edit');
$e = static fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
$error = '';
$saved = isset($_GET['saved']);
$maternityId = max(0, (int)($_GET['maternity_id'] ?? $_POST['maternity_id'] ?? 0));
$obsId = max(0, (int)($_GET['observation_id'] ?? 0));

$matStmt = $conn->prepare("SELECT m.id,m.patient_id,m.anc_number,m.gravida,m.parity,p.full_name,p.patient_number FROM maternity m JOIN patients p ON p.id=m.patient_id WHERE COALESCE(p.is_walkin,0)=0 AND p.clinic_category IN ('ANC','PNC','Maternity') ORDER BY p.full_name");
$matStmt->execute();
$matResult = $matStmt->get_result();
$matRows = $matResult->fetch_all(MYSQLI_ASSOC);
$matStmt->close();
$selected = null;
foreach ($matRows as $matRow) if ((int)$matRow['id'] === $maternityId) { $selected = $matRow; break; }
if ($maternityId > 0 && !$selected) { http_response_code(404); exit('Registered maternity record not found.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canCreate) { http_response_code(403); exit('You do not have permission to record labour observations.'); }
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Security token mismatch. Refresh the page and try again.';
    } elseif (!$selected) {
        $error = 'Select a registered maternity patient.';
    } else {
        $labourStart = trim((string)($_POST['labour_start'] ?? ''));
        $observedAt = trim((string)($_POST['observed_at'] ?? ''));
        $fhrRaw = trim((string)($_POST['fetal_heart_rate'] ?? ''));
        $liquor = trim((string)($_POST['liquor'] ?? ''));
        $moulding = trim((string)($_POST['moulding'] ?? ''));
        $cervixRaw = trim((string)($_POST['cervical_dilatation'] ?? ''));
        $descent = trim((string)($_POST['head_descent'] ?? ''));
        $contractionsRaw = trim((string)($_POST['contractions_per_10min'] ?? ''));
        $duration = trim((string)($_POST['contraction_duration'] ?? ''));
        $pulseRaw = trim((string)($_POST['pulse'] ?? ''));
        $systolicRaw = trim((string)($_POST['systolic_bp'] ?? ''));
        $diastolicRaw = trim((string)($_POST['diastolic_bp'] ?? ''));
        $temperatureRaw = trim((string)($_POST['temperature'] ?? ''));
        $urineRaw = trim((string)($_POST['urine_volume_ml'] ?? ''));
        $protein = trim((string)($_POST['urine_protein'] ?? ''));
        $ketones = trim((string)($_POST['urine_ketones'] ?? ''));
        $oxytocin = trim((string)($_POST['oxytocin_rate'] ?? ''));
        $medications = trim((string)($_POST['medications'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));
        $startDate = DateTime::createFromFormat('Y-m-d\\TH:i', $labourStart);
        $observedDate = DateTime::createFromFormat('Y-m-d\\TH:i', $observedAt);
        if (!$startDate || !$observedDate) $error = 'Enter a valid labour start time and observation time.';
        elseif ($observedDate < $startDate) $error = 'Observation time cannot be earlier than labour start time.';
        elseif ($fhrRaw !== '' && ((int)$fhrRaw < 30 || (int)$fhrRaw > 240)) $error = 'Fetal heart rate must be between 30 and 240 bpm.';
        elseif ($cervixRaw !== '' && ((float)$cervixRaw < 0 || (float)$cervixRaw > 10)) $error = 'Cervical dilatation must be between 0 and 10 cm.';
        elseif ($contractionsRaw !== '' && ((int)$contractionsRaw < 0 || (int)$contractionsRaw > 10)) $error = 'Contractions must be between 0 and 10 per 10 minutes.';
        elseif ($temperatureRaw !== '' && (!is_numeric($temperatureRaw) || (float)$temperatureRaw < 25 || (float)$temperatureRaw > 45)) $error = 'Check the temperature entered.';
        elseif ($fhrRaw !== '' && filter_var($fhrRaw, FILTER_VALIDATE_INT) === false) $error = 'Fetal heart rate must be a whole number.';
        elseif ($contractionsRaw !== '' && filter_var($contractionsRaw, FILTER_VALIDATE_INT) === false) $error = 'Contractions must be a whole number.';
        elseif ($pulseRaw !== '' && (filter_var($pulseRaw, FILTER_VALIDATE_INT) === false || (int)$pulseRaw < 1 || (int)$pulseRaw > 300)) $error = 'Maternal pulse must be a whole number between 1 and 300 bpm.';
        elseif (($systolicRaw === '') !== ($diastolicRaw === '')) $error = 'Enter both systolic and diastolic blood pressure, or leave both blank.';
        elseif ($systolicRaw !== '' && (filter_var($systolicRaw, FILTER_VALIDATE_INT) === false || (int)$systolicRaw < 40 || (int)$systolicRaw > 300)) $error = 'Systolic blood pressure must be a whole number between 40 and 300.';
        elseif ($diastolicRaw !== '' && (filter_var($diastolicRaw, FILTER_VALIDATE_INT) === false || (int)$diastolicRaw < 20 || (int)$diastolicRaw > 200)) $error = 'Diastolic blood pressure must be a whole number between 20 and 200.';
        elseif ($systolicRaw !== '' && $diastolicRaw !== '' && (int)$systolicRaw <= (int)$diastolicRaw) $error = 'Systolic blood pressure must be greater than diastolic blood pressure.';
        elseif ($urineRaw !== '' && (filter_var($urineRaw, FILTER_VALIDATE_INT) === false || (int)$urineRaw < 0 || (int)$urineRaw > 5000)) $error = 'Urine volume must be a whole number between 0 and 5,000 ml.';
        elseif (strlen($liquor) > 40 || strlen($moulding) > 40 || strlen($descent) > 40 || strlen($duration) > 40 || strlen($protein) > 40 || strlen($ketones) > 40 || strlen($oxytocin) > 80 || strlen($medications) > 255 || strlen($notes) > 65535) $error = 'One or more partograph text fields exceed the permitted length.';
        if ($error === '') {
            $fhr = $fhrRaw === '' ? null : (int)$fhrRaw;
            $cervix = $cervixRaw === '' ? null : (float)$cervixRaw;
            $contractions = $contractionsRaw === '' ? null : (int)$contractionsRaw;
            $pulse = $pulseRaw === '' ? null : (int)$pulseRaw;
            $systolic = $systolicRaw === '' ? null : (int)$systolicRaw;
            $diastolic = $diastolicRaw === '' ? null : (int)$diastolicRaw;
            $temperature = $temperatureRaw === '' ? null : (float)$temperatureRaw;
            $urine = $urineRaw === '' ? null : (int)$urineRaw;
            $uid = (int)($_SESSION['user_id'] ?? 0);
            $sql = "INSERT INTO maternity_partograph_observations (maternity_id,labour_start,observed_at,fetal_heart_rate,liquor,moulding,cervical_dilatation,head_descent,contractions_per_10min,contraction_duration,pulse,systolic_bp,diastolic_bp,temperature,urine_volume_ml,urine_protein,urine_ketones,oxytocin_rate,medications,notes,recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) $error = 'Partograph storage is not installed. Run database/maternity_partograph_migration.sql first.';
            else {
                $startSql = $startDate->format('Y-m-d H:i:s');
                $observedSql = $observedDate->format('Y-m-d H:i:s');
                $stmt->bind_param('ississdsisiiidisssssi', $maternityId, $startSql, $observedSql, $fhr, $liquor, $moulding, $cervix, $descent, $contractions, $duration, $pulse, $systolic, $diastolic, $temperature, $urine, $protein, $ketones, $oxytocin, $medications, $notes, $uid);
                if ($stmt->execute()) {
                    if (function_exists('audit')) audit('maternity_partograph_observation_add', 'maternity_id=' . $maternityId);
                    $stmt->close();
                    header('Location: partograph.php?maternity_id=' . $maternityId . '&saved=1');
                    exit;
                }
                error_log('HMS partograph insert failed: ' . $stmt->error);
                $error = 'Unable to save this observation. Check the database migration and try again.';
                $stmt->close();
            }
        }
    }
}

$observations = [];
if ($selected) {
    $list = $conn->prepare("SELECT o.*,u.full_name AS recorder_name FROM maternity_partograph_observations o LEFT JOIN users u ON u.id=o.recorded_by WHERE o.maternity_id=? ORDER BY o.observed_at ASC,o.id ASC LIMIT 500");
    if ($list) {
        $list->bind_param('i', $maternityId);
        if ($list->execute()) $observations = $list->get_result()->fetch_all(MYSQLI_ASSOC);
        $list->close();
    }
}
$chartLabels = []; $dilatation = []; $fhrValues = []; $contractionValues = [];
foreach ($observations as $o) {
    $elapsedMinutes = max(0, (int)floor((strtotime($o['observed_at']) - strtotime($o['labour_start'])) / 60));
    $chartLabels[] = sprintf('%dh %02dm', intdiv($elapsedMinutes, 60), $elapsedMinutes % 60);
    $dilatation[] = $o['cervical_dilatation'] === null ? null : (float)$o['cervical_dilatation'];
    $fhrValues[] = $o['fetal_heart_rate'] === null ? null : (int)$o['fetal_heart_rate'];
    $contractionValues[] = $o['contractions_per_10min'] === null ? null : (int)$o['contractions_per_10min'];
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.partograph-page{padding:24px;background:#f3f6fb;min-height:calc(100vh - 75px);color:#243247}.partograph-shell{max-width:1550px;margin:auto}.partograph-hero{background:linear-gradient(125deg,#083d7b,#1264bd 60%,#13a8b8);color:#fff;border-radius:18px;padding:25px 28px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:0 14px 34px rgba(14,72,139,.18)}.partograph-hero h1{font-size:1.65rem;font-weight:800;margin:6px 0}.partograph-hero p{margin:0;color:#e1efff}.partograph-kicker{color:#b9e5ff;font-size:.68rem;letter-spacing:.14em;text-transform:uppercase;font-weight:800}.partograph-panel{background:#fff;border:1px solid #e1e8f0;border-radius:15px;box-shadow:0 5px 18px rgba(20,40,70,.045);overflow:hidden;margin-bottom:18px}.partograph-head{padding:15px 19px;border-bottom:1px solid #e9eef4;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}.partograph-body{padding:18px}.partograph-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.partograph-chart{height:310px;position:relative}.partograph-form-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}.partograph-field label{display:block;color:#526176;font-size:.74rem;font-weight:800;margin-bottom:5px}.partograph-field input,.partograph-field select,.partograph-field textarea{width:100%;border:1px solid #d7e0eb;border-radius:8px;padding:9px 10px;font-size:.85rem;background:#fff}.partograph-field textarea{min-height:75px}.partograph-table-wrap{overflow-x:auto}.partograph-table{width:100%;border-collapse:collapse;min-width:1250px}.partograph-table th,.partograph-table td{padding:10px 12px;border-bottom:1px solid #edf1f5;text-align:left;font-size:.78rem;vertical-align:top}.partograph-table th{background:#f7f9fc;color:#68778b;text-transform:uppercase;font-size:.64rem;letter-spacing:.05em}.partograph-note{background:#eff7ff;border:1px solid #cfe6fa;color:#24496a;border-radius:10px;padding:12px 14px;font-size:.82rem}.partograph-empty{text-align:center;padding:32px;color:#7b8798}@media(max-width:1000px){.partograph-grid{grid-template-columns:1fr}.partograph-form-grid{grid-template-columns:repeat(2,minmax(0,1fr)}}@media(max-width:600px){.partograph-page{padding:14px 10px}.partograph-form-grid{grid-template-columns:1fr}.partograph-hero{padding:20px}}
</style>
<div class="partograph-page"><div class="partograph-shell">
<section class="partograph-hero"><div><div class="partograph-kicker">Maternal & newborn care · Labour monitoring</div><h1>Partograph / Partogram</h1><p>Time-based labour observations for a registered maternity encounter.</p></div><a href="<?= $maternityId ? 'view.php?id='.$maternityId : 'index.php' ?>" class="btn btn-light"><i class="fas fa-arrow-left mr-1"></i> Back to Maternity</a></section>
<div class="partograph-note mb-3"><strong>Clinical use:</strong> This chart is a documentation aid, not an automated diagnosis or escalation system. A qualified clinician must interpret findings and follow current facility protocols. Enter observations at the intervals required by your maternity protocol.</div>
<?php if($saved):?><div class="alert alert-success">Labour observation saved.</div><?php endif;?>
<?php if($error):?><div class="alert alert-danger"><?= $e($error) ?></div><?php endif;?>
<section class="partograph-panel"><div class="partograph-head"><strong>Choose maternity encounter</strong><span class="text-muted small">Registered patients only</span></div><div class="partograph-body"><form method="get" class="row align-items-end"><div class="col-lg-8 form-group mb-2"><label for="maternity_id">Patient / ANC record</label><select class="form-control" id="maternity_id" name="maternity_id" required><option value="">Select patient</option><?php foreach($matRows as $mr):?><option value="<?=$mr['id']?>" <?=((int)$mr['id']===$maternityId?'selected':'')?>><?= $e($mr['full_name']) ?> · <?= $e($mr['patient_number']) ?> · ANC <?= $e($mr['anc_number']) ?></option><?php endforeach;?></select></div><div class="col-lg-4 form-group mb-2"><button class="btn btn-primary"><i class="fas fa-chart-line mr-1"></i> Open Partograph</button></div></form></div></section>
<?php if($selected):?>
<section class="partograph-panel"><div class="partograph-head"><strong><?= $e($selected['full_name']) ?> · <?= $e($selected['patient_number']) ?> · ANC <?= $e($selected['anc_number']) ?></strong><span class="text-muted small">Gravida <?= $e($selected['gravida']) ?> / Parity <?= $e($selected['parity']) ?></span></div><div class="partograph-body">
<?php if($canCreate):?><form method="post"><input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="maternity_id" value="<?=$maternityId?>">
<div class="partograph-form-grid">
<div class="partograph-field"><label for="labour_start">Labour start date/time *</label><input type="datetime-local" id="labour_start" name="labour_start" required value="<?= $e($_POST['labour_start'] ?? (count($observations) ? date('Y-m-d\TH:i',strtotime($observations[0]['labour_start'])) : date('Y-m-d\TH:i'))) ?>"></div>
<div class="partograph-field"><label for="observed_at">Observation date/time *</label><input type="datetime-local" id="observed_at" name="observed_at" required value="<?= $e($_POST['observed_at'] ?? date('Y-m-d\TH:i')) ?>"></div>
<div class="partograph-field"><label for="fetal_heart_rate">Fetal heart rate (bpm)</label><input type="number" id="fetal_heart_rate" name="fetal_heart_rate" min="30" max="240" value="<?= $e($_POST['fetal_heart_rate'] ?? '') ?>"></div>
<div class="partograph-field"><label for="liquor">Liquor / membranes</label><select id="liquor" name="liquor"><option value="">Not recorded</option><?php foreach(['Intact','Clear','Meconium stained','Blood stained','Absent'] as $v):?><option <?=((($_POST['liquor']??'')===$v)?'selected':'')?>><?= $e($v) ?></option><?php endforeach;?></select></div>
<div class="partograph-field"><label for="moulding">Moulding</label><select id="moulding" name="moulding"><option value="">Not recorded</option><?php foreach(['0','+','++','+++'] as $v):?><option <?=((($_POST['moulding']??'')===$v)?'selected':'')?>><?= $e($v) ?></option><?php endforeach;?></select></div>
<div class="partograph-field"><label for="cervical_dilatation">Cervical dilatation (cm)</label><input type="number" step="0.5" id="cervical_dilatation" name="cervical_dilatation" min="0" max="10" value="<?= $e($_POST['cervical_dilatation'] ?? '') ?>"></div>
<div class="partograph-field"><label for="head_descent">Head descent</label><input id="head_descent" name="head_descent" placeholder="e.g. fifths palpable" value="<?= $e($_POST['head_descent'] ?? '') ?>"></div>
<div class="partograph-field"><label for="contractions_per_10min">Contractions / 10 min</label><input type="number" id="contractions_per_10min" name="contractions_per_10min" min="0" max="10" value="<?= $e($_POST['contractions_per_10min'] ?? '') ?>"></div>
<div class="partograph-field"><label for="contraction_duration">Contraction duration</label><select id="contraction_duration" name="contraction_duration"><option value="">Not recorded</option><?php foreach(['&lt;20 sec','20–40 sec','&gt;40 sec'] as $v):?><option value="<?= $e(html_entity_decode($v)) ?>" <?=((($_POST['contraction_duration']??'')===html_entity_decode($v))?'selected':'')?>><?= $e(html_entity_decode($v)) ?></option><?php endforeach;?></select></div>
<div class="partograph-field"><label for="pulse">Maternal pulse (bpm)</label><input type="number" id="pulse" name="pulse" min="20" max="250" value="<?= $e($_POST['pulse'] ?? '') ?>"></div>
<div class="partograph-field"><label for="systolic_bp">Systolic BP (mmHg)</label><input type="number" id="systolic_bp" name="systolic_bp" min="40" max="300" value="<?= $e($_POST['systolic_bp'] ?? '') ?>"></div>
<div class="partograph-field"><label for="diastolic_bp">Diastolic BP (mmHg)</label><input type="number" id="diastolic_bp" name="diastolic_bp" min="20" max="200" value="<?= $e($_POST['diastolic_bp'] ?? '') ?>"></div>
<div class="partograph-field"><label for="temperature">Temperature (°C)</label><input type="number" step="0.1" id="temperature" name="temperature" min="25" max="45" value="<?= $e($_POST['temperature'] ?? '') ?>"></div>
<div class="partograph-field"><label for="urine_volume_ml">Urine volume (ml)</label><input type="number" id="urine_volume_ml" name="urine_volume_ml" min="0" max="5000" value="<?= $e($_POST['urine_volume_ml'] ?? '') ?>"></div>
<div class="partograph-field"><label for="urine_protein">Urine protein</label><input id="urine_protein" name="urine_protein" placeholder="e.g. negative, trace, +, ++" value="<?= $e($_POST['urine_protein'] ?? '') ?>"></div>
<div class="partograph-field"><label for="urine_ketones">Urine ketones</label><input id="urine_ketones" name="urine_ketones" placeholder="e.g. negative, trace, +, ++" value="<?= $e($_POST['urine_ketones'] ?? '') ?>"></div>
<div class="partograph-field"><label for="oxytocin_rate">Oxytocin / infusion</label><input id="oxytocin_rate" name="oxytocin_rate" value="<?= $e($_POST['oxytocin_rate'] ?? '') ?>"></div>
<div class="partograph-field"><label for="medications">Medications / interventions</label><input id="medications" name="medications" value="<?= $e($_POST['medications'] ?? '') ?>"></div>
<div class="partograph-field" style="grid-column:1/-1"><label for="notes">Clinical notes / action taken</label><textarea id="notes" name="notes"><?= $e($_POST['notes'] ?? '') ?></textarea></div>
</div><div class="mt-3"><button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Labour Observation</button></div></form><?php else:?><div class="alert alert-warning mb-0">You have view-only access to Maternity.</div><?php endif;?>
</div></section>
<section class="partograph-grid">
<div class="partograph-panel"><div class="partograph-head"><strong>Cervical dilatation over time</strong><span class="small text-muted">Recorded values only</span></div><div class="partograph-body"><div class="partograph-chart"><canvas id="cervicalChart" role="img" aria-label="Cervical dilatation over time"></canvas></div></div></div>
<div class="partograph-panel"><div class="partograph-head"><strong>Fetal heart rate over time</strong><span class="small text-muted">Recorded values only</span></div><div class="partograph-body"><div class="partograph-chart"><canvas id="fhrChart" role="img" aria-label="Fetal heart rate over time"></canvas></div></div></div>
<div class="partograph-panel"><div class="partograph-head"><strong>Contractions per 10 minutes</strong><span class="small text-muted">Recorded values only</span></div><div class="partograph-body"><div class="partograph-chart"><canvas id="contractionsChart" role="img" aria-label="Contractions per 10 minutes over time"></canvas></div></div></div>
</section>
<section class="partograph-panel"><div class="partograph-head"><strong>Labour observation log</strong><span class="text-muted small"><?= count($observations) ?> observations</span></div><div class="partograph-table-wrap"><table class="partograph-table"><thead><tr><th>Observed</th><th>Labour start</th><th>FHR</th><th>Liquor</th><th>Moulding</th><th>Cervix cm</th><th>Head descent</th><th>Contractions</th><th>Duration</th><th>Pulse</th><th>BP</th><th>Temp °C</th><th>Urine ml</th><th>Protein / ketones</th><th>Oxytocin / meds</th><th>Notes</th><th>Recorded by</th></tr></thead><tbody><?php if($observations):foreach(array_reverse($observations) as $o):?><tr><td><?= $e(date('d M Y H:i',strtotime($o['observed_at']))) ?></td><td><?= $e(date('d M H:i',strtotime($o['labour_start']))) ?></td><td><?= $e($o['fetal_heart_rate']??'—') ?></td><td><?= $e($o['liquor']??'—') ?></td><td><?= $e($o['moulding']??'—') ?></td><td><?= $e($o['cervical_dilatation']??'—') ?></td><td><?= $e($o['head_descent']??'—') ?></td><td><?= $e($o['contractions_per_10min']??'—') ?></td><td><?= $e($o['contraction_duration']??'—') ?></td><td><?= $e($o['pulse']??'—') ?></td><td><?= $e(($o['systolic_bp']??'—').' / '.($o['diastolic_bp']??'—')) ?></td><td><?= $e($o['temperature']??'—') ?></td><td><?= $e($o['urine_volume_ml']??'—') ?></td><td><?= $e(($o['urine_protein']??'—').' / '.($o['urine_ketones']??'—')) ?></td><td><?= $e(trim(($o['oxytocin_rate']??'').' '.($o['medications']??'')) ?: '—') ?></td><td><?= $e($o['notes']??'—') ?></td><td><?= $e($o['recorder_name']??'Unknown') ?></td></tr><?php endforeach;else:?><tr><td colspan="17" class="partograph-empty">No labour observations have been recorded for this maternity encounter.</td></tr><?php endif;?></tbody></table></div></section>
<?php endif;?>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function(){if(typeof Chart==='undefined')return;
const labels=<?=json_encode($chartLabels,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
const dilation=<?=json_encode($dilatation,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
const fhr=<?=json_encode($fhrValues,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
const contractions=<?=json_encode($contractionValues,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
const base={responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false}},y:{beginAtZero:true}}};
const c=document.getElementById('cervicalChart');if(c)new Chart(c,{type:'line',data:{labels,datasets:[{label:'Cervical dilatation (cm)',data:dilation,borderColor:'#1264bd',backgroundColor:'rgba(18,100,189,.12)',fill:true,tension:0,spanGaps:false}]},options:{...base,scales:{...base.scales,y:{min:0,max:10,title:{display:true,text:'Dilatation (cm)'},ticks:{stepSize:1}}}}});
const f=document.getElementById('fhrChart');if(f)new Chart(f,{type:'line',data:{labels,datasets:[{label:'FHR (bpm)',data:fhr,borderColor:'#13a8b8',backgroundColor:'rgba(19,168,184,.12)',fill:false,tension:0,spanGaps:false}]},options:{...base,scales:{...base.scales,y:{min:30,max:240,title:{display:true,text:'Beats per minute'}}}}});
const cc=document.getElementById('contractionsChart');if(cc)new Chart(cc,{type:'bar',data:{labels,datasets:[{label:'Contractions / 10 min',data:contractions,backgroundColor:'rgba(8,61,123,.78)',borderRadius:5}]},options:{...base,scales:{...base.scales,y:{min:0,max:10,ticks:{stepSize:1},title:{display:true,text:'Contractions / 10 minutes'}}}}});
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
