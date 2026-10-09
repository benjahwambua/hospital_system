<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action=(string)($_POST['action']??'');
    require_module_access($conn,'insurance',$action==='verify'?'approve':'create');
} else require_module_access($conn,'insurance','view');

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token']; $error=''; $success='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($csrf,(string)($_POST['csrf_token']??''))) { $error='Invalid security token.'; }
    else {
        $action=(string)($_POST['action']??'');
        try {
            if ($action==='add') {
                $patientId=(int)($_POST['patient_id']??0); $payerId=(int)($_POST['payer_id']??0); $planId=(int)($_POST['plan_id']??0);
                $member=trim((string)($_POST['member_number']??'')); $card=trim((string)($_POST['card_number']??'')); $principal=trim((string)($_POST['principal_member_name']??''));
                if($patientId<=0||$payerId<=0||$member==='') throw new Exception('Patient, payer and member number are required.');
                $s=$conn->prepare("INSERT INTO patient_coverages (patient_id,payer_id,plan_id,member_number,card_number,principal_member_name,eligibility_status,start_date,end_date,is_primary) VALUES (?,?,?,?,?,?, 'Pending', NULLIF(?,''), NULLIF(?,''), 1)");
                if(!$s) throw new Exception('Unable to prepare coverage: '.$conn->error);
                $start=(string)($_POST['start_date']??''); $end=(string)($_POST['end_date']??'');
                $s->bind_param('iiissssss',$patientId,$payerId,$planId,$member,$card,$principal,$start,$end); if(!$s->execute()) throw new Exception($s->error); $id=$s->insert_id; $s->close();
                if(function_exists('audit')) audit('insurance_coverage_created',"coverage_id={$id},patient_id={$patientId},payer_id={$payerId}");
                $success='Coverage added and marked Pending for verification.';
            } elseif ($action==='verify') {
                $id=(int)($_POST['coverage_id']??0); $status=(string)($_POST['status']??'Verified');
                if(!in_array($status,['Verified','Inactive','Expired','Rejected'],true)||$id<=0) throw new Exception('Invalid verification request.');
                $s=$conn->prepare("UPDATE patient_coverages SET eligibility_status=?,verification_reference=?,updated_at=NOW() WHERE id=?");
                if(!$s) throw new Exception($conn->error); $ref=trim((string)($_POST['verification_reference']??'')); $s->bind_param('ssi',$status,$ref,$id); if(!$s->execute()||$s->affected_rows!==1) throw new Exception('Coverage could not be updated.'); $s->close();
                if(function_exists('audit')) audit('insurance_coverage_verified',"coverage_id={$id},status={$status},reference={$ref}");
                $success='Coverage eligibility updated.';
            }
        } catch(Throwable $e) { $error='Unable to save the coverage record. '.$e->getMessage(); }
    }
}
$tablesReady=true; foreach(['patient_coverages','payers','payer_plans'] as $t){$q=$conn->query("SHOW TABLES LIKE '".$conn->real_escape_string($t)."'");if(!$q||$q->num_rows===0)$tablesReady=false;}
$patients=[];$payers=[];$plans=[];$coverages=[];
if($tablesReady){
 $r=$conn->query("SELECT id,patient_number,full_name FROM patients ORDER BY full_name LIMIT 500");if($r)while($x=$r->fetch_assoc())$patients[]=$x;
 $r=$conn->query("SELECT id,payer_name,payer_type FROM payers WHERE active=1 ORDER BY payer_name");if($r)while($x=$r->fetch_assoc())$payers[]=$x;
 $r=$conn->query("SELECT pp.id,pp.payer_id,pp.plan_name FROM payer_plans pp WHERE pp.active=1 ORDER BY pp.plan_name");if($r)while($x=$r->fetch_assoc())$plans[]=$x;
 $r=$conn->query("SELECT pc.*,p.full_name,p.patient_number,py.payer_name,pp.plan_name FROM patient_coverages pc JOIN patients p ON p.id=pc.patient_id JOIN payers py ON py.id=pc.payer_id LEFT JOIN payer_plans pp ON pp.id=pc.plan_id ORDER BY pc.updated_at DESC,pc.id DESC LIMIT 100");if($r)while($x=$r->fetch_assoc())$coverages[]=$x;
}
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid hms-module-page"><div class="hms-module-shell">
<section class="hms-module-hero"><div><div class="hms-module-kicker">Insurance &amp; SHA</div><h1>Patient Coverage</h1><p>Register payer coverage and control eligibility before claims are raised.</p></div></section>
<?php if(!$tablesReady): ?><div class="alert alert-warning">Run <strong>database/insurance_migration.sql</strong> before using patient coverage.</div><?php else: ?>
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; if($success): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>
<div class="card shadow-sm mb-4"><div class="card-body"><h5 style="font-weight:800">Add Coverage</h5><form method="post" class="row">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="add">
<div class="col-md-3 mb-3"><label>Patient</label><select name="patient_id" class="form-control" required><option value="">Select patient</option><?php foreach($patients as $p): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['full_name'].' — '.$p['patient_number'])?></option><?php endforeach; ?></select></div>
<div class="col-md-2 mb-3"><label>Payer</label><select name="payer_id" class="form-control" required><option value="">Select payer</option><?php foreach($payers as $p): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['payer_name'])?></option><?php endforeach; ?></select></div>
<div class="col-md-2 mb-3"><label>Plan</label><select name="plan_id" class="form-control"><option value="0">No specific plan</option><?php foreach($plans as $p): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['plan_name'])?></option><?php endforeach; ?></select></div>
<div class="col-md-2 mb-3"><label>Member Number</label><input name="member_number" class="form-control" required></div>
<div class="col-md-2 mb-3"><label>Card Number</label><input name="card_number" class="form-control"></div>
<div class="col-md-1 mb-3 d-flex align-items-end"><button class="btn btn-primary btn-block" title="Add"><i class="fas fa-plus"></i></button></div>
<div class="col-md-3 mb-3"><label>Principal Member</label><input name="principal_member_name" class="form-control"></div><div class="col-md-2 mb-3"><label>Start Date</label><input type="date" name="start_date" class="form-control"></div><div class="col-md-2 mb-3"><label>End Date</label><input type="date" name="end_date" class="form-control"></div>
</form></div></div>
<div class="card shadow-sm"><div class="card-header bg-white"><strong>Coverage Register</strong><span class="float-right text-muted"><?=count($coverages)?> record(s)</span></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Patient</th><th>Payer</th><th>Plan</th><th>Member</th><th>Eligibility</th><th>Verification</th><th>Action</th></tr></thead><tbody>
<?php foreach($coverages as $c): ?><tr><td><strong><?=htmlspecialchars($c['full_name'])?></strong><br><small><?=htmlspecialchars($c['patient_number'])?></small></td><td><?=htmlspecialchars($c['payer_name'])?></td><td><?=htmlspecialchars($c['plan_name']??'—')?></td><td><?=htmlspecialchars($c['member_number'])?></td><td><span class="badge badge-info"><?=htmlspecialchars($c['eligibility_status'])?></span></td><td><?=htmlspecialchars($c['verification_reference']??'—')?></td><td><?php if($c['eligibility_status']==='Pending'): ?><form method="post" class="form-inline"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="verify"><input type="hidden" name="coverage_id" value="<?=$c['id']?>"><input name="verification_reference" class="form-control form-control-sm mr-1" placeholder="Reference" required><select name="status" class="form-control form-control-sm mr-1"><option>Verified</option><option>Rejected</option></select><button class="btn btn-sm btn-success"><i class="fas fa-check"></i></button></form><?php else: ?><span class="text-muted">No action</span><?php endif; ?></td></tr><?php endforeach; if(!$coverages): ?><tr><td colspan="7" class="text-center text-muted py-4">No coverage records found.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php endif; ?></div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
