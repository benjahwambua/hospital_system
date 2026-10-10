<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'diagnosis', 'view');

function diagnosis_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$canCreate = can_module_action($conn, 'diagnosis', 'create');
$canEdit = can_module_action($conn, 'diagnosis', 'edit');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'add_code') {
        if (!$canEdit) { http_response_code(403); exit('Forbidden: diagnosis catalogue edit permission required.'); }
        $system = (string)($_POST['code_system'] ?? 'ICD-10');
        $code = strtoupper(trim((string)($_POST['code'] ?? '')));
        $description = trim((string)($_POST['description'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        if (!in_array($system, ['ICD-10','ICD-10-CM','SNOMED CT','Local'], true) || $code === '' || $description === '') {
            $error = 'Select a code system and enter both code and description.';
        } else {
            $uid = (int)($_SESSION['user_id'] ?? 0);
            $stmt = $conn->prepare('INSERT INTO diagnosis_codes (code_system,code,description,category,created_by,updated_by) VALUES (?,?,?,?,?,?)');
            if (!$stmt) $error = 'Unable to prepare the catalogue entry. Confirm the diagnosis migration has been applied.';
            else {
                $stmt->bind_param('ssssii',$system,$code,$description,$category,$uid,$uid);
                if ($stmt->execute()) {
                    $newId=(int)$stmt->insert_id;
                    if (function_exists('audit')) audit('diagnosis_code_created','diagnosis_code_id='.$newId.',system='.$system.',code='.$code);
                    $message='Diagnosis code added to the local catalogue.';
                } else $error = 'Code could not be added. Check whether this code already exists in the selected code system.';
                $stmt->close();
            }
        }
    } elseif (($_POST['action'] ?? '') === 'assign') {
        if (!$canCreate) { http_response_code(403); exit('Forbidden: diagnosis create permission required.'); }
        $visitId=filter_input(INPUT_POST,'visit_id',FILTER_VALIDATE_INT) ?: 0;
        $codeId=filter_input(INPUT_POST,'diagnosis_code_id',FILTER_VALIDATE_INT) ?: 0;
        $type=(string)($_POST['diagnosis_type'] ?? 'Secondary');
        $notes=trim((string)($_POST['clinical_notes'] ?? ''));
        $onset=trim((string)($_POST['onset_date'] ?? ''));
        if ($visitId<=0 || $codeId<=0 || !in_array($type,['Primary','Secondary','Differential','Complication'],true)) {
            $error='Select a visit, a valid diagnosis code and diagnosis type.';
        } else {
            $conn->begin_transaction();
            try {
                $v=$conn->prepare('SELECT id,patient_id,status FROM visits WHERE id=? FOR UPDATE');
                $v->bind_param('i',$visitId);$v->execute();$visit=$v->get_result()->fetch_assoc();$v->close();
                if (!$visit) throw new RuntimeException('The selected visit was not found.');
                if (($visit['status'] ?? '')==='Completed' || ($visit['status'] ?? '')==='Cancelled') throw new RuntimeException('This visit is closed. Start or select an active visit before coding diagnoses.');
                $codeCheck=$conn->prepare('SELECT id FROM diagnosis_codes WHERE id=? AND active=1 LIMIT 1');
                $codeCheck->bind_param('i',$codeId);$codeCheck->execute();$codeExists=(bool)$codeCheck->get_result()->fetch_assoc();$codeCheck->close();
                if (!$codeExists) throw new RuntimeException('The selected diagnosis code is inactive or unavailable.');
                $dup=$conn->prepare('SELECT id FROM visit_diagnoses WHERE visit_id=? AND diagnosis_code_id=? LIMIT 1');
                $dup->bind_param('ii',$visitId,$codeId);$dup->execute();$duplicate=(bool)$dup->get_result()->fetch_assoc();$dup->close();
                if ($duplicate) throw new RuntimeException('This diagnosis code is already recorded for the selected visit.');
                if ($type==='Primary') {
                    $demote=$conn->prepare("UPDATE visit_diagnoses SET diagnosis_type='Secondary',updated_by=?,updated_at=NOW() WHERE visit_id=? AND diagnosis_type='Primary' AND status='Active'");
                    $uid=(int)($_SESSION['user_id']??0);$demote->bind_param('ii',$uid,$visitId);$demote->execute();$demote->close();
                }
                $patientId=(int)$visit['patient_id'];$uid=(int)($_SESSION['user_id']??0);$onsetSql=$onset!==''?$onset:null;
                if ($onsetSql!==null && !DateTime::createFromFormat('Y-m-d',$onsetSql)) throw new RuntimeException('Enter a valid onset date.');
                $stmt=$conn->prepare('INSERT INTO visit_diagnoses (patient_id,visit_id,diagnosis_code_id,diagnosis_type,clinical_notes,onset_date,coded_by,updated_by) VALUES (?,?,?,?,?,?,?,?)');
                if (!$stmt) throw new RuntimeException('Unable to prepare the diagnosis assignment.');
                $stmt->bind_param('iiisssii',$patientId,$visitId,$codeId,$type,$notes,$onsetSql,$uid,$uid);
                if (!$stmt->execute()) throw new RuntimeException('Unable to save diagnosis. Confirm the migration and selected records.');
                $newId=(int)$stmt->insert_id;$stmt->close();$conn->commit();
                if (function_exists('audit')) audit('visit_diagnosis_added','diagnosis_id='.$newId.',visit_id='.$visitId.',code_id='.$codeId.',type='.$type);
                $message='Diagnosis recorded against the selected visit.';
            } catch (Throwable $e) {
                $conn->rollback();$error=$e instanceof RuntimeException?$e->getMessage():'Diagnosis could not be saved.';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'update_diagnosis') {
        if (!$canEdit) { http_response_code(403); exit('Forbidden: diagnosis edit permission required.'); }
        $id=filter_input(INPUT_POST,'diagnosis_id',FILTER_VALIDATE_INT) ?: 0;
        $type=(string)($_POST['diagnosis_type']??'Secondary');
        $status=(string)($_POST['status']??'Active');
        $notes=trim((string)($_POST['clinical_notes']??''));
        $reason=trim((string)($_POST['status_reason']??''));
        if ($id<=0 || !in_array($type,['Primary','Secondary','Differential','Complication'],true) || !in_array($status,['Active','Resolved','Entered in Error'],true)) {
            $error='Invalid diagnosis update.';
        } elseif ($status==='Entered in Error' && $reason==='') {
            $error='A reason is required when marking a diagnosis entered in error.';
        } else {
            $conn->begin_transaction();
            try {
                $lock=$conn->prepare('SELECT id,visit_id,status FROM visit_diagnoses WHERE id=? FOR UPDATE');
                $lock->bind_param('i',$id);$lock->execute();$current=$lock->get_result()->fetch_assoc();$lock->close();
                if (!$current) throw new RuntimeException('Diagnosis record was not found.');
                $visitId=(int)$current['visit_id'];$uid=(int)($_SESSION['user_id']??0);
                if ($type==='Primary' && $status==='Active') {
                    $demote=$conn->prepare("UPDATE visit_diagnoses SET diagnosis_type='Secondary',updated_by=?,updated_at=NOW() WHERE visit_id=? AND id<>? AND diagnosis_type='Primary' AND status='Active'");
                    $demote->bind_param('iii',$uid,$visitId,$id);$demote->execute();$demote->close();
                } elseif ($type==='Primary') {
                    throw new RuntimeException('Only an active diagnosis can be the primary diagnosis.');
                }
                $stmt=$conn->prepare('UPDATE visit_diagnoses SET diagnosis_type=?,status=?,clinical_notes=?,status_reason=?,updated_by=?,updated_at=NOW() WHERE id=?');
                if (!$stmt) throw new RuntimeException('Unable to prepare diagnosis update.');
                $stmt->bind_param('ssssii',$type,$status,$notes,$reason,$uid,$id);
                if (!$stmt->execute()) throw new RuntimeException('Diagnosis update failed.');
                $stmt->close();$conn->commit();
                if (function_exists('audit')) audit('visit_diagnosis_updated','diagnosis_id='.$id.',status='.$status.',type='.$type);
                $message='Diagnosis updated.';
            } catch (Throwable $e) {
                $conn->rollback();$error=$e instanceof RuntimeException?$e->getMessage():'Diagnosis update failed.';
            }
        }
    }
}

$search=trim((string)($_GET['search']??''));
$like='%'.$search.'%';
if ($search!=='') {
    $codes=$conn->prepare("SELECT id,code_system,code,description,category FROM diagnosis_codes WHERE active=1 AND (code LIKE ? OR description LIKE ?) ORDER BY code_system,code LIMIT 1000");
    $codes->bind_param('ss',$like,$like);$codes->execute();$codeRows=$codes->get_result();
} else {
    $codeRows=$conn->query('SELECT id,code_system,code,description,category FROM diagnosis_codes WHERE active=1 ORDER BY code_system,code LIMIT 1000');
}
$visits=$conn->query("SELECT v.id,v.visit_number,v.patient_id,v.visit_date,v.status,p.patient_number,p.full_name FROM visits v JOIN patients p ON p.id=v.patient_id ORDER BY v.visit_date DESC,v.id DESC LIMIT 1000");
$diagnoses=$conn->query("SELECT d.*,v.visit_number,v.visit_date,p.patient_number,p.full_name,c.code_system,c.code,c.description FROM visit_diagnoses d JOIN visits v ON v.id=d.visit_id JOIN patients p ON p.id=d.patient_id JOIN diagnosis_codes c ON c.id=d.diagnosis_code_id ORDER BY d.created_at DESC LIMIT 250");
if ($search!=='') { $catalogueResults=$conn->prepare("SELECT code_system,code,description,category FROM diagnosis_codes WHERE active=1 AND (code LIKE ? OR description LIKE ?) ORDER BY code_system,code LIMIT 100");$catalogueResults->bind_param('ss',$like,$like);$catalogueResults->execute();$catalogueResults=$catalogueResults->get_result(); } else { $catalogueResults=$conn->query('SELECT code_system,code,description,category FROM diagnosis_codes WHERE active=1 ORDER BY code_system,code LIMIT 100'); }
$counts=['today'=>0,'primary'=>0,'active'=>0,'catalogue'=>0];
if($q=$conn->query("SELECT SUM(DATE(created_at)=CURDATE()) today_count,SUM(diagnosis_type='Primary' AND status='Active') primary_count,SUM(status='Active') active_count FROM visit_diagnoses")){$r=$q->fetch_assoc()?:[];$counts['today']=(int)($r['today_count']??0);$counts['primary']=(int)($r['primary_count']??0);$counts['active']=(int)($r['active_count']??0);}
if($q=$conn->query('SELECT COUNT(*) total FROM diagnosis_codes WHERE active=1'))$counts['catalogue']=(int)($q->fetch_assoc()['total']??0);
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<style>
.diagnosis-page{padding:26px;background:#f4f7fb;min-height:calc(100vh - 60px)}.diagnosis-shell{max-width:1550px;margin:auto}.diagnosis-hero{background:linear-gradient(135deg,#063b73,#075b9d);border-radius:20px;padding:28px 30px;color:#fff;display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:20px;box-shadow:0 10px 28px rgba(6,59,115,.15)}.diagnosis-hero h1{margin:4px 0 8px;font-size:28px;color:#fff}.diagnosis-hero p{margin:0;color:#d9eaff;font-size:14px}.diagnosis-kicker{font-size:10px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#9ee7ff}.diagnosis-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.diagnosis-stat,.diagnosis-panel{background:#fff;border:1px solid #e3eaf3;border-radius:14px;padding:18px;box-shadow:0 3px 12px rgba(22,44,75,.04)}.diagnosis-stat small{color:#758399;text-transform:uppercase;font-size:10px;letter-spacing:1px;font-weight:800}.diagnosis-stat strong{display:block;font-size:28px;color:#12385f;margin-top:8px}.diagnosis-layout{display:grid;grid-template-columns:minmax(290px,.8fr) minmax(0,1.8fr);gap:18px;align-items:start}.diagnosis-panel h2{font-size:17px;color:#173b62;margin:0 0 15px}.diagnosis-form label{display:block;font-size:12px;font-weight:700;color:#43536a;margin:12px 0 5px}.diagnosis-form input,.diagnosis-form select,.diagnosis-form textarea{width:100%;border:1px solid #d7e0ec;border-radius:8px;padding:10px 11px;font:inherit;font-size:13px;background:#fff;box-sizing:border-box}.diagnosis-form textarea{min-height:65px;resize:vertical}.diagnosis-btn{border:0;border-radius:8px;padding:10px 14px;background:#075b9d;color:#fff;font-weight:800;cursor:pointer;margin-top:12px}.diagnosis-table-wrap{overflow:auto}.diagnosis-table{width:100%;border-collapse:collapse;font-size:12px;min-width:900px}.diagnosis-table th,.diagnosis-table td{padding:12px 10px;border-bottom:1px solid #e9eef5;text-align:left;vertical-align:top}.diagnosis-table th{background:#f7f9fc;color:#66758b;text-transform:uppercase;letter-spacing:.6px;font-size:10px}.diagnosis-muted{color:#758399;font-size:11px;margin-top:4px}.diagnosis-pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#e8f1fb;color:#174e7e;font-size:10px;font-weight:800}.diagnosis-pill.primary{background:#dff6eb;color:#146345}.diagnosis-alert{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:13px}.diagnosis-alert.error{background:#fff0f0;color:#922b2b;border:1px solid #f4d0d0}.diagnosis-alert.success{background:#e5f7ee;color:#176345;border:1px solid #c5ead7}.diagnosis-edit details{border:1px solid #e2eaf3;border-radius:9px;padding:9px}.diagnosis-edit summary{cursor:pointer;color:#075b9d;font-weight:800}.diagnosis-empty{padding:28px;text-align:center;color:#758399}@media(max-width:1100px){.diagnosis-layout{grid-template-columns:1fr}.diagnosis-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.diagnosis-page{padding:14px}.diagnosis-hero{padding:22px;align-items:flex-start;flex-direction:column}.diagnosis-stat{padding:13px}.diagnosis-stat strong{font-size:23px}}
</style>
<div class="diagnosis-page"><div class="diagnosis-shell">
<section class="diagnosis-hero"><div><div class="diagnosis-kicker">Clinical Services / Structured Clinical Data</div><h1>Diagnosis Coding</h1><p>Maintain a local code catalogue and attach primary, secondary and differential diagnoses to clinical visits.</p></div><span class="diagnosis-pill">Coding foundation · UAT required</span></section>
<?php if($error!==''):?><div class="diagnosis-alert error"><?=diagnosis_h($error)?></div><?php endif;?><?php if($message!==''):?><div class="diagnosis-alert success"><?=diagnosis_h($message)?></div><?php endif;?>
<div class="diagnosis-grid"><div class="diagnosis-stat"><small>Diagnoses added today</small><strong><?=$counts['today']?></strong></div><div class="diagnosis-stat"><small>Active primary diagnoses</small><strong><?=$counts['primary']?></strong></div><div class="diagnosis-stat"><small>Active diagnoses</small><strong><?=$counts['active']?></strong></div><div class="diagnosis-stat"><small>Active catalogue codes</small><strong><?=$counts['catalogue']?></strong></div></div>
<div class="diagnosis-layout">
<section class="diagnosis-panel"><h2>Code a visit diagnosis</h2><?php if($canCreate):?><form class="diagnosis-form" method="post"><input type="hidden" name="csrf_token" value="<?=diagnosis_h(csrf_token())?>"><input type="hidden" name="action" value="assign">
<label for="visit_id">Patient visit *</label><select id="visit_id" name="visit_id" required><option value="">Select visit</option><?php if($visits):while($v=$visits->fetch_assoc()):?><option value="<?=(int)$v['id']?>"><?=diagnosis_h($v['visit_number'].' — '.$v['patient_number'].' '.$v['full_name'].' ('.$v['visit_date'].' / '.$v['status'].')')?></option><?php endwhile;endif;?></select>
<label for="diagnosis_code_id">Diagnosis code *</label><select id="diagnosis_code_id" name="diagnosis_code_id" required><option value="">Select code</option><?php if($codeRows):while($c=$codeRows->fetch_assoc()):?><option value="<?=(int)$c['id']?>"><?=diagnosis_h('['.$c['code_system'].'] '.$c['code'].' — '.$c['description'])?></option><?php endwhile;endif;?></select>
<label for="diagnosis_type">Classification *</label><select id="diagnosis_type" name="diagnosis_type"><option>Primary</option><option>Secondary</option><option>Differential</option><option>Complication</option></select>
<label for="onset_date">Onset date</label><input id="onset_date" type="date" name="onset_date">
<label for="clinical_notes">Clinical notes / supporting evidence</label><textarea id="clinical_notes" name="clinical_notes" maxlength="4000"></textarea><button class="diagnosis-btn" type="submit">Record diagnosis</button></form><?php else:?><p class="diagnosis-muted">You have view access only. Request Diagnosis Create permission to code visits.</p><?php endif;?>
<hr style="margin:22px 0;border:0;border-top:1px solid #e5ebf3"><h2>Catalogue maintenance</h2><?php if($canEdit):?><form class="diagnosis-form" method="post"><input type="hidden" name="csrf_token" value="<?=diagnosis_h(csrf_token())?>"><input type="hidden" name="action" value="add_code">
<label for="code_system">Code system *</label><select id="code_system" name="code_system"><option>ICD-10</option><option>ICD-10-CM</option><option>SNOMED CT</option><option>Local</option></select>
<label for="code">Code *</label><input id="code" name="code" maxlength="32" required>
<label for="description">Description *</label><textarea id="description" name="description" maxlength="500" required></textarea>
<label for="category">Clinical category</label><input id="category" name="category" maxlength="120"><button class="diagnosis-btn" type="submit">Add catalogue code</button></form><?php else:?><p class="diagnosis-muted">Catalogue maintenance requires Diagnosis Edit permission.</p><?php endif;?>
<h2 style="margin-top:24px">Active code catalogue <?= $search!=='' ? '(filtered)' : '(first 100)' ?></h2><div class="diagnosis-table-wrap"><table class="diagnosis-table"><thead><tr><th>System</th><th>Code</th><th>Description</th><th>Category</th></tr></thead><tbody><?php if($catalogueResults):while($cc=$catalogueResults->fetch_assoc()):?><tr><td><?=diagnosis_h($cc['code_system'])?></td><td><strong><?=diagnosis_h($cc['code'])?></strong></td><td><?=diagnosis_h($cc['description'])?></td><td><?=diagnosis_h($cc['category'])?></td></tr><?php endwhile;else:?><tr><td colspan="4" class="diagnosis-empty">No matching active codes.</td></tr><?php endif;?></tbody></table></div>
<p class="diagnosis-muted" style="margin-top:12px">The migration creates an empty catalogue. Import an approved, current code set before production use; locally entered codes are not automatically validated against WHO ICD-10 or national billing rules.</p>
</section>
<section class="diagnosis-panel"><h2>Recent coded diagnoses</h2><form method="get" class="diagnosis-form" style="display:grid;grid-template-columns:1fr auto;gap:8px;align-items:end;margin-bottom:14px"><div><label for="search">Search code catalogue</label><input id="search" name="search" value="<?=diagnosis_h($search)?>" placeholder="Code or description"></div><button class="diagnosis-btn" type="submit">Search codes</button></form>
<div class="diagnosis-table-wrap"><table class="diagnosis-table"><thead><tr><th>Patient / Visit</th><th>Diagnosis</th><th>Type / Status</th><th>Maintenance</th></tr></thead><tbody><?php if($diagnoses&&$diagnoses->num_rows):while($d=$diagnoses->fetch_assoc()):?><tr><td><strong><?=diagnosis_h($d['patient_number'].' — '.$d['full_name'])?></strong><div class="diagnosis-muted"><?=diagnosis_h($d['visit_number'].' · '.$d['visit_date'])?></div></td><td><strong><?=diagnosis_h($d['code_system'].' '.$d['code'])?></strong><div><?=diagnosis_h($d['description'])?></div><div class="diagnosis-muted"><?=diagnosis_h($d['clinical_notes'])?></div></td><td><span class="diagnosis-pill <?= $d['diagnosis_type']==='Primary'?'primary':''?>"><?=diagnosis_h($d['diagnosis_type'])?></span><div class="diagnosis-muted"><?=diagnosis_h($d['status'])?></div><?php if($d['status_reason']):?><div class="diagnosis-muted"><?=diagnosis_h($d['status_reason'])?></div><?php endif;?></td><td class="diagnosis-edit"><?php if($canEdit):?><details><summary>Edit record</summary><form class="diagnosis-form" method="post"><input type="hidden" name="csrf_token" value="<?=diagnosis_h(csrf_token())?>"><input type="hidden" name="action" value="update_diagnosis"><input type="hidden" name="diagnosis_id" value="<?=(int)$d['id']?>">
<label>Classification</label><select name="diagnosis_type"><?php foreach(['Primary','Secondary','Differential','Complication'] as $t):?><option value="<?=diagnosis_h($t)?>" <?=$d['diagnosis_type']===$t?'selected':''?>><?=diagnosis_h($t)?></option><?php endforeach;?></select>
<label>Status</label><select name="status"><?php foreach(['Active','Resolved','Entered in Error'] as $st):?><option value="<?=diagnosis_h($st)?>" <?=$d['status']===$st?'selected':''?>><?=diagnosis_h($st)?></option><?php endforeach;?></select>
<label>Clinical notes</label><textarea name="clinical_notes" maxlength="4000"><?=diagnosis_h($d['clinical_notes'])?></textarea><label>Reason for status change / entered-in-error reason</label><textarea name="status_reason" maxlength="1500"><?=diagnosis_h($d['status_reason'])?></textarea><button class="diagnosis-btn" type="submit">Save changes</button></form></details><?php else:?><span class="diagnosis-muted">Edit permission required</span><?php endif;?></td></tr><?php endwhile;else:?><tr><td colspan="4" class="diagnosis-empty">No coded diagnoses have been recorded yet.</td></tr><?php endif;?></tbody></table></div>
</section></div>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
