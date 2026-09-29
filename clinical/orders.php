<style>
.orders-page{padding:28px 24px 40px;background:#f5f7fb;min-height:calc(100vh - 72px)}
.orders-shell{max-width:1500px;margin:0 auto}
.orders-hero{background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;border-radius:18px;padding:24px 28px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:16px;box-shadow:0 4px 18px rgba(31,45,61,.08)}
.orders-hero h1{margin:3px 0 5px;color:#fff;font-size:26px;font-weight:800}.orders-hero p{margin:0;color:rgba(255,255,255,.8);font-size:13px}.orders-kicker{text-transform:uppercase;letter-spacing:1.5px;font-size:10px;font-weight:800;color:#bfe8ff}
.orders-nav{display:flex;gap:8px;flex-wrap:wrap}.orders-nav a{border-radius:8px;font-weight:700}
/* HMS operational page styling */
.main-content{background:#f5f7fb;min-height:calc(100vh - 72px)}
.main-content>.container-fluid{max-width:1500px}
.main-content .card{border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 18px rgba(31,45,61,.05);overflow:hidden}
.main-content .card-header{background:#fff;border-bottom:1px solid #edf0f5;color:#25324a}
.main-content .table thead th{background:#f8fafc;border-top:0;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.35px}
.main-content .table td{border-color:#edf0f5;vertical-align:middle}
.main-content .table tbody tr:hover{background:#f8fbff}
.main-content .form-control{border-color:#d7dee8;border-radius:9px}
.main-content .form-control:focus{border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}
.main-content .btn{border-radius:8px;font-weight:700}
</style>
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'clinical', 'create');
require_role(['admin','doctor','nurse']);

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
$patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$visitId = (int)($_GET['visit_id'] ?? $_POST['visit_id'] ?? 0);
$message = '';

if ($patientId <= 0) { header('Location: /hospital_system/patients/patient_list.php?notice=select_patient_for_orders'); exit; }
if ($visitId <= 0) $visitId = get_or_create_current_visit($conn, $patientId);

$p = $conn->prepare("SELECT id, full_name, patient_number FROM patients WHERE id=? LIMIT 1");
$p->bind_param('i',$patientId); $p->execute(); $patient=$p->get_result()->fetch_assoc(); $p->close();
if (!$patient) die('Patient not found.');

$v = $conn->prepare("SELECT * FROM visits WHERE id=? AND patient_id=? LIMIT 1");
if ($v) { $v->bind_param('ii',$visitId,$patientId); $v->execute(); $visit=$v->get_result()->fetch_assoc(); $v->close(); }
if (!$visit) die('Visit not found.');

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['place_order'])) {
    if (($visit['status'] ?? '') === 'Completed') {
        $message = 'This visit is already completed. Start a new visit before placing additional orders.';
    } elseif (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
    } else {
        $type = strtolower(trim($_POST['order_type'] ?? ''));
        try {
            $conn->begin_transaction();
            if (in_array($type,['lab','radiology'],true)) {
                $serviceId=(int)($_POST['service_id'] ?? 0);
                $s=$conn->prepare("SELECT id,service_name,category FROM services_master WHERE id=? AND active=1 AND category=? LIMIT 1");
                if (!$s) throw new Exception('Unable to load service.');
                $s->bind_param('is',$serviceId,$type); $s->execute(); $service=$s->get_result()->fetch_assoc(); $s->close();
                if (!$service) throw new Exception('Select a valid service.');
                $resolved=get_service_price($conn,$serviceId,null,null);
                $price=(float)$resolved['price'];
                $invoiceId=get_or_create_visit_invoice($conn,$patientId,$visitId);

                $hasSnapshots=false;
                $checkCols=$conn->query("SHOW COLUMNS FROM patient_services");
                if($checkCols){
                    $snapshotCols=[];
                    while($col=$checkCols->fetch_assoc()) $snapshotCols[$col['Field']]=true;
                    $hasSnapshots=isset($snapshotCols['service_code_snapshot'],$snapshotCols['service_name_snapshot'],$snapshotCols['quantity'],$snapshotCols['gross_amount'],$snapshotCols['discount_amount'],$snapshotCols['net_amount'],$snapshotCols['price_id']);
                }

                if($hasSnapshots){
                    $ins=$conn->prepare("INSERT INTO patient_services (patient_id,service_id,category,price,service_code_snapshot,service_name_snapshot,quantity,gross_amount,discount_amount,net_amount,price_id,visit_id,created_at,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),'Pending')");
                    if (!$ins) throw new Exception('Unable to create order: '.$conn->error);
                    $code=$resolved['service_code']; $name=$resolved['service_name']; $qty=1; $gross=$price; $discount=0.0; $net=$price; $priceId=(int)$resolved['price_id'];
                    $ins->bind_param('iisdssddddii', $patientId,$serviceId,$type,$price,$code,$name,$qty,$gross,$discount,$net,$priceId,$visitId);
                } else {
                    $ins=$conn->prepare("INSERT INTO patient_services (patient_id,service_id,category,price,visit_id,created_at,status) VALUES (?,?,?,?,?,NOW(),'Pending')");
                    if (!$ins) throw new Exception('Unable to create order: '.$conn->error);
                    $ins->bind_param('iisdi',$patientId,$serviceId,$type,$price,$visitId);
                }
                if (!$ins->execute()) throw new Exception($ins->error);
                $ins->close();
                $invoiceItemId=add_invoice_item($conn,$invoiceId,ucfirst($type).': '.$resolved['service_name'],1,$price,$type,$serviceId);
                post_invoice_journal($conn,$invoiceId,$patientId,$price,ucfirst($type).' order',$invoiceItemId);
                $conn->commit();
                $message=ucfirst($type).' order placed. Invoice #'.$invoiceId.'.';
            } elseif ($type==='pharmacy') {
                $medicineId=(int)($_POST['medicine_id'] ?? 0);
                $quantity=max(1,(int)($_POST['quantity'] ?? 1));
                $notes=trim($_POST['order_notes'] ?? '');
                $s=$conn->prepare("SELECT id,drug_name,selling_price,quantity FROM pharmacy_stock WHERE id=? LIMIT 1");
                $s->bind_param('i',$medicineId); $s->execute(); $medicine=$s->get_result()->fetch_assoc(); $s->close();
                if (!$medicine) throw new Exception('Select a valid medicine.');
                if ((int)$medicine['quantity'] < $quantity) throw new Exception('Insufficient stock.');
                $unit=(float)$medicine['selling_price'];
                if (ensure_prescription_visit_column($conn)) {
                    $rx=$conn->prepare("INSERT INTO prescriptions (patient_id,medicine_id,quantity,unit_price,visit_id,frequency,created_at) VALUES (?,?,?,?,?,?,NOW())");
                    $rx->bind_param('iiidis',$patientId,$medicineId,$quantity,$unit,$visitId,$notes);
                } else {
                    $rx=$conn->prepare("INSERT INTO prescriptions (patient_id,medicine_id,quantity,unit_price,frequency,created_at) VALUES (?,?,?,?,?,NOW())");
                    $rx->bind_param('iiids',$patientId,$medicineId,$quantity,$unit,$notes);
                }
                if (!$rx->execute()) throw new Exception($rx->error);
                $prescriptionId=(int)$rx->insert_id; $rx->close();
                $q=$conn->query("SHOW TABLES LIKE 'pharmacy_queue'");
                if (!$q || $q->num_rows === 0) {
                    throw new Exception('Pharmacy queue is not available. Run the clinical care migration before placing pharmacy orders.');
                }
                {
                    $has=$conn->query("SHOW COLUMNS FROM pharmacy_queue LIKE 'visit_id'");
                    if ($has && $has->num_rows) {
                        $pq=$conn->prepare("INSERT INTO pharmacy_queue (prescription_id,patient_id,medicine_id,quantity,status,visit_id,created_at) VALUES (?,?,?,?, 'pending',?,NOW())");
                        if (!$pq) throw new Exception('Unable to prepare pharmacy queue entry.');
                        $pq->bind_param('iiiii',$prescriptionId,$patientId,$medicineId,$quantity,$visitId);
                        if (!$pq->execute()) { $err=$pq->error; $pq->close(); throw new Exception('Unable to send prescription to Pharmacy: '.$err); }
                        $pq->close();
                    } else {
                        $pq=$conn->prepare("INSERT INTO pharmacy_queue (prescription_id,patient_id,medicine_id,quantity,status,created_at) VALUES (?,?,?,?, 'pending',NOW())");
                        if (!$pq) throw new Exception('Unable to prepare pharmacy queue entry.');
                        $pq->bind_param('iiii',$prescriptionId,$patientId,$medicineId,$quantity);
                        if (!$pq->execute()) { $err=$pq->error; $pq->close(); throw new Exception('Unable to send prescription to Pharmacy: '.$err); }
                        $pq->close();
                    }
                }
                $conn->commit();
                $message='Prescription sent to Pharmacy for dispensing. Stock and the pharmacy charge are posted when Pharmacy dispenses. ';
            } else throw new Exception('Select a department.');
        } catch (Throwable $e) {
            $conn->rollback();
            $message=$e->getMessage();
        }
    }
}

$labs=$conn->query("SELECT id,service_name FROM services_master WHERE active=1 AND category='lab' ORDER BY service_name");
$rads=$conn->query("SELECT id,service_name FROM services_master WHERE active=1 AND category='radiology' ORDER BY service_name");
$meds=$conn->query("SELECT id,drug_name,selling_price,quantity FROM pharmacy_stock WHERE quantity>0 ORDER BY drug_name");
$orders=$conn->prepare("SELECT ps.id,ps.category,ps.price,ps.status,ps.created_at,sm.service_name FROM patient_services ps JOIN services_master sm ON sm.id=ps.service_id WHERE ps.patient_id=? AND ps.visit_id=? AND ps.category IN ('lab','radiology') ORDER BY ps.id DESC");
$orders->bind_param('ii',$patientId,$visitId); $orders->execute(); $orderRows=$orders->get_result();

$rxRows=null;
if (ensure_prescription_visit_column($conn)) {
    $rx=$conn->prepare("SELECT pr.id,pr.quantity,pr.unit_price,pr.created_at,s.drug_name FROM prescriptions pr JOIN pharmacy_stock s ON s.id=pr.medicine_id WHERE pr.patient_id=? AND pr.visit_id=? ORDER BY pr.id DESC");
    $rx->bind_param('ii',$patientId,$visitId); $rx->execute(); $rxRows=$rx->get_result();
}

include __DIR__.'/../includes/header.php'; include __DIR__.'/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4">
<div class="card shadow-sm mb-4"><div class="card-body">
<h4 class="font-weight-bold">Clinical Orders</h4>
<div class="text-muted mb-3"><?=htmlspecialchars($patient['full_name'])?> · <?=htmlspecialchars($patient['patient_number'])?> · Visit <?=htmlspecialchars($visit['visit_number'])?></div>
<?php if($message): ?><div class="alert alert-info"><?=htmlspecialchars($message)?></div><?php endif; ?>
<form method="post" class="row">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>"><input type="hidden" name="patient_id" value="<?=$patientId?>"><input type="hidden" name="visit_id" value="<?=$visitId?>">
<div class="col-md-3 mb-3"><label>Department</label><select name="order_type" id="orderType" class="form-control" onchange="toggleOrder()" required><option value="">Select...</option><option value="lab">Laboratory</option><option value="radiology">Radiology</option><option value="pharmacy">Pharmacy</option></select></div>
<div class="col-md-5 mb-3" id="serviceBox"><label>Investigation / Service</label><select name="service_id" class="form-control"><option value="">Select...</option>
<?php if($labs): while($x=$labs->fetch_assoc()): ?><option value="<?=$x['id']?>">Lab: <?=htmlspecialchars($x['service_name'])?> — KES <?=number_format((float)get_service_price($conn,(int)$x['id'])['price'],2)?></option><?php endwhile; endif; ?>
<?php if($rads): while($x=$rads->fetch_assoc()): ?><option value="<?=$x['id']?>">Radiology: <?=htmlspecialchars($x['service_name'])?> — KES <?=number_format($x['price'],2)?></option><?php endwhile; endif; ?></select></div>
<div class="col-md-5 mb-3" id="medicineBox" style="display:none"><label>Medicine</label><select name="medicine_id" class="form-control"><option value="">Select...</option><?php if($meds): while($m=$meds->fetch_assoc()): ?><option value="<?=$m['id']?>"><?=htmlspecialchars($m['drug_name'])?> — KES <?=number_format($m['selling_price'],2)?> · Stock <?=$m['quantity']?></option><?php endwhile; endif; ?></select></div>
<div class="col-md-2 mb-3"><label>Qty</label><input type="number" name="quantity" value="1" min="1" class="form-control"></div>
<div class="col-md-10 mb-3"><label>Instructions / Notes</label><input type="text" name="order_notes" class="form-control"></div>
<div class="col-md-2 mb-3"><button name="place_order" class="btn btn-success btn-block">Place Order</button></div>
</form>
</div></div>
<div class="card shadow-sm"><div class="card-header">Lab & Radiology Orders</div><div class="card-body"><table class="table table-sm"><tr><th>Department</th><th>Service</th><th>Status</th><th>Amount</th><th>Time</th></tr><?php while($o=$orderRows->fetch_assoc()): ?><tr><td><?=htmlspecialchars(ucfirst($o['category']))?></td><td><?=htmlspecialchars($o['service_name'])?></td><td><?=htmlspecialchars($o['status']??'Pending')?></td><td>KES <?=number_format($o['price'],2)?></td><td><?=htmlspecialchars($o['created_at'])?></td></tr><?php endwhile; ?></table></div></div>
<?php if($rxRows): ?><div class="card shadow-sm mt-4"><div class="card-header">Pharmacy Orders</div><div class="card-body"><table class="table table-sm"><tr><th>Medicine</th><th>Qty</th><th>Unit Price</th><th>Time</th></tr><?php while($r=$rxRows->fetch_assoc()): ?><tr><td><?=htmlspecialchars($r['drug_name'])?></td><td><?=$r['quantity']?></td><td>KES <?=number_format($r['unit_price'],2)?></td><td><?=htmlspecialchars($r['created_at'])?></td></tr><?php endwhile; ?></table></div></div><?php endif; ?>
<a class="btn btn-outline-primary mt-3" href="../patients/patient_dashboard.php?id=<?=$patientId?>&tab=clinical">Back to Clinical Care</a>
</div></div>
<script>
function toggleOrder(){var t=document.getElementById('orderType').value;document.getElementById('serviceBox').style.display=t==='pharmacy'?'none':'block';document.getElementById('medicineBox').style.display=t==='pharmacy'?'block':'none';}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>