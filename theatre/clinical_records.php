<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'theatre', 'view');

function theatre_records_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$canEdit = can_module_action($conn, 'theatre', 'edit');
$error = '';
$message = '';
$caseId = filter_input(INPUT_GET, 'case_id', FILTER_VALIDATE_INT) ?: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caseId = filter_input(INPUT_POST, 'case_id', FILTER_VALIDATE_INT) ?: 0;
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } elseif (!$canEdit) {
        http_response_code(403);
        exit('Forbidden: theatre edit permission required.');
    } else {
        $action = (string)($_POST['action'] ?? '');
        $caseStmt = $conn->prepare('SELECT id,status FROM theatre_cases WHERE id=? LIMIT 1');
        if (!$caseStmt) {
            $error = 'Theatre case lookup unavailable. Confirm the theatre migration is applied.';
        } else {
            $caseStmt->bind_param('i', $caseId);
            if (!$caseStmt->execute()) {
                $error = 'Unable to verify the selected theatre case.';
            } else {
                $case = $caseStmt->get_result()->fetch_assoc();
                if (!$case) {
                    $error = 'Select an existing theatre case.';
                } elseif ($action === 'observation' && $case['status'] !== 'Recovery') {
                    $error = 'PACU observations can only be recorded while the case is in Recovery.';
                } elseif ($action === 'implant' && !in_array($case['status'], ['In Theatre','Recovery','Completed'], true)) {
                    $error = 'Implant/device records can only be added during theatre, recovery or as a post-case record.';
                } elseif (!in_array($action, ['observation','implant'], true)) {
                    $error = 'Unknown record action.';
                } else {
                    $uid = (int)($_SESSION['user_id'] ?? 0);
                    if ($action === 'observation') {
                        $raw = [];
                        foreach (['heart_rate','respiratory_rate','systolic_bp','diastolic_bp','oxygen_saturation','temperature_c','pain_score'] as $f) $raw[$f] = trim((string)($_POST[$f] ?? ''));
                        $ranges = ['heart_rate'=>[20,250],'respiratory_rate'=>[1,80],'systolic_bp'=>[40,300],'diastolic_bp'=>[20,200],'pain_score'=>[0,10]];
                        foreach ($ranges as $f=>$range) {
                            if ($raw[$f] !== '' && (filter_var($raw[$f], FILTER_VALIDATE_INT) === false || (int)$raw[$f] < $range[0] || (int)$raw[$f] > $range[1])) $error = 'One or more vital-sign values are outside the accepted input range.';
                        }
                        if ($raw['oxygen_saturation'] !== '' && (!is_numeric($raw['oxygen_saturation']) || (float)$raw['oxygen_saturation'] < 0 || (float)$raw['oxygen_saturation'] > 100)) $error = 'Oxygen saturation must be between 0 and 100.';
                        if ($raw['temperature_c'] !== '' && (!is_numeric($raw['temperature_c']) || (float)$raw['temperature_c'] < 25 || (float)$raw['temperature_c'] > 45)) $error = 'Temperature must be between 25 and 45 °C.';
                        $level = (string)($_POST['consciousness_level'] ?? '');
                        if (!in_array($level, ['', 'Alert','Voice','Pain','Unresponsive','Other'], true)) $error = 'Select a valid consciousness level.';
                        $oxygen = trim((string)($_POST['oxygen_support'] ?? ''));
                        $interventions = trim((string)($_POST['interventions'] ?? ''));
                        $notes = trim((string)($_POST['notes'] ?? ''));
                        if (strlen($oxygen)>120 || strlen($interventions)>1000 || strlen($notes)>4000) $error = 'Observation text exceeds the allowed length.';
                        if ($error === '' && !array_filter($raw, fn($v)=>$v!=='') && $level==='' && $oxygen==='' && $interventions==='' && $notes==='') $error = 'Enter at least one observation or clinical note.';
                        if ($error === '') {
                            $hr=$raw['heart_rate']===''?null:(int)$raw['heart_rate']; $rr=$raw['respiratory_rate']===''?null:(int)$raw['respiratory_rate'];
                            $sbp=$raw['systolic_bp']===''?null:(int)$raw['systolic_bp']; $dbp=$raw['diastolic_bp']===''?null:(int)$raw['diastolic_bp'];
                            $spo2=$raw['oxygen_saturation']===''?null:(float)$raw['oxygen_saturation']; $temp=$raw['temperature_c']===''?null:(float)$raw['temperature_c'];
                            $pain=$raw['pain_score']===''?null:(int)$raw['pain_score']; $level=$level===''?null:$level;
                            $stmt=$conn->prepare('INSERT INTO theatre_recovery_observations (case_id,recorded_by,heart_rate,respiratory_rate,systolic_bp,diastolic_bp,oxygen_saturation,temperature_c,pain_score,consciousness_level,oxygen_support,interventions,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
                            if (!$stmt) $error='Recovery record table unavailable. Apply database/theatre_clinical_records_migration.sql.';
                            else {
                                $stmt->bind_param('iiiiiiddissss',$caseId,$uid,$hr,$rr,$sbp,$dbp,$spo2,$temp,$pain,$level,$oxygen,$interventions,$notes);
                                if ($stmt->execute()) { $message='Recovery observation recorded.'; if(function_exists('audit')) audit('theatre_recovery_observation_added','case_id='.$caseId.',observation_id='.$stmt->insert_id); }
                                else { error_log('Theatre recovery observation insert failed: '.$stmt->error); $error='Could not save the observation. Check the migration and retry.'; }
                                $stmt->close();
                            }
                        }
                    } else {
                        $item=trim((string)($_POST['item_name']??'')); $manufacturer=trim((string)($_POST['manufacturer']??'')); $model=trim((string)($_POST['model']??''));
                        $lot=trim((string)($_POST['lot_number']??'')); $serial=trim((string)($_POST['serial_number']??'')); $expiryRaw=trim((string)($_POST['expiry_date']??''));
                        $quantity=filter_var($_POST['quantity']??'1',FILTER_VALIDATE_INT); $site=trim((string)($_POST['implanted_site']??'')); $notes=trim((string)($_POST['notes']??''));
                        $expiry=null;
                        if ($expiryRaw!=='') { $dt=DateTime::createFromFormat('!Y-m-d',$expiryRaw); $de=DateTime::getLastErrors(); if(!$dt||$dt->format('Y-m-d')!==$expiryRaw||($de!==false&&($de['warning_count']||$de['error_count']))) $error='Enter a valid device expiry date.'; else $expiry=$expiryRaw; }
                        if($error===''&&($item===''||strlen($item)>180||strlen($manufacturer)>160||strlen($model)>160||strlen($lot)>120||strlen($serial)>120||strlen($site)>180||strlen($notes)>1000)) $error='Enter a valid device name and keep fields within their limits.';
                        if($error===''&&($quantity===false||$quantity<1||$quantity>32767)) $error='Quantity must be a positive whole number.';
                        if($error===''&&$lot===''&&$serial==='') $error='A lot/batch number or serial number is required for traceability.';
                        if($error==='') {
                            $stmt=$conn->prepare('INSERT INTO theatre_implant_usage (case_id,item_name,manufacturer,model,lot_number,serial_number,expiry_date,quantity,implanted_site,notes,recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                            if(!$stmt) $error='Implant/device table unavailable. Apply database/theatre_clinical_records_migration.sql.';
                            else {
                                $stmt->bind_param('issssssissi',$caseId,$item,$manufacturer,$model,$lot,$serial,$expiry,$quantity,$site,$notes,$uid);
                                if($stmt->execute()) { $message='Implant/device traceability record saved.'; if(function_exists('audit')) audit('theatre_implant_record_added','case_id='.$caseId.',implant_id='.$stmt->insert_id); }
                                else { error_log('Theatre implant insert failed: '.$stmt->error); $error='Could not save the device record. Check the migration and retry.'; }
                                $stmt->close();
                            }
                        }
                    }
                }
            }
            $caseStmt->close();
        }
    }
}

$caseOptions=$conn->query("SELECT tc.id,tc.case_number,tc.procedure_name,tc.status,p.patient_number,p.full_name FROM theatre_cases tc JOIN patients p ON p.id=tc.patient_id ORDER BY tc.scheduled_at DESC,tc.id DESC LIMIT 200");
$selectedCase=null;
if($caseId>0){$stmt=$conn->prepare('SELECT tc.id,tc.case_number,tc.procedure_name,tc.status,tc.scheduled_at,p.patient_number,p.full_name FROM theatre_cases tc JOIN patients p ON p.id=tc.patient_id WHERE tc.id=? LIMIT 1');if($stmt){$stmt->bind_param('i',$caseId);if($stmt->execute())$selectedCase=$stmt->get_result()->fetch_assoc();$stmt->close();}}
$observations=null;$implants=null;$recordsUnavailable=false;
if($selectedCase){
 $stmt=$conn->prepare('SELECT observed_at,heart_rate,respiratory_rate,systolic_bp,diastolic_bp,oxygen_saturation,temperature_c,pain_score,consciousness_level,oxygen_support,interventions,notes FROM theatre_recovery_observations WHERE case_id=? ORDER BY observed_at DESC,id DESC LIMIT 100');
 if($stmt){$stmt->bind_param('i',$caseId);if($stmt->execute())$observations=$stmt->get_result();else $recordsUnavailable=true;$stmt->close();}else $recordsUnavailable=true;
 $stmt=$conn->prepare('SELECT item_name,manufacturer,model,lot_number,serial_number,expiry_date,quantity,implanted_site,notes,recorded_at FROM theatre_implant_usage WHERE case_id=? ORDER BY recorded_at DESC,id DESC');
 if($stmt){$stmt->bind_param('i',$caseId);if($stmt->execute())$implants=$stmt->get_result();else $recordsUnavailable=true;$stmt->close();}else $recordsUnavailable=true;
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<style>
.tr-wrap{max-width:1450px;margin:auto;padding:24px;color:#1b3553}.tr-hero{background:linear-gradient(120deg,#082b4c,#075b9d);padding:24px;border-radius:16px;color:#fff;margin-bottom:18px}.tr-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.tr-panel{background:#fff;border:1px solid #e2eaf3;border-radius:14px;padding:18px;margin-bottom:16px}.tr-panel h2{font-size:17px;margin:0 0 12px;color:#173b62}.tr-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.tr-form label{font-size:12px;font-weight:700;color:#43536a;display:flex;flex-direction:column;gap:5px}.tr-form input,.tr-form select,.tr-form textarea{box-sizing:border-box;width:100%;padding:9px;border:1px solid #d7e0ec;border-radius:7px;font:inherit;font-size:13px}.tr-form textarea{min-height:65px}.tr-wide{grid-column:1/-1}.tr-btn{background:#075b9d;color:#fff;border:0;border-radius:8px;padding:10px 14px;font-weight:800;cursor:pointer}.tr-table{width:100%;border-collapse:collapse;font-size:12px}.tr-table th,.tr-table td{padding:9px;border-bottom:1px solid #e7edf4;text-align:left;vertical-align:top}.tr-table th{background:#f6f9fc;color:#64758b}.tr-muted{font-size:12px;color:#687991}.tr-alert{padding:12px;border-radius:9px;margin:12px 0}.tr-error{background:#fff0f0;color:#8a2929}.tr-success{background:#e6f7ee;color:#176345}@media(max-width:800px){.tr-grid,.tr-form{grid-template-columns:1fr}.tr-wrap{padding:12px}}
</style>
<div class="tr-wrap">
<section class="tr-hero"><h1>Theatre Clinical Records</h1><p>PACU observations and implant/device traceability. These records supplement—not replace—approved clinical charts and local protocols.</p></section>
<?php if($error!==''): ?><div class="tr-alert tr-error"><?= theatre_records_h($error) ?></div><?php endif; ?><?php if($message!==''): ?><div class="tr-alert tr-success"><?= theatre_records_h($message) ?></div><?php endif; ?>
<section class="tr-panel"><h2>Select theatre case</h2><form method="get" class="tr-form"><label class="tr-wide">Case<select name="case_id" required><option value="">Select case</option><?php if($caseOptions):while($opt=$caseOptions->fetch_assoc()): ?><option value="<?= (int)$opt['id'] ?>" <?= $caseId===(int)$opt['id']?'selected':'' ?>><?= theatre_records_h($opt['case_number'].' — '.$opt['patient_number'].' '.$opt['full_name'].' — '.$opt['procedure_name'].' ('.$opt['status'].')') ?></option><?php endwhile;endif; ?></select></label><div class="tr-wide"><button class="tr-btn">Load case records</button> <a href="index.php">Return to Theatre &amp; Surgery</a></div></form>
<?php if($selectedCase): ?><p><strong><?= theatre_records_h($selectedCase['case_number']) ?></strong> — <?= theatre_records_h($selectedCase['patient_number'].' '.$selectedCase['full_name']) ?> — <?= theatre_records_h($selectedCase['procedure_name']) ?> — <?= theatre_records_h($selectedCase['status']) ?></p><?php endif; ?>
<?php if($recordsUnavailable): ?><p class="tr-alert tr-error">Clinical record tables unavailable. Apply database/theatre_clinical_records_migration.sql in a backed-up test database.</p><?php endif; ?></section>
<?php if($selectedCase): ?><div class="tr-grid">
<section class="tr-panel"><h2>Recovery / PACU observations</h2><p class="tr-muted">Record observations while the case is in Recovery.</p>
<?php if($canEdit&&$selectedCase['status']==='Recovery'): ?><form method="post" class="tr-form"><input type="hidden" name="csrf_token" value="<?= theatre_records_h(csrf_token()) ?>"><input type="hidden" name="case_id" value="<?= (int)$caseId ?>"><input type="hidden" name="action" value="observation">
<label>Heart rate (bpm)<input type="number" name="heart_rate" min="20" max="250"></label><label>Respiratory rate (/min)<input type="number" name="respiratory_rate" min="1" max="80"></label><label>Systolic BP<input type="number" name="systolic_bp" min="40" max="300"></label><label>Diastolic BP<input type="number" name="diastolic_bp" min="20" max="200"></label><label>Oxygen saturation (%)<input type="number" step="0.01" name="oxygen_saturation" min="0" max="100"></label><label>Temperature (°C)<input type="number" step="0.1" name="temperature_c" min="25" max="45"></label><label>Pain score (0–10)<input type="number" name="pain_score" min="0" max="10"></label><label>Consciousness<select name="consciousness_level"><option value="">Not recorded</option><?php foreach(['Alert','Voice','Pain','Unresponsive','Other'] as $v): ?><option><?= theatre_records_h($v) ?></option><?php endforeach; ?></select></label><label>Oxygen support<input name="oxygen_support" maxlength="120"></label><label>Interventions<textarea name="interventions" maxlength="1000"></textarea></label><label class="tr-wide">Notes<textarea name="notes" maxlength="4000"></textarea></label><div class="tr-wide"><button class="tr-btn">Save observation</button></div></form><?php elseif(!$canEdit): ?><p class="tr-muted">Theatre Edit permission is required to record observations.</p><?php else: ?><p class="tr-muted">Move the case to Recovery through the main Theatre workflow before adding observations.</p><?php endif; ?>
<h3>Recorded observations</h3><div style="overflow:auto"><table class="tr-table"><thead><tr><th>Time</th><th>Vitals</th><th>Consciousness / support</th><th>Interventions / notes</th></tr></thead><tbody><?php if($observations):while($o=$observations->fetch_assoc()): ?><tr><td><?= theatre_records_h($o['observed_at']) ?></td><td>HR <?= theatre_records_h($o['heart_rate']??'—') ?>; RR <?= theatre_records_h($o['respiratory_rate']??'—') ?><br>BP <?= theatre_records_h($o['systolic_bp']??'—') ?>/<?= theatre_records_h($o['diastolic_bp']??'—') ?><br>SpO₂ <?= theatre_records_h($o['oxygen_saturation']??'—') ?>%; Temp <?= theatre_records_h($o['temperature_c']??'—') ?>°C; Pain <?= theatre_records_h($o['pain_score']??'—') ?></td><td><?= theatre_records_h($o['consciousness_level']??'—') ?><br><?= theatre_records_h($o['oxygen_support']??'') ?></td><td><?= nl2br(theatre_records_h(($o['interventions']??'').(($o['notes']??'')!==''?"\n".$o['notes']:''))) ?></td></tr><?php endwhile;else: ?><tr><td colspan="4">No observations recorded.</td></tr><?php endif; ?></tbody></table></div></section>
<section class="tr-panel"><h2>Implant / device traceability</h2><p class="tr-muted">A lot/batch or serial number is required.</p>
<?php if($canEdit&&in_array($selectedCase['status'],['In Theatre','Recovery','Completed'],true)): ?><form method="post" class="tr-form"><input type="hidden" name="csrf_token" value="<?= theatre_records_h(csrf_token()) ?>"><input type="hidden" name="case_id" value="<?= (int)$caseId ?>"><input type="hidden" name="action" value="implant"><label>Item / device name *<input name="item_name" required maxlength="180"></label><label>Manufacturer<input name="manufacturer" maxlength="160"></label><label>Model / catalogue no.<input name="model" maxlength="160"></label><label>Lot / batch number<input name="lot_number" maxlength="120"></label><label>Serial number<input name="serial_number" maxlength="120"></label><label>Expiry date<input type="date" name="expiry_date"></label><label>Quantity *<input type="number" name="quantity" min="1" max="32767" value="1" required></label><label>Implanted site / location<input name="implanted_site" maxlength="180"></label><label class="tr-wide">Notes<textarea name="notes" maxlength="1000"></textarea></label><div class="tr-wide"><button class="tr-btn">Save device record</button></div></form><?php elseif(!$canEdit): ?><p class="tr-muted">Theatre Edit permission is required to record device traceability.</p><?php else: ?><p class="tr-muted">Device traceability can be recorded once the case reaches In Theatre.</p><?php endif; ?>
<h3>Recorded devices / implants</h3><div style="overflow:auto"><table class="tr-table"><thead><tr><th>Item</th><th>Traceability</th><th>Qty / site</th><th>Recorded</th></tr></thead><tbody><?php if($implants):while($i=$implants->fetch_assoc()): ?><tr><td><?= theatre_records_h($i['item_name']) ?><br><?= theatre_records_h($i['manufacturer']??'') ?> <?= theatre_records_h($i['model']??'') ?><div class="tr-muted"><?= theatre_records_h($i['notes']??'') ?></div></td><td>Lot: <?= theatre_records_h($i['lot_number']??'—') ?><br>Serial: <?= theatre_records_h($i['serial_number']??'—') ?><br>Expiry: <?= theatre_records_h($i['expiry_date']??'—') ?></td><td><?= (int)$i['quantity'] ?><br><?= theatre_records_h($i['implanted_site']??'—') ?></td><td><?= theatre_records_h($i['recorded_at']) ?></td></tr><?php endwhile;else: ?><tr><td colspan="4">No device records.</td></tr><?php endif; ?></tbody></table></div></section></div><?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
