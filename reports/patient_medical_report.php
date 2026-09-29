<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

$patient_id = max(0, (int)($_GET['id'] ?? 0));
if ($patient_id <= 0) { http_response_code(400); exit('Invalid patient ID.'); }

function report_table_exists(mysqli $conn, string $table): bool {
    $safe = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '{$safe}'");
    return $r && $r->num_rows > 0;
}
function report_column_exists(mysqli $conn, string $table, string $column): bool {
    if (!report_table_exists($conn, $table)) return false;
    $t = $conn->real_escape_string($table); $c = $conn->real_escape_string($column);
    $r = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    return $r && $r->num_rows > 0;
}
function report_rows(mysqli $conn, string $sql): array {
    $r = $conn->query($sql);
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}
function report_e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function report_date($value): string {
    if (!$value) return '—';
    $ts = strtotime((string)$value);
    return $ts ? date('d M Y H:i', $ts) : report_e($value);
}

$stmt = $conn->prepare("SELECT p.*, u.full_name AS doctor_name, u.specialization FROM patients p LEFT JOIN users u ON u.id=p.doctor_id WHERE p.id=? LIMIT 1");
$stmt->bind_param('i', $patient_id); $stmt->execute(); $patient = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$patient) { http_response_code(404); exit('Patient not found.'); }

$hasVisits = report_table_exists($conn, 'visits');
$hasVisitEncounters = report_column_exists($conn, 'encounters', 'visit_id');
$hasVisitServices = report_column_exists($conn, 'patient_services', 'visit_id');
$hasVisitPrescriptions = report_column_exists($conn, 'prescriptions', 'visit_id');
$hasVisitVitals = report_column_exists($conn, 'vitals', 'visit_id');
$hasSpo2 = report_column_exists($conn, 'vitals', 'spo2');

$activeVisit = null;
if ($hasVisits) {
    $s=$conn->prepare("SELECT id,visit_number,visit_type,clinic_category,visit_date,visit_time,status FROM visits WHERE patient_id=? AND status IN ('Open','In Progress') ORDER BY visit_date DESC,visit_time DESC,id DESC LIMIT 1");
    $s->bind_param('i',$patient_id); $s->execute(); $activeVisit=$s->get_result()->fetch_assoc(); $s->close();
}
$visitCondition = $activeVisit ? " AND visit_id=".(int)$activeVisit['id'] : '';

$encounters = report_rows($conn, "SELECT e.*, u.full_name AS doctor_name FROM encounters e LEFT JOIN users u ON u.id=e.doctor_id WHERE e.patient_id={$patient_id}".($activeVisit && $hasVisitEncounters ? " AND e.visit_id=".(int)$activeVisit['id'] : '')." ORDER BY e.created_at DESC,e.id DESC");
$allVisits = $hasVisits ? report_rows($conn,"SELECT visit_number,visit_date,visit_time,visit_type,clinic_category,status FROM visits WHERE patient_id={$patient_id} ORDER BY visit_date DESC,visit_time DESC,id DESC LIMIT 30") : [];

$vitalSql="SELECT * FROM vitals WHERE patient_id={$patient_id}";
if ($activeVisit && $hasVisitVitals) $vitalSql.=" AND visit_id=".(int)$activeVisit['id'];
$vitalSql.=" ORDER BY id DESC";
$vitals=report_rows($conn,$vitalSql);

$serviceSql="SELECT ps.*,sm.service_name,sm.category AS service_category FROM patient_services ps LEFT JOIN services_master sm ON sm.id=ps.service_id WHERE ps.patient_id={$patient_id}";
if($activeVisit && $hasVisitServices)$serviceSql.=" AND ps.visit_id=".(int)$activeVisit['id'];
$serviceSql.=" ORDER BY ps.created_at DESC,ps.id DESC";
$services=report_rows($conn,$serviceSql);

$prescriptionSql="SELECT pr.*,ps.drug_name FROM prescriptions pr LEFT JOIN pharmacy_stock ps ON ps.id=pr.medicine_id WHERE pr.patient_id={$patient_id}";
if($activeVisit && $hasVisitPrescriptions)$prescriptionSql.=" AND pr.visit_id=".(int)$activeVisit['id'];
$prescriptionSql.=" ORDER BY pr.created_at DESC,pr.id DESC";
$prescriptions=report_rows($conn,$prescriptionSql);

$labRadiology=[];
foreach($services as $svc){
    $cat=strtolower((string)($svc['category'] ?? $svc['service_category'] ?? ''));
    if(in_array($cat,['lab','radiology'],true))$labRadiology[]=$svc;
}

$admissions=report_table_exists($conn,'admissions') ? report_rows($conn,"SELECT a.* ,u.full_name AS doctor_name FROM admissions a LEFT JOIN users u ON u.id=a.attending_doctor WHERE a.patient_id={$patient_id} ORDER BY a.admission_date DESC,a.id DESC") : [];
$appointments=report_table_exists($conn,'appointments') ? report_rows($conn,"SELECT a.*,u.full_name AS doctor_name FROM appointments a LEFT JOIN users u ON u.id=a.doctor_id WHERE a.patient_id={$patient_id} ORDER BY a.appointment_date DESC,a.appointment_time DESC,a.id DESC LIMIT 30") : [];

$maternity=null;$maternityVisits=[];$deliveries=[];$maternityAdmission=null;
if(report_table_exists($conn,'maternity')){
    $s=$conn->prepare("SELECT * FROM maternity WHERE patient_id=? ORDER BY id DESC LIMIT 1");$s->bind_param('i',$patient_id);$s->execute();$maternity=$s->get_result()->fetch_assoc();$s->close();
    if($maternity){
        $mid=(int)$maternity['id'];
        if(report_table_exists($conn,'maternity_visits'))$maternityVisits=report_rows($conn,"SELECT * FROM maternity_visits WHERE maternity_id={$mid} ORDER BY created_at DESC");
        if(report_table_exists($conn,'maternity_delivery'))$deliveries=report_rows($conn,"SELECT d.*,b.gender AS baby_gender,b.weight AS baby_weight,b.apgar,b.alive FROM maternity_delivery d LEFT JOIN maternity_baby b ON b.maternity_id=d.maternity_id AND b.created_at>=d.created_at WHERE d.maternity_id={$mid} ORDER BY d.created_at DESC");
        if(report_table_exists($conn,'maternity_admissions'))$maternityAdmission=report_rows($conn,"SELECT ma.*,a.admission_date,a.ward_name,a.bed_number,a.status AS clinical_status FROM maternity_admissions ma LEFT JOIN admissions a ON a.id=ma.admission_id WHERE ma.patient_id={$patient_id} ORDER BY ma.id DESC LIMIT 1")[0] ?? null;
    }
}

$billingItems=[];
$invoices=[];
$totalCharges=0;$totalPaid=0;$approvedRefunds=0;
if(report_table_exists($conn,'invoices') && report_table_exists($conn,'invoice_items')){
    $invoices=report_rows($conn,"SELECT i.*,v.visit_number FROM invoices i LEFT JOIN visits v ON v.id=i.visit_id WHERE i.patient_id={$patient_id} AND LOWER(COALESCE(i.status,'')) NOT IN ('cancelled','canceled','void') ORDER BY i.created_at DESC,i.id DESC");
    $billingItems=report_rows($conn,"SELECT ii.*,i.created_at AS invoice_date,i.status AS invoice_status,i.visit_id,v.visit_number FROM invoice_items ii INNER JOIN invoices i ON i.id=ii.invoice_id LEFT JOIN visits v ON v.id=i.visit_id WHERE i.patient_id={$patient_id} AND LOWER(COALESCE(i.status,'')) NOT IN ('cancelled','canceled','void') ORDER BY i.created_at DESC,ii.id DESC");
    foreach($invoices as $inv)$totalCharges+=(float)($inv['total'] ?? 0);
}
if(report_table_exists($conn,'payments')){
    $paid=report_rows($conn,"SELECT COALESCE(SUM(p.amount),0) total_paid FROM payments p INNER JOIN invoices i ON i.id=p.invoice_id WHERE i.patient_id={$patient_id}");
    $totalPaid=(float)($paid[0]['total_paid']??0);
}
if(report_table_exists($conn,'refunds')){
    $ref=report_rows($conn,"SELECT COALESCE(SUM(r.amount),0) total_refunded FROM refunds r INNER JOIN payments p ON p.id=r.payment_id INNER JOIN invoices i ON i.id=p.invoice_id WHERE i.patient_id={$patient_id} AND LOWER(COALESCE(r.status,''))='approved'");
    $approvedRefunds=(float)($ref[0]['total_refunded']??0);
}
$netPaid=max($totalPaid-$approvedRefunds,0);
$balance=max($totalCharges-$netPaid,0);

$historySections=[];
foreach($encounters as $e){
    $historySections[]=[
        'date'=>$e['created_at']??null,'title'=>'Clinical Encounter','visit'=>$e['visit_id']??'','doctor'=>$e['doctor_name']??'',
        'items'=>[
            'Presenting Complaint'=>$e['presenting_complaint']??'',
            'History of Present Illness'=>$e['hpc']??'',
            'Medical History'=>$e['medical_history']??'',
            'Surgical History'=>$e['surgical_history']??'',
            'Family History'=>$e['family_history']??'',
            'Drug History'=>$e['drug_history']??'',
            'Allergies'=>$e['allergies']??'',
            'Social History'=>$e['social_history']??'',
            'Review of Systems'=>$e['review_systems']??'',
            'Physical Examination'=>$e['physical_exam']??'',
            'Diagnosis'=>$e['diagnosis']??'',
            'Differential Diagnosis'=>$e['differential_diagnosis']??'',
            'Investigations'=>$e['investigations']??'',
            'Management Plan'=>$e['management_plan']??'',
            'Prescription Instructions'=>$e['prescription_instructions']??'',
            'Doctor Notes'=>$e['doctor_notes']??''
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Medical Report - <?=report_e($patient['patient_number'])?></title>
<style>
*{box-sizing:border-box}body{margin:0;background:#eef2f6;color:#25324a;font-family:"Segoe UI",Arial,sans-serif;font-size:11px}.report{width:210mm;margin:18px auto;background:#fff;padding:14mm;box-shadow:0 2px 18px rgba(0,0,0,.1)}.brand{display:flex;justify-content:space-between;align-items:center;border-bottom:3px solid #075b9d;padding-bottom:12px;margin-bottom:14px}.brand img{max-height:58px}.brand h1{font-size:19px;color:#075b9d;margin:0 0 3px}.brand p{margin:2px 0;color:#667085;font-size:10px;text-align:right}.report-title{text-align:center;font-size:17px;font-weight:800;letter-spacing:.8px;margin:12px 0 15px;text-transform:uppercase}.identity{display:grid;grid-template-columns:1.5fr 1fr 1fr;background:#f7fafc;border:1px solid #dce5ee;border-radius:8px;padding:12px;margin-bottom:14px}.identity .label,.metric .label,.section-label{font-size:8px;text-transform:uppercase;font-weight:800;letter-spacing:.6px;color:#718096}.identity .value{font-size:12px;font-weight:700;margin-top:3px}.identity .primary{font-size:16px}.metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:7px;margin-bottom:14px}.metric{border:1px solid #dce5ee;border-radius:7px;padding:9px}.metric .value{font-size:14px;font-weight:800;margin-top:3px;color:#075b9d}.metric.balance .value{color:#b42318}.section{margin:13px 0;border:1px solid #dce5ee;border-radius:8px;overflow:hidden;page-break-inside:avoid}.section-head{background:#f1f7fc;border-bottom:1px solid #dce5ee;padding:8px 10px;font-size:11px;font-weight:800;color:#075b9d}.section-body{padding:10px}.grid2{display:grid;grid-template-columns:1fr 1fr;gap:9px}.field{margin-bottom:8px}.field:last-child{margin-bottom:0}.field .section-label{display:block;margin-bottom:3px}.field .text{white-space:pre-line;line-height:1.45}.table{width:100%;border-collapse:collapse}.table th{background:#f5f8fb;text-align:left;font-size:8px;text-transform:uppercase;color:#5f6f82;padding:6px;border-bottom:1px solid #dce5ee}.table td{padding:6px;border-bottom:1px solid #edf1f5;vertical-align:top}.muted{color:#7b8794}.badge{display:inline-block;padding:2px 6px;border-radius:10px;background:#edf4fb;color:#075b9d;font-size:8px;font-weight:800}.empty{padding:12px;text-align:center;color:#8994a3;font-style:italic}.signature{display:flex;justify-content:space-between;gap:30px;margin-top:25px;page-break-inside:avoid}.signbox{width:42%;text-align:center;padding-top:35px;border-top:1px solid #555}.footer{margin-top:15px;border-top:1px solid #dce5ee;padding-top:7px;text-align:center;color:#7b8794;font-size:8px}.no-print{text-align:center;margin:15px}.no-print button{border:0;background:#075b9d;color:#fff;padding:10px 20px;border-radius:7px;font-weight:700}@media print{@page{size:A4 portrait;margin:10mm}body{background:#fff}.report{margin:0;box-shadow:none;width:100%;padding:0}.no-print{display:none}.section{page-break-inside:avoid}.table tr{page-break-inside:avoid}.identity,.metrics{break-inside:avoid}}@media(max-width:800px){.report{width:100%;margin:0;padding:16px}.identity,.metrics,.grid2{grid-template-columns:1fr}.brand{gap:10px;align-items:flex-start}.brand p{text-align:left}}
</style>
</head>
<body>
<div class="report">
<div class="brand"><img src="../assets/img/logo.png" alt="Logo"><div><h1>EMAQURE MEDICAL CENTRE</h1><p>Biashara Street, Mlolongo</p><p>Tel: +254793069565</p></div></div>
<div class="report-title">Comprehensive Patient Medical Report</div>

<div class="identity">
<div><div class="label">Patient Name</div><div class="value primary"><?=report_e($patient['full_name'])?></div><div class="label" style="margin-top:6px">Patient Number</div><div class="value"><?=report_e($patient['patient_number'])?></div></div>
<div><div class="label">Age / Gender</div><div class="value"><?=report_e($patient['age']??'—')?> years / <?=report_e($patient['gender']??'—')?></div><div class="label" style="margin-top:6px">Date of Birth</div><div class="value"><?=report_e($patient['date_of_birth']??'—')?></div></div>
<div><div class="label">Phone / Address</div><div class="value"><?=report_e($patient['phone']??'—')?></div><div class="value muted"><?=report_e($patient['address']??'—')?></div></div>
</div>

<div class="metrics">
<div class="metric"><div class="label">Current Visit</div><div class="value"><?=report_e($activeVisit['visit_number']??'None')?></div></div>
<div class="metric"><div class="label">Visits Recorded</div><div class="value"><?=count($allVisits)?></div></div>
<div class="metric"><div class="label">Current Admission</div><div class="value"><?=($admissions && (($admissions[0]['status']??'')==='Admitted'))?'Admitted':'Outpatient'?></div></div>
<div class="metric balance"><div class="label">Balance Due</div><div class="value">KES <?=number_format($balance,2)?></div></div>
</div>

<div class="section"><div class="section-head">Patient & Care Information</div><div class="section-body grid2">
<div><div class="field"><span class="section-label">Assigned Doctor</span><div class="text">Dr. <?=report_e($patient['doctor_name']??'Not Assigned')?><?=!empty($patient['specialization'])?' — '.report_e($patient['specialization']):''?></div></div><div class="field"><span class="section-label">Clinic / Service Type</span><div class="text"><?=report_e($patient['clinic_category']??'—')?></div></div></div>
<div><div class="field"><span class="section-label">Next of Kin</span><div class="text"><?=report_e($patient['next_of_kin_name']??'—')?><?=!empty($patient['next_of_kin_phone'])?' — '.report_e($patient['next_of_kin_phone']):''?></div></div><div class="field"><span class="section-label">Patient Type</span><div class="text"><?=!empty($patient['is_walkin'])?'Walk-in':'Registered Patient'?></div></div></div>
</div></div>

<?php if($activeVisit):?><div class="section"><div class="section-head">Current Encounter</div><div class="section-body grid2"><div><div class="field"><span class="section-label">Visit</span><div class="text"><?=report_e($activeVisit['visit_number'])?> — <?=report_e($activeVisit['visit_type'])?></div></div><div class="field"><span class="section-label">Department</span><div class="text"><?=report_e($activeVisit['clinic_category'])?></div></div></div><div><div class="field"><span class="section-label">Date / Time</span><div class="text"><?=report_date(($activeVisit['visit_date']??'').' '.($activeVisit['visit_time']??''))?></div></div><div class="field"><span class="section-label">Status</span><div class="text"><span class="badge"><?=report_e($activeVisit['status'])?></span></div></div></div></div></div><?php endif;?>

<div class="section"><div class="section-head">Latest / Recorded Vitals</div><div class="section-body"><?php if($vitals):?><table class="table"><thead><tr><th>Date</th><th>Temperature</th><th>BP</th><th>Weight</th><th>Pulse</th><th>Respiration</th><?php if($hasSpo2):?><th>SpO₂</th><?php endif;?></tr></thead><tbody><?php foreach($vitals as $v):?><tr><td><?=report_date($v['created_at']??null)?></td><td><?=report_e($v['temperature']??'—')?></td><td><?=report_e($v['bp']??'—')?></td><td><?=report_e($v['weight']??'—')?></td><td><?=report_e($v['pulse']??'—')?></td><td><?=report_e($v['respiration']??'—')?></td><?php if($hasSpo2):?><td><?=report_e($v['spo2']??'—')?></td><?php endif;?></tr><?php endforeach;?></tbody></table><?php else:?><div class="empty">No vitals recorded.</div><?php endif;?></div></div>

<div class="section"><div class="section-head">Clinical History, Assessment & Management</div><div class="section-body"><?php if($historySections):foreach($historySections as $h):?><div style="border-bottom:1px solid #e7edf3;padding-bottom:10px;margin-bottom:10px"><div style="display:flex;justify-content:space-between;font-weight:800;color:#075b9d;margin-bottom:8px"><span><?=report_date($h['date'])?></span><span><?=report_e($h['doctor']?'Dr. '.$h['doctor']:'Medical Officer')?></span></div><div class="grid2"><?php foreach($h['items'] as $label=>$value):if(trim((string)$value)!==''):?><div class="field"><span class="section-label"><?=report_e($label)?></span><div class="text"><?=report_e($value)?></div></div><?php endif;endforeach;?></div></div><?php endforeach;else:?><div class="empty">No clinical encounters recorded.</div><?php endif;?></div></div>

<div class="section"><div class="section-head">Services, Investigations & Results</div><div class="section-body"><?php if($services):?><table class="table"><thead><tr><th>Date</th><th>Service / Investigation</th><th>Category</th><th>Status</th><th>Result / Notes</th></tr></thead><tbody><?php foreach($services as $s):?><tr><td><?=report_date($s['created_at']??null)?></td><td><strong><?=report_e($s['service_name']??'Service')?></strong></td><td><?=report_e($s['category']??$s['service_category']??'—')?></td><td><?=report_e($s['status']??'—')?></td><td><?=report_e($s['results']??$s['doctor_notes']??'—')?></td></tr><?php endforeach;?></tbody></table><?php else:?><div class="empty">No services or investigations recorded.</div><?php endif;?></div></div>

<div class="section"><div class="section-head">Prescriptions & Medicines</div><div class="section-body"><?php if($prescriptions):?><table class="table"><thead><tr><th>Date</th><th>Medicine</th><th>Quantity</th><th>Instructions</th><th>Unit Price</th></tr></thead><tbody><?php foreach($prescriptions as $pr):?><tr><td><?=report_date($pr['created_at']??null)?></td><td><strong><?=report_e($pr['drug_name']??'Medicine')?></strong></td><td><?=report_e($pr['quantity']??'—')?></td><td><?=report_e($pr['frequency']??$pr['dosage_instructions']??'—')?></td><td>KES <?=number_format((float)($pr['unit_price']??0),2)?></td></tr><?php endforeach;?></tbody></table><?php else:?><div class="empty">No prescriptions recorded.</div><?php endif;?></div></div>

<div class="section"><div class="section-head">Admissions / Ward / IPD</div><div class="section-body"><?php if($admissions):?><table class="table"><thead><tr><th>Admission Date</th><th>Ward</th><th>Bed</th><th>Doctor</th><th>Status</th><th>Reason / Notes</th></tr></thead><tbody><?php foreach($admissions as $a):?><tr><td><?=report_date($a['admission_date']??null)?></td><td><?=report_e($a['ward_name']??'—')?></td><td><?=report_e($a['bed_number']??'—')?></td><td><?=report_e($a['doctor_name']??$a['attending_doctor']??'—')?></td><td><?=report_e($a['status']??'—')?></td><td><?=report_e($a['reason']??$a['discharge_notes']??$a['follow_up']??'—')?></td></tr><?php endforeach;?></tbody></table><?php else:?><div class="empty">No admission records.</div><?php endif;?></div></div>

<?php if($maternity):?><div class="section"><div class="section-head">Maternity / ANC / PNC</div><div class="section-body">
<div class="grid2"><?php foreach($maternity as $k=>$v):if(!in_array($k,['id','patient_id'],true)&&$v!==''&&$v!==null):?><div class="field"><span class="section-label"><?=report_e(ucwords(str_replace('_',' ',$k)))?></span><div class="text"><?=report_e($v)?></div></div><?php endif;endforeach;?></div>
<?php if($maternityVisits):?><h4 style="margin:12px 0 6px;color:#075b9d">Maternity Visits</h4><table class="table"><thead><tr><?php foreach(array_keys($maternityVisits[0]) as $k):if(!in_array($k,['id','maternity_id'],true)):?><th><?=report_e(ucwords(str_replace('_',' ',$k)))?></th><?php endif;endforeach;?></tr></thead><tbody><?php foreach($maternityVisits as $mv):?><tr><?php foreach($mv as $k=>$v):if(!in_array($k,['id','maternity_id'],true)):?><td><?=report_e($v)?></td><?php endif;endforeach;?></tr><?php endforeach;?></tbody></table><?php endif;?>
<?php if($deliveries):?><h4 style="margin:12px 0 6px;color:#075b9d">Delivery Records</h4><table class="table"><thead><tr><th>Date</th><th>Baby Gender</th><th>Weight</th><th>Apgar</th><th>Alive</th></tr></thead><tbody><?php foreach($deliveries as $d):?><tr><td><?=report_date($d['created_at']??null)?></td><td><?=report_e($d['baby_gender']??'—')?></td><td><?=report_e($d['baby_weight']??'—')?></td><td><?=report_e($d['apgar']??'—')?></td><td><?=isset($d['alive'])?(($d['alive'])?'Yes':'No'):'—'?></td></tr><?php endforeach;?></tbody></table><?php endif;?>
<?php if($maternityAdmission):?><div class="field" style="margin-top:10px"><span class="section-label">Maternity Admission</span><div class="text"><?=report_e($maternityAdmission['ward_name']??'—')?> / Bed <?=report_e($maternityAdmission['bed_number']??'—')?> — <?=report_e($maternityAdmission['clinical_status']??'—')?></div></div><?php endif;?>
</div></div><?php endif;?>

<div class="section"><div class="section-head">Appointments & Visit History</div><div class="section-body"><?php if($appointments):?><table class="table"><thead><tr><th>Date</th><th>Time</th><th>Doctor</th><th>Reason</th><th>Status</th></tr></thead><tbody><?php foreach($appointments as $a):?><tr><td><?=report_e($a['appointment_date']??'—')?></td><td><?=report_e($a['appointment_time']??'—')?></td><td><?=report_e($a['doctor_name']??'—')?></td><td><?=report_e($a['reason']??'—')?></td><td><?=report_e($a['status']??'—')?></td></tr><?php endforeach;?></tbody></table><?php endif;?>
<?php if($allVisits):?><h4 style="margin:12px 0 6px;color:#075b9d">Visit History</h4><table class="table"><thead><tr><th>Visit No.</th><th>Date</th><th>Type</th><th>Department</th><th>Status</th></tr></thead><tbody><?php foreach($allVisits as $v):?><tr><td><?=report_e($v['visit_number'])?></td><td><?=report_e($v['visit_date'])?></td><td><?=report_e($v['visit_type'])?></td><td><?=report_e($v['clinic_category'])?></td><td><?=report_e($v['status'])?></td></tr><?php endforeach;?></tbody></table><?php endif;?><?php if(!$appointments&&!$allVisits):?><div class="empty">No appointment or visit history.</div><?php endif;?></div></div>

<div class="section"><div class="section-head">Billing, Services & Payments</div><div class="section-body">
<div class="metrics" style="margin-bottom:10px"><div class="metric"><div class="label">Charges</div><div class="value">KES <?=number_format($totalCharges,2)?></div></div><div class="metric"><div class="label">Payments</div><div class="value">KES <?=number_format($totalPaid,2)?></div></div><div class="metric"><div class="label">Approved Refunds</div><div class="value">KES <?=number_format($approvedRefunds,2)?></div></div><div class="metric balance"><div class="label">Outstanding</div><div class="value">KES <?=number_format($balance,2)?></div></div></div>
<?php if($billingItems):?><table class="table"><thead><tr><th>Date</th><th>Visit</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead><tbody><?php foreach($billingItems as $b):?><tr><td><?=report_date($b['invoice_date']??null)?></td><td><?=report_e($b['visit_number']??'—')?></td><td><?=report_e($b['description']??'—')?></td><td><?=report_e($b['qty']??$b['quantity']??1)?></td><td>KES <?=number_format((float)($b['unit_price']??$b['price']??0),2)?></td><td>KES <?=number_format((float)($b['total']??0),2)?></td></tr><?php endforeach;?></tbody></table><?php else:?><div class="empty">No billing items recorded.</div><?php endif;?></div></div>

<div class="signature"><div class="signbox">Authorized Medical Officer<br><small>Signature / Date</small></div><div class="signbox">Official Hospital Stamp<br><small>EMAQURE MEDICAL CENTRE</small></div></div>
<div class="footer">Comprehensive medical report generated <?=date('d M Y H:i')?>. This report consolidates information available on the Patient Dashboard.</div>
</div>
<div class="no-print"><button onclick="window.print()">Print Medical Report (A4)</button></div>
</body></html>