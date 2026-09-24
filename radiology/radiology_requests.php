<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $enc_id = intval($_POST['encounter_id'] ?? 0);
    $procedure = trim($_POST['procedure'] ?? '');
    $stmt = $conn->prepare("SELECT patient_id, visit_id FROM encounters WHERE id=? LIMIT 1");
    $stmt->bind_param('i',$enc_id); $stmt->execute(); $enc=$stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($enc && $procedure !== '') {
        $visit_id=(int)($enc['visit_id'] ?? 0);
        if($visit_id<=0) $visit_id=get_or_create_current_visit($conn,(int)$enc['patient_id'],'Outpatient','Radiology');
        $stmt=$conn->prepare("INSERT INTO procedures (encounter_id, procedure_name, requested_by, requested_at) VALUES (?, ?, ?, NOW())");
        $uid=(int)($_SESSION['user_id']??0); $stmt->bind_param('isi',$enc_id,$procedure,$uid); $stmt->execute(); $stmt->close();
        $svc=$conn->prepare("SELECT id,price FROM services_master WHERE service_name=? AND category='radiology' AND active=1 LIMIT 1");
        if($svc){$svc->bind_param('s',$procedure);$svc->execute();$service=$svc->get_result()->fetch_assoc();$svc->close();
            if($service && $visit_id>0){
                $ps=$conn->prepare("INSERT INTO patient_services (patient_id,service_id,category,price,visit_id,created_at,status) VALUES (?,?,?,?,?,NOW(),'Pending')");
                if($ps){$pid=(int)$enc['patient_id'];$price=(float)$service['price'];$sid=(int)$service['id'];$cat = 'radiology';
                $ps->bind_param('iisdi',$pid,$sid,$cat,$price,$visit_id);$ps->execute();$ps->close();
                    $invoice=get_or_create_visit_invoice($conn,$pid,$visit_id); add_invoice_item($conn,$invoice,'Radiology: '.$procedure,1,$price,'radiology',$sid); post_invoice_journal($conn,$invoice,$pid,$price,'Radiology order');
                }
            }
        }
    }
}

$encounters = $conn->query("SELECT e.id, p.full_name FROM encounters e JOIN patients p ON p.id=e.patient_id ORDER BY e.created_at DESC");
$procedures = $conn->query("SELECT pr.*, p.full_name FROM procedures pr JOIN encounters e ON e.id=pr.encounter_id JOIN patients p ON p.id=e.patient_id ORDER BY pr.requested_at DESC");

$all_procedures = ['X-Ray','Ultrasound','CT Scan','ECG','Minor Surgery'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
<h3>Radiology / Procedures</h3>
<form method="post">
<label>Encounter</label>
<select name="encounter_id" class="form-control">
<?php while($e=$encounters->fetch_assoc()): ?>
<option value="<?=$e['id']?>"><?=htmlspecialchars($e['full_name'])?> (<?=$e['id']?>)</option>
<?php endwhile; ?>
</select>
<label>Procedure / Radiology</label>
<select name="procedure" class="form-control">
<?php foreach($all_procedures as $p): ?><option><?=htmlspecialchars($p)?></option><?php endforeach; ?>
</select>
<div style="margin-top:10px;"><button class="btn" type="submit">Request Procedure</button></div>
</form>

<hr>
<h4>All Procedures</h4>
<table class="table">
<thead><tr><th>Patient</th><th>Procedure</th><th>Requested At</th></tr></thead>
<tbody>
<?php while($pr=$procedures->fetch_assoc()): ?>
<tr>
<td><?=htmlspecialchars($pr['full_name'])?></td>
<td><?=htmlspecialchars($pr['procedure_name'])?></td>
<td><?=$pr['requested_at']?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
