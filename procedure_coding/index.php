<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'procedure_coding', 'view');

function procedure_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$canCreate=can_module_action($conn,'procedure_coding','create');
$canEdit=can_module_action($conn,'procedure_coding','edit');
$message='';$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf_token($_POST['csrf_token']??null)){
        $error='Your session token expired. Refresh the page and try again.';
    }elseif(($_POST['action']??'')==='add_code'){
        if(!$canEdit){http_response_code(403);exit('Forbidden: procedure catalogue edit permission required.');}
        $system=(string)($_POST['code_system']??'Local');
        $code=strtoupper(trim((string)($_POST['code']??'')));
        $description=trim((string)($_POST['description']??''));
        $category=trim((string)($_POST['category']??''));
        $feeRaw=trim((string)($_POST['standard_fee']??''));
        $fee=$feeRaw===''?null:(is_numeric($feeRaw)?(float)$feeRaw:-1);
        if(!in_array($system,['ICD-10-PCS','CPT','Local'],true)||$code===''||$description===''||$fee===-1||($fee!==null&&$fee<0)){
            $error='Enter a valid code system, code, description and non-negative optional standard fee.';
        }else{
            $uid=(int)($_SESSION['user_id']??0);
            $stmt=$conn->prepare('INSERT INTO procedure_codes (code_system,code,description,category,standard_fee,created_by,updated_by) VALUES (?,?,?,?,?,?,?)');
            if(!$stmt)$error='Unable to prepare the catalogue entry. Confirm the procedure coding migration.';
            else{
                $stmt->bind_param('ssssdii',$system,$code,$description,$category,$fee,$uid,$uid);
                if($stmt->execute()){
                    $newId=(int)$stmt->insert_id;if(function_exists('audit'))audit('procedure_code_created','procedure_code_id='.$newId.',system='.$system.',code='.$code);
                    $message='Procedure code added to the local catalogue.';
                }else $error='Code could not be added. It may already exist in this code system.';
                $stmt->close();
            }
        }
    }elseif(($_POST['action']??'')==='assign'){
        if(!$canCreate){http_response_code(403);exit('Forbidden: procedure create permission required.');}
        $visitId=filter_input(INPUT_POST,'visit_id',FILTER_VALIDATE_INT)?:0;
        $codeId=filter_input(INPUT_POST,'procedure_code_id',FILTER_VALIDATE_INT)?:0;
        $laterality=(string)($_POST['laterality']??'Not Applicable');
        $provider=trim((string)($_POST['performing_provider']??''));
        $notes=trim((string)($_POST['clinical_notes']??''));
        if($visitId<=0||$codeId<=0||!in_array($laterality,['Not Applicable','Left','Right','Bilateral'],true)){
            $error='Select a visit, procedure code and valid laterality.';
        }else{
            $conn->begin_transaction();
            try{
                $v=$conn->prepare('SELECT id,patient_id,status FROM visits WHERE id=? FOR UPDATE');
                $v->bind_param('i',$visitId);$v->execute();$visit=$v->get_result()->fetch_assoc();$v->close();
                if(!$visit)throw new RuntimeException('The selected visit was not found.');
                if(in_array(($visit['status']??''),['Completed','Cancelled'],true))throw new RuntimeException('This visit is closed. Select an active visit before adding a procedure.');
                $c=$conn->prepare('SELECT id FROM procedure_codes WHERE id=? AND active=1 LIMIT 1');
                $c->bind_param('i',$codeId);$c->execute();$valid=(bool)$c->get_result()->fetch_assoc();$c->close();
                if(!$valid)throw new RuntimeException('The selected procedure code is inactive or unavailable.');
                $patientId=(int)$visit['patient_id'];$uid=(int)($_SESSION['user_id']??0);
                $stmt=$conn->prepare("INSERT INTO visit_procedures (patient_id,visit_id,procedure_code_id,laterality,performing_provider,clinical_notes,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?)");
                if(!$stmt)throw new RuntimeException('Unable to prepare procedure assignment.');
                $stmt->bind_param('iiisssii',$patientId,$visitId,$codeId,$laterality,$provider,$notes,$uid,$uid);
                if(!$stmt->execute())throw new RuntimeException('Unable to save the procedure assignment.');
                $newId=(int)$stmt->insert_id;$stmt->close();$conn->commit();
                if(function_exists('audit'))audit('visit_procedure_added','procedure_id='.$newId.',visit_id='.$visitId.',code_id='.$codeId);
                $message='Procedure recorded against the selected visit.';
            }catch(Throwable $e){$conn->rollback();$error=$e instanceof RuntimeException?$e->getMessage():'Procedure could not be saved.';}
        }
    }elseif(($_POST['action']??'')==='update_procedure'){
        if(!$canEdit){http_response_code(403);exit('Forbidden: procedure edit permission required.');}
        $id=filter_input(INPUT_POST,'procedure_id',FILTER_VALIDATE_INT)?:0;
        $status=(string)($_POST['procedure_type']??'Planned');
        $laterality=(string)($_POST['laterality']??'Not Applicable');
        $performed=trim((string)($_POST['performed_at']??''));
        $provider=trim((string)($_POST['performing_provider']??''));
        $notes=trim((string)($_POST['clinical_notes']??''));
        $reason=trim((string)($_POST['status_reason']??''));
        if($id<=0||!in_array($status,['Planned','Performed','Cancelled','Entered in Error'],true)||!in_array($laterality,['Not Applicable','Left','Right','Bilateral'],true)){
            $error='Invalid procedure update.';
        }elseif($status==='Performed'&&$performed===''){
            $error='Record the actual performed date and time before marking the procedure performed.';
        }elseif(in_array($status,['Cancelled','Entered in Error'],true)&&$reason===''){
            $error='A reason is required for cancellation or entered-in-error status.';
        }else{
            $performedSql=null;
            if($performed!==''){$dt=DateTime::createFromFormat('Y-m-d\TH:i',$performed);if(!$dt)$error='Enter a valid performed date and time.';else $performedSql=$dt->format('Y-m-d H:i:s');}
            if($error===''){
                $conn->begin_transaction();
                try{
                    $lock=$conn->prepare('SELECT id,procedure_type FROM visit_procedures WHERE id=? FOR UPDATE');
                    $lock->bind_param('i',$id);$lock->execute();$current=$lock->get_result()->fetch_assoc();$lock->close();
                    if(!$current)throw new RuntimeException('Procedure record was not found.');
                    $old=(string)$current['procedure_type'];
                    $transitions=['Planned'=>['Planned','Performed','Cancelled','Entered in Error'],'Performed'=>['Performed','Entered in Error'],'Cancelled'=>['Cancelled'],'Entered in Error'=>['Entered in Error']];
                    if(!in_array($status,$transitions[$old]??[],true))throw new RuntimeException('This procedure status transition is not allowed.');
                    $uid=(int)($_SESSION['user_id']??0);
                    $stmt=$conn->prepare('UPDATE visit_procedures SET procedure_type=?,laterality=?,performed_at=COALESCE(?,performed_at),performing_provider=?,clinical_notes=?,status_reason=?,updated_by=?,updated_at=NOW() WHERE id=?');
                    if(!$stmt)throw new RuntimeException('Unable to prepare procedure update.');
                    $stmt->bind_param('ssssssii',$status,$laterality,$performedSql,$provider,$notes,$reason,$uid,$id);
                    if(!$stmt->execute())throw new RuntimeException('Procedure update failed.');
                    $stmt->close();$conn->commit();
                    if(function_exists('audit'))audit('visit_procedure_updated','procedure_id='.$id.',from='.$old.',to='.$status);
                    $message='Procedure record updated.';
                }catch(Throwable $e){$conn->rollback();$error=$e instanceof RuntimeException?$e->getMessage():'Procedure update failed.';}
            }
        }
    }
}

$search=trim((string)($_GET['search']??''));$like='%'.$search.'%';
if($search!==''){$codes=$conn->prepare("SELECT id,code_system,code,description,category,standard_fee FROM procedure_codes WHERE active=1 AND (code LIKE ? OR description LIKE ?) ORDER BY code_system,code LIMIT 1000");$codes->bind_param('ss',$like,$like);$codes->execute();$codeRows=$codes->get_result();}
else{$codeRows=$conn->query('SELECT id,code_system,code,description,category,standard_fee FROM procedure_codes WHERE active=1 ORDER BY code_system,code LIMIT 1000');}
$visits=$conn->query("SELECT v.id,v.visit_number,v.visit_date,v.status,p.patient_number,p.full_name FROM visits v JOIN patients p ON p.id=v.patient_id ORDER BY v.visit_date DESC,v.id DESC LIMIT 1000");
$records=$conn->query("SELECT vp.*,v.visit_number,v.visit_date,p.patient_number,p.full_name,pc.code_system,pc.code,pc.description,pc.standard_fee FROM visit_procedures vp JOIN visits v ON v.id=vp.visit_id JOIN patients p ON p.id=vp.patient_id JOIN procedure_codes pc ON pc.id=vp.procedure_code_id ORDER BY vp.created_at DESC LIMIT 250");
$stats=['today'=>0,'planned'=>0,'performed'=>0,'catalogue'=>0];
if($q=$conn->query("SELECT SUM(DATE(created_at)=CURDATE()) today_count,SUM(procedure_type='Planned') planned_count,SUM(procedure_type='Performed') performed_count FROM visit_procedures")){$r=$q->fetch_assoc()?:[];$stats['today']=(int)($r['today_count']??0);$stats['planned']=(int)($r['planned_count']??0);$stats['performed']=(int)($r['performed_count']??0);}
if($q=$conn->query('SELECT COUNT(*) total FROM procedure_codes WHERE active=1'))$stats['catalogue']=(int)($q->fetch_assoc()['total']??0);
if($search!==''){$catalogue=$conn->prepare("SELECT code_system,code,description,category,standard_fee FROM procedure_codes WHERE active=1 AND (code LIKE ? OR description LIKE ?) ORDER BY code_system,code LIMIT 100");$catalogue->bind_param('ss',$like,$like);$catalogue->execute();$catalogue=$catalogue->get_result();}
else{$catalogue=$conn->query('SELECT code_system,code,description,category,standard_fee FROM procedure_codes WHERE active=1 ORDER BY code_system,code LIMIT 100');}
include __DIR__.'/../includes/header.php';include __DIR__.'/../includes/sidebar.php';
?>
<style>
.procedure-page{padding:26px;background:#f4f7fb;min-height:calc(100vh - 60px)}.procedure-shell{max-width:1550px;margin:auto}.procedure-hero{background:linear-gradient(135deg,#063b73,#075b9d);border-radius:20px;padding:28px 30px;color:#fff;display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:20px;box-shadow:0 10px 28px rgba(6,59,115,.15)}.procedure-hero h1{margin:4px 0 8px;font-size:28px;color:#fff}.procedure-hero p{margin:0;color:#d9eaff;font-size:14px}.procedure-kicker{font-size:10px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#9ee7ff}.procedure-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.procedure-stat,.procedure-panel{background:#fff;border:1px solid #e3eaf3;border-radius:14px;padding:18px;box-shadow:0 3px 12px rgba(22,44,75,.04)}.procedure-stat small{color:#758399;text-transform:uppercase;font-size:10px;letter-spacing:1px;font-weight:800}.procedure-stat strong{display:block;font-size:28px;color:#12385f;margin-top:8px}.procedure-layout{display:grid;grid-template-columns:minmax(290px,.8fr) minmax(0,1.8fr);gap:18px;align-items:start}.procedure-panel h2{font-size:17px;color:#173b62;margin:0 0 15px}.procedure-form label{display:block;font-size:12px;font-weight:700;color:#43536a;margin:12px 0 5px}.procedure-form input,.procedure-form select,.procedure-form textarea{width:100%;border:1px solid #d7e0ec;border-radius:8px;padding:10px 11px;font:inherit;font-size:13px;background:#fff;box-sizing:border-box}.procedure-form textarea{min-height:65px;resize:vertical}.procedure-btn{border:0;border-radius:8px;padding:10px 14px;background:#075b9d;color:#fff;font-weight:800;cursor:pointer;margin-top:12px}.procedure-table-wrap{overflow:auto}.procedure-table{width:100%;border-collapse:collapse;font-size:12px;min-width:900px}.procedure-table th,.procedure-table td{padding:12px 10px;border-bottom:1px solid #e9eef5;text-align:left;vertical-align:top}.procedure-table th{background:#f7f9fc;color:#66758b;text-transform:uppercase;letter-spacing:.6px;font-size:10px}.procedure-muted{color:#758399;font-size:11px;margin-top:4px}.procedure-pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#e8f1fb;color:#174e7e;font-size:10px;font-weight:800}.procedure-pill.good{background:#dff6eb;color:#146345}.procedure-alert{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:13px}.procedure-alert.error{background:#fff0f0;color:#922b2b;border:1px solid #f4d0d0}.procedure-alert.success{background:#e5f7ee;color:#176345;border:1px solid #c5ead7}.procedure-edit details{border:1px solid #e2eaf3;border-radius:9px;padding:9px}.procedure-edit summary{cursor:pointer;color:#075b9d;font-weight:800}.procedure-empty{padding:28px;text-align:center;color:#758399}@media(max-width:1100px){.procedure-layout{grid-template-columns:1fr}.procedure-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.procedure-page{padding:14px}.procedure-hero{padding:22px;align-items:flex-start;flex-direction:column}.procedure-stat{padding:13px}.procedure-stat strong{font-size:23px}}
</style>
<div class="procedure-page"><div class="procedure-shell">
<section class="procedure-hero"><div><div class="procedure-kicker">Clinical Services / Structured Clinical Data</div><h1>Procedure Coding</h1><p>Maintain a procedure code catalogue and record planned or completed procedures against patient visits.</p></div><span class="procedure-pill">Coding foundation · UAT required</span></section>
<?php if($error!==''):?><div class="procedure-alert error"><?=procedure_h($error)?></div><?php endif;?><?php if($message!==''):?><div class="procedure-alert success"><?=procedure_h($message)?></div><?php endif;?>
<div class="procedure-grid"><div class="procedure-stat"><small>Procedures added today</small><strong><?=$stats['today']?></strong></div><div class="procedure-stat"><small>Planned</small><strong><?=$stats['planned']?></strong></div><div class="procedure-stat"><small>Performed</small><strong><?=$stats['performed']?></strong></div><div class="procedure-stat"><small>Active catalogue codes</small><strong><?=$stats['catalogue']?></strong></div></div>
<div class="procedure-layout">
<section class="procedure-panel"><h2>Record procedure</h2><?php if($canCreate):?><form class="procedure-form" method="post"><input type="hidden" name="csrf_token" value="<?=procedure_h(csrf_token())?>"><input type="hidden" name="action" value="assign">
<label>Patient visit *</label><select name="visit_id" required><option value="">Select visit</option><?php if($visits):while($v=$visits->fetch_assoc()):?><option value="<?=(int)$v['id']?>"><?=procedure_h($v['visit_number'].' — '.$v['patient_number'].' '.$v['full_name'].' ('.$v['visit_date'].' / '.$v['status'].')')?></option><?php endwhile;endif;?></select>
<label>Procedure code *</label><select name="procedure_code_id" required><option value="">Select code</option><?php if($codeRows):while($c=$codeRows->fetch_assoc()):?><option value="<?=(int)$c['id']?>"><?=procedure_h('['.$c['code_system'].'] '.$c['code'].' — '.$c['description'])?></option><?php endwhile;endif;?></select>
<label>Laterality</label><select name="laterality"><option>Not Applicable</option><option>Left</option><option>Right</option><option>Bilateral</option></select>
<label>Performing provider (if known)</label><input name="performing_provider" maxlength="180">
<label>Clinical notes</label><textarea name="clinical_notes" maxlength="4000"></textarea><button class="procedure-btn" type="submit">Record planned procedure</button></form><?php else:?><p class="procedure-muted">You have view access only. Request Procedure Coding Create permission.</p><?php endif;?>
<hr style="margin:22px 0;border:0;border-top:1px solid #e5ebf3"><h2>Catalogue maintenance</h2><?php if($canEdit):?><form class="procedure-form" method="post"><input type="hidden" name="csrf_token" value="<?=procedure_h(csrf_token())?>"><input type="hidden" name="action" value="add_code">
<label>Code system *</label><select name="code_system"><option>Local</option><option>ICD-10-PCS</option><option>CPT</option></select><label>Code *</label><input name="code" maxlength="32" required><label>Description *</label><textarea name="description" maxlength="500" required></textarea><label>Category</label><input name="category" maxlength="120"><label>Standard fee (optional; not posted to billing)</label><input type="number" min="0" step="0.01" name="standard_fee"><button class="procedure-btn" type="submit">Add procedure code</button></form><?php else:?><p class="procedure-muted">Catalogue maintenance requires Procedure Coding Edit permission.</p><?php endif;?>
<p class="procedure-muted" style="margin-top:12px">The catalogue starts empty. Load an approved, current procedure code set and validate tariff rules before production. The optional standard fee is informational only and does not create an invoice.</p>
</section>
<section class="procedure-panel"><h2>Visit procedure register</h2><div class="procedure-table-wrap"><table class="procedure-table"><thead><tr><th>Patient / Visit</th><th>Procedure</th><th>Status / Details</th><th>Update</th></tr></thead><tbody><?php if($records&&$records->num_rows):while($p=$records->fetch_assoc()):?><tr><td><strong><?=procedure_h($p['patient_number'].' — '.$p['full_name'])?></strong><div class="procedure-muted"><?=procedure_h($p['visit_number'].' · '.$p['visit_date'])?></div></td><td><strong><?=procedure_h($p['code_system'].' '.$p['code'])?></strong><div><?=procedure_h($p['description'])?></div><div class="procedure-muted">Fee reference: <?=$p['standard_fee']===null?'Not set':number_format((float)$p['standard_fee'],2)?></div></td><td><span class="procedure-pill <?=$p['procedure_type']==='Performed'?'good':''?>"><?=procedure_h($p['procedure_type'])?></span><div class="procedure-muted">Laterality: <?=procedure_h($p['laterality'])?></div><?php if($p['performed_at']):?><div class="procedure-muted">Performed: <?=procedure_h(date('d M Y H:i',strtotime($p['performed_at'])))?></div><?php endif;?><div class="procedure-muted"><?=procedure_h($p['performing_provider'])?></div><div class="procedure-muted"><?=procedure_h($p['clinical_notes'])?></div><?php if($p['status_reason']):?><div class="procedure-muted">Reason: <?=procedure_h($p['status_reason'])?></div><?php endif;?></td><td class="procedure-edit"><?php if($canEdit&&!in_array($p['procedure_type'],['Cancelled','Entered in Error'],true)):?><details><summary>Update procedure</summary><form class="procedure-form" method="post"><input type="hidden" name="csrf_token" value="<?=procedure_h(csrf_token())?>"><input type="hidden" name="action" value="update_procedure"><input type="hidden" name="procedure_id" value="<?=(int)$p['id']?>"><label>Status</label><select name="procedure_type"><?php $next=['Planned'=>['Planned','Performed','Cancelled','Entered in Error'],'Performed'=>['Performed','Entered in Error']];foreach(($next[$p['procedure_type']]??[$p['procedure_type']]) as $st):?><option value="<?=procedure_h($st)?>" <?=$st===$p['procedure_type']?'selected':''?>><?=procedure_h($st)?></option><?php endforeach;?></select><label>Laterality</label><select name="laterality"><?php foreach(['Not Applicable','Left','Right','Bilateral'] as $lat):?><option value="<?=procedure_h($lat)?>" <?=$p['laterality']===$lat?'selected':''?>><?=procedure_h($lat)?></option><?php endforeach;?></select><label>Performed date/time (required for Performed)</label><input type="datetime-local" name="performed_at" value="<?=$p['performed_at']?procedure_h(date('Y-m-d\TH:i',strtotime($p['performed_at']))):''?>"><label>Performing provider</label><input name="performing_provider" maxlength="180" value="<?=procedure_h($p['performing_provider'])?>"><label>Clinical notes</label><textarea name="clinical_notes" maxlength="4000"><?=procedure_h($p['clinical_notes'])?></textarea><label>Reason for cancellation / correction</label><textarea name="status_reason" maxlength="1500"><?=procedure_h($p['status_reason'])?></textarea><button class="procedure-btn" type="submit">Save procedure</button></form></details><?php else:?><span class="procedure-muted"><?=in_array($p['procedure_type'],['Cancelled','Entered in Error'],true)?'Closed':($canEdit?'':'Edit permission required')?></span><?php endif;?></td></tr><?php endwhile;else:?><tr><td colspan="4" class="procedure-empty">No procedures recorded yet.</td></tr><?php endif;?></tbody></table></div>
<h2 style="margin-top:24px">Active procedure catalogue <?= $search!==''?'(filtered)':'(first 100)' ?></h2><form method="get" class="procedure-form" style="display:grid;grid-template-columns:1fr auto;gap:8px;align-items:end;margin-bottom:12px"><div><label>Search code catalogue</label><input name="search" value="<?=procedure_h($search)?>" placeholder="Code or description"></div><button class="procedure-btn" type="submit">Search</button></form><div class="procedure-table-wrap"><table class="procedure-table"><thead><tr><th>System</th><th>Code</th><th>Description</th><th>Category</th></tr></thead><tbody><?php if($catalogue):while($c=$catalogue->fetch_assoc()):?><tr><td><?=procedure_h($c['code_system'])?></td><td><strong><?=procedure_h($c['code'])?></strong></td><td><?=procedure_h($c['description'])?></td><td><?=procedure_h($c['category'])?></td></tr><?php endwhile;else:?><tr><td colspan="4" class="procedure-empty">No active codes.</td></tr><?php endif;?></tbody></table></div>
</section></div><p class="procedure-muted" style="margin-top:16px">Procedure coding is a clinical record only in this increment. It does not replace an operative note, surgical safety documentation, theatre register, or approved tariff and claims workflow.</p>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
