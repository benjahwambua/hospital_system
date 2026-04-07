<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$maternityId = max(0, (int)($_GET['id'] ?? 0));
$fromPatientId = max(0, (int)($_GET['patient_id'] ?? 0));
$fromAppointmentId = max(0, (int)($_GET['appointment_id'] ?? 0));
$printMode = isset($_GET['print']) && $_GET['print'] === '1';

$success = '';
$error = '';
$recordedData = null;

if ($maternityId === 0) {
    if ($fromPatientId > 0) {
        $stmt = $conn->prepare('SELECT id FROM maternity WHERE patient_id = ? LIMIT 1');
        $stmt->bind_param('i', $fromPatientId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $maternityId = (int)$row['id'];
        }
    } elseif ($fromAppointmentId > 0) {
        $stmt = $conn->prepare('SELECT m.id FROM maternity m JOIN appointments a ON m.patient_id = a.patient_id WHERE a.id = ? LIMIT 1');
        $stmt->bind_param('i', $fromAppointmentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $maternityId = (int)$row['id'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $postedToken)) {
        $error = 'Security token mismatch. Please refresh and try again.';
    } else {
        $conn->begin_transaction();
        try {
            $patientId = max(0, (int)($_POST['patient_id'] ?? 0));
            $actionType = $_POST['action'];
            $visitType = trim((string)($_POST['visit_type'] ?? 'ANC'));
            $bp = trim((string)($_POST['bp'] ?? ''));
            $temp = trim((string)($_POST['temp'] ?? ''));
            $pulse = trim((string)($_POST['pulse'] ?? ''));
            $weight = trim((string)($_POST['weight'] ?? ''));
            $fhr = trim((string)($_POST['fhr'] ?? ''));
            $cervix = trim((string)($_POST['cervix'] ?? ''));
            $membrane = trim((string)($_POST['membrane'] ?? ''));
            $notes = trim((string)($_POST['notes'] ?? ''));

            $drugNames = array_filter($_POST['drug_name'] ?? []);
            $serviceNames = array_filter($_POST['service_name'] ?? []);

            $drugText = !empty($drugNames) ? implode(', ', $drugNames) : 'None';
            $serviceText = !empty($serviceNames) ? implode(', ', $serviceNames) : 'None';
            $prescriptionSummary = 'Drugs: ' . $drugText . ' | Procedures: ' . $serviceText;

            $deliverySummary = '';
            if ($visitType === 'Labour' && !empty($_POST['delivery_outcome'])) {
                $deliverySummary = sprintf(
                    "\n[DELIVERY RECORD: Outcome: %s | Mode: %s | Baby: %s, %skg]",
                    trim((string)($_POST['delivery_outcome'] ?? '')),
                    trim((string)($_POST['delivery_mode'] ?? '')),
                    trim((string)($_POST['baby_gender'] ?? '')),
                    trim((string)($_POST['baby_weight'] ?? ''))
                );
            }

            $referralSummary = '';
            if (!empty($_POST['referral_location'])) {
                $referralSummary = sprintf(
                    "\n[REFERRAL: To %s | Reason: %s]",
                    trim((string)($_POST['referral_location'] ?? '')),
                    trim((string)($_POST['referral_reason'] ?? ''))
                );
            }

            $finalNotes = $notes . $deliverySummary . $referralSummary;

            $stmt = $conn->prepare('INSERT INTO maternity_visits (maternity_id, visit_type, bp, temp, pulse, weight, fetal_heart_rate, cervix, membrane_status, drugs_given, notes, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())');
            $stmt->bind_param('issssssssss', $maternityId, $visitType, $bp, $temp, $pulse, $weight, $fhr, $cervix, $membrane, $prescriptionSummary, $finalNotes);
            $stmt->execute();
            $stmt->close();

            if ($fromAppointmentId > 0) {
                $stmt = $conn->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ?");
                $stmt->bind_param('i', $fromAppointmentId);
                $stmt->execute();
                $stmt->close();
            }

            $appointmentInfo = 'No appointment scheduled';
            if (!empty($_POST['next_appointment_date'])) {
                $nextAppointmentDate = $_POST['next_appointment_date'];
                $appointmentReason = 'Maternity Follow-up (' . $visitType . ')';
                $stmt = $conn->prepare("INSERT INTO appointments (patient_id, appointment_date, reason, status, created_at) VALUES (?, ?, ?, 'Pending', NOW())");
                $stmt->bind_param('iss', $patientId, $nextAppointmentDate, $appointmentReason);
                $stmt->execute();
                $stmt->close();
                $appointmentInfo = date('d M Y, h:i A', strtotime($nextAppointmentDate));
            }

            if ($actionType === 'save_all') {
                $stmt = $conn->prepare("INSERT INTO billing (patient_id, item_name, amount, category, status, created_at) VALUES (?, 'Maternity Consultation/Procedure', 500.00, 'Service', 'Unpaid', NOW())");
                $stmt->bind_param('i', $patientId);
                $stmt->execute();
                $stmt->close();

                if (!empty($_POST['service_id'])) {
                    $stmt = $conn->prepare("INSERT INTO billing (patient_id, item_name, amount, category, status, created_at) VALUES (?, ?, ?, 'Service', 'Unpaid', NOW())");
                    foreach ($_POST['service_id'] as $idx => $serviceId) {
                        if (!empty($serviceId)) {
                            $serviceName = $_POST['service_name'][$idx] ?? 'Service';
                            $servicePrice = (float)($_POST['service_price'][$idx] ?? 0);
                            $stmt->bind_param('isd', $patientId, $serviceName, $servicePrice);
                            $stmt->execute();
                        }
                    }
                    $stmt->close();
                }

                if (!empty($_POST['drug_id'])) {
                    $stmt = $conn->prepare("INSERT INTO billing (patient_id, item_name, amount, category, status, created_at) VALUES (?, ?, ?, 'Pharmacy', 'Unpaid', NOW())");
                    foreach ($_POST['drug_id'] as $idx => $drugId) {
                        if (!empty($drugId)) {
                            $drugName = $_POST['drug_name'][$idx] ?? 'Medicine';
                            $drugPrice = (float)($_POST['drug_price'][$idx] ?? 0);
                            $stmt->bind_param('isd', $patientId, $drugName, $drugPrice);
                            $stmt->execute();
                        }
                    }
                    $stmt->close();
                }
                $success = 'Clinical records saved, consultation charged, and billing posted.';
            } else {
                $success = 'Clinical records saved successfully (No billing created).';
            }

            $recordedData = [
                'vitals' => "BP: {$bp} | Temp: {$temp} | Weight: {$weight}",
                'procedures' => $serviceText,
                'medicines' => $drugText,
                'notes' => $finalNotes,
                'appointment' => $appointmentInfo,
            ];

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$ancList = $conn->query('SELECT m.id as mid, p.full_name, m.anc_number FROM maternity m JOIN patients p ON p.id = m.patient_id ORDER BY p.full_name ASC');
$record = null;
$previousVisit = null;
$nextAppointment = null;
$recentVisits = null;
if ($maternityId > 0) {
    $stmt = $conn->prepare('SELECT m.*, p.id as pid, p.full_name, p.patient_number FROM maternity m JOIN patients p ON p.id = m.patient_id WHERE m.id = ? LIMIT 1');
    $stmt->bind_param('i', $maternityId);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare('SELECT * FROM maternity_visits WHERE maternity_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->bind_param('i', $maternityId);
    $stmt->execute();
    $previousVisit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($record) {
        $stmt = $conn->prepare('SELECT appointment_date FROM appointments WHERE patient_id = ? AND appointment_date >= NOW() ORDER BY appointment_date ASC LIMIT 1');
        $stmt->bind_param('i', $record['pid']);
        $stmt->execute();
        $nextAppointment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare('SELECT * FROM maternity_visits WHERE maternity_id = ? ORDER BY created_at DESC LIMIT 10');
        $stmt->bind_param('i', $maternityId);
        $stmt->execute();
        $recentVisits = $stmt->get_result();
        $stmt->close();
    }
}

$services = $conn->query('SELECT id, service_name, price FROM services_master ORDER BY service_name ASC');
$drugs = $conn->query('SELECT id, drug_name, selling_price FROM pharmacy_stock ORDER BY drug_name ASC');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.main-body{background:#f0f2f5;padding:32px 20px;min-height:100vh}.container-fixed{max-width:1000px;margin:0 auto}.v-section{background:#fff;border:1px solid #d1d9e0;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.05)}.v-header{background:#004a99;color:#fff;padding:12px 20px;font-weight:600;border-radius:7px 7px 0 0}.v-body{padding:25px}.grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:15px}.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px}label{font-size:11px;font-weight:700;color:#555;display:block;margin-bottom:5px;text-transform:uppercase}.form-control{width:100%;padding:10px;border:1px solid #ced4da;border-radius:5px;font-size:14px}.summary-box{background:#e6fffa;border:1px solid #b2f5ea;padding:15px;border-radius:8px;margin-bottom:20px}.billing-row{background:#f8f9fa;padding:15px;border-radius:8px;border:1px solid #eee;position:relative;margin-bottom:10px}.action-footer{display:flex;gap:10px;margin-top:20px;justify-content:flex-end}.btn-small{border:none;padding:10px 22px;border-radius:5px;font-weight:700;font-size:13px;cursor:pointer}.btn-finalize{background:#d9534f;color:#fff}.btn-save-only{background:#004a99;color:#fff}.btn-add{background:#28a745;color:#fff;padding:5px 10px;font-size:11px;margin-bottom:10px;border-radius:4px;border:none;cursor:pointer}.btn-remove{background:#dc3545;color:#fff;border:none;padding:2px 8px;border-radius:4px;cursor:pointer;position:absolute;top:5px;right:5px;font-size:10px}.search-area{background:#fff;padding:20px;border-radius:8px;border-top:4px solid #ffc107;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.05)}.apt-section{background:#e7f5ff;border:1px dashed #228be6;padding:15px;border-radius:8px;margin-top:15px}.ref-section{background:#fff8f1;border:1px solid #ffe0b2;padding:15px;border-radius:8px;margin-top:15px;border-left:5px solid #f57c00}.print-tools{display:flex;gap:10px;justify-content:flex-end;margin-bottom:12px}.btn-print{background:#343a40;color:#fff;border:none;padding:8px 14px;border-radius:5px;cursor:pointer;text-decoration:none;display:inline-block}
@media print{.search-area,.action-footer,.btn-add,.btn-remove,.print-tools{display:none!important}.main-body{padding:0;background:#fff}}
</style>
<div class="main-body"><div class="container-fixed">
<?php if ($success): ?><div class="summary-box"><strong>✅ <?= htmlspecialchars($success) ?></strong><?php if ($recordedData): ?><div style="margin-top:8px;font-size:13px"><strong>Vitals:</strong> <?= htmlspecialchars($recordedData['vitals']) ?><br><strong>Procedures:</strong> <?= htmlspecialchars($recordedData['procedures']) ?><br><strong>Medicines:</strong> <?= htmlspecialchars($recordedData['medicines']) ?><br><strong>Appointment:</strong> <?= htmlspecialchars($recordedData['appointment']) ?><br><strong>Notes:</strong><br><i><?= nl2br(htmlspecialchars($recordedData['notes'])) ?></i></div><?php endif; ?></div><?php endif; ?>
<?php if ($error): ?><div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:8px;margin-bottom:20px;border:1px solid #f5c6cb;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="search-area"><label>Select Registered ANC Patient</label><select class="form-control" onchange="if(this.value) window.location.href='?id='+this.value"><option value="">-- Start typing patient name or ANC number --</option><?php if($ancList): while($row=$ancList->fetch_assoc()): ?><option value="<?= (int)$row['mid'] ?>" <?= ($maternityId===(int)$row['mid'])?'selected':'' ?>><?= htmlspecialchars($row['full_name']) ?> (ANC: <?= htmlspecialchars($row['anc_number']) ?>)</option><?php endwhile; endif; ?></select></div>
<?php if ($record): ?>
<div class="print-tools"><a class="btn-print" href="?id=<?= (int)$maternityId ?>&print=1" target="_blank" rel="noopener">🖨️ Print Summary</a><a class="btn-print" href="/hospital_system/patients/patient_dashboard.php?id=<?= (int)$record['pid'] ?>" style="background:#007bff;">↩ Back to Patient Dashboard</a></div>
<div class="v-section" style="border-left:6px solid #004a99;"><div class="v-body" style="display:flex;justify-content:space-between;padding:15px 25px;"><div><h2 style="margin:0;color:#004a99"><?= htmlspecialchars($record['full_name']) ?></h2><small>ID: <?= htmlspecialchars($record['patient_number']) ?> | ANC No: <?= htmlspecialchars($record['anc_number']) ?></small></div><div style="text-align:right;"><label>Expected Delivery</label><span style="font-size:18px;font-weight:800;color:#d9534f;"><?= !empty($record['expected_delivery']) ? date('d M Y', strtotime($record['expected_delivery'])) : 'N/A' ?></span></div></div></div>
<form method="post" id="maternityForm"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="patient_id" value="<?= (int)$record['pid'] ?>">
<div class="v-section"><div class="v-header">I. CLINICAL ASSESSMENT & VITALS</div><div class="v-body"><div class="grid-4"><div><label>Visit Type</label><select name="visit_type" id="visit_type_select" class="form-control" onchange="toggleDeliverySection()"><option>ANC</option><option>Labour</option><option>PNC</option></select></div><div><label>BP (mmHg)</label><input name="bp" id="bp_input" class="form-control" required placeholder="120/80" oninput="checkBP(this.value)"></div><div><label>Temp (°C)</label><input name="temp" class="form-control" placeholder="36.5"></div><div><label>Pulse (bpm)</label><input name="pulse" class="form-control"></div></div><div class="grid-4"><div><label>Weight (kg)</label><input name="weight" class="form-control"></div><div><label>Fetal Heart (bpm)</label><input name="fhr" class="form-control"></div><div><label>Cervix (cm)</label><input name="cervix" class="form-control"></div><div><label>Membranes</label><input name="membrane" class="form-control"></div></div><label>Clinical Observations / Progress Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div></div>
<div id="delivery_outcome_section" style="display:none;background:#fff0f6;border:1px solid #ffdeeb;padding:20px;border-radius:8px;margin-bottom:20px;border-left:5px solid #f06292;"><div style="color:#f06292;font-weight:800;font-size:14px;margin-bottom:15px;">👶 BIRTH & DELIVERY OUTCOME</div><div class="grid-4"><div><label>Mode</label><select name="delivery_mode" class="form-control"><option>SVD</option><option>C-Section</option><option>Vacuum</option></select></div><div><label>Outcome</label><select name="delivery_outcome" class="form-control"><option>Live Birth</option><option>Still Birth</option></select></div><div><label>Baby Gender</label><select name="baby_gender" class="form-control"><option>Male</option><option>Female</option></select></div><div><label>Weight (kg)</label><input type="number" step="0.01" name="baby_weight" class="form-control"></div></div></div>
<div class="v-section"><div class="v-header">II. PRESCRIPTIONS, SERVICES & REFERRALS</div><div class="v-body"><div class="grid-2"><div><label>Services / Procedures</label><button type="button" class="btn-add" onclick="addServiceRow()">+ Add Service</button><div id="services_container"><div class="billing-row"><input list="service_options" class="form-control" onchange="updateServiceData(this)" placeholder="Search service..."><input type="hidden" name="service_id[]" class="srv_id"><input type="hidden" name="service_name[]" class="srv_name_hidden"><label style="margin-top:5px;">Price</label><input name="service_price[]" class="form-control srv_p" readonly style="background:#e9ecef"></div></div></div><div><label>Prescribed Drugs</label><button type="button" class="btn-add" onclick="addDrugRow()">+ Add Medicine</button><div id="drugs_container"><div class="billing-row"><input list="drug_options" class="form-control" onchange="updateDrugData(this)" placeholder="Search drug..."><input type="hidden" name="drug_id[]" class="drg_id"><input type="hidden" name="drug_name[]" class="drg_name_hidden"><label style="margin-top:5px;">Price</label><input name="drug_price[]" class="form-control drg_p" readonly style="background:#e9ecef"></div></div></div></div>
<datalist id="service_options"><?php if($services): $services->data_seek(0); while($s=$services->fetch_assoc()): ?><option value="<?= htmlspecialchars($s['service_name']) ?>" data-id="<?= (int)$s['id'] ?>" data-price="<?= htmlspecialchars((string)$s['price']) ?>"></option><?php endwhile; endif; ?></datalist>
<datalist id="drug_options"><?php if($drugs): $drugs->data_seek(0); while($d=$drugs->fetch_assoc()): ?><option value="<?= htmlspecialchars($d['drug_name']) ?>" data-id="<?= (int)$d['id'] ?>" data-price="<?= htmlspecialchars((string)$d['selling_price']) ?>"></option><?php endwhile; endif; ?></datalist>
<div class="ref-section"><label style="color:#f57c00;">🚩 External Referral (Optional)</label><div class="grid-2"><input name="referral_location" class="form-control" placeholder="Facility Name"><input name="referral_reason" class="form-control" placeholder="Reason for referral"></div></div>
<div class="apt-section"><label style="color:#1864ab;">📅 Schedule Next Appointment</label><input type="datetime-local" name="next_appointment_date" class="form-control" style="max-width:320px;"></div>
<div class="action-footer"><button type="submit" name="action" value="save_only" class="btn-small btn-save-only">💾 Save Record</button><button type="submit" name="action" value="save_all" class="btn-small btn-finalize">💰 Post Bill</button></div>
</div></div></form>
<div class="v-section"><div class="v-header" style="background:#546e7a;">III. PATIENT HISTORY & FOLLOW-UP</div><div class="v-body"><div class="grid-2"><div><label>Last Visit Summary</label><?php if($previousVisit): ?><div style="background:#fff9db;border:1px solid #ffe066;padding:15px;border-radius:6px;"><b>BP:</b> <?= htmlspecialchars($previousVisit['bp']) ?> | <b>Weight:</b> <?= htmlspecialchars($previousVisit['weight']) ?> kg | <b>FHR:</b> <?= htmlspecialchars($previousVisit['fetal_heart_rate']) ?> bpm<br><b>Procedures/Drugs:</b> <?= htmlspecialchars($previousVisit['drugs_given']) ?><br><b>Clinical Notes:</b><br><span style="color:#555;"><?= nl2br(htmlspecialchars($previousVisit['notes'])) ?></span></div><?php else: ?><p style="color:#999;">No previous history found.</p><?php endif; ?></div><div><label>Next Appointment Scheduled</label><div class="apt-section" style="margin-top:0;border-style:solid;background:#f1f3f5;border-color:#dee2e6;"><?php if($nextAppointment): ?><h3 style="margin:0;color:#1864ab;"><?= date('d M Y', strtotime($nextAppointment['appointment_date'])) ?></h3><small><?= date('h:i A', strtotime($nextAppointment['appointment_date'])) ?></small><?php else: ?><p style="margin:0;color:#868e96;">No upcoming appointments.</p><?php endif; ?></div></div></div>
<?php if($recentVisits && $recentVisits->num_rows>0): ?><div style="margin-top:18px;"><label>Recent Maternity Visits</label><table style="width:100%;border-collapse:collapse;"><thead><tr style="background:#eef3f8;"><th style="padding:10px;text-align:left;">Date</th><th style="padding:10px;text-align:left;">Type</th><th style="padding:10px;text-align:left;">Vitals</th><th style="padding:10px;text-align:left;">Procedures / Medicines</th></tr></thead><tbody><?php while($rv=$recentVisits->fetch_assoc()): ?><tr><td style="padding:10px;border-bottom:1px solid #eee;"><?= date('d M Y H:i', strtotime($rv['created_at'])) ?></td><td style="padding:10px;border-bottom:1px solid #eee;"><?= htmlspecialchars($rv['visit_type']) ?></td><td style="padding:10px;border-bottom:1px solid #eee;">BP <?= htmlspecialchars($rv['bp']) ?> | Temp <?= htmlspecialchars($rv['temp']) ?> | Pulse <?= htmlspecialchars($rv['pulse']) ?></td><td style="padding:10px;border-bottom:1px solid #eee;"><?= htmlspecialchars($rv['drugs_given']) ?></td></tr><?php endwhile; ?></tbody></table></div><?php endif; ?>
</div></div>
<?php endif; ?>
</div></div>
<script>
function checkBP(val){const parts=val.split('/');if(parts.length===2){const sys=parseInt(parts[0]);const dia=parseInt(parts[1]);const input=document.getElementById('bp_input');if(sys>=140||dia>=90){input.style.border='2px solid #d9534f';input.style.background='#fff5f5';}else{input.style.border='';input.style.background='';}}}
function toggleDeliverySection(){const visitType=document.getElementById('visit_type_select').value;document.getElementById('delivery_outcome_section').style.display=(visitType==='Labour')?'block':'none';}
function addServiceRow(){const c=document.getElementById('services_container');const d=document.createElement('div');d.className='billing-row';d.innerHTML='<button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button><input list="service_options" class="form-control" onchange="updateServiceData(this)" placeholder="Search service..."><input type="hidden" name="service_id[]" class="srv_id"><input type="hidden" name="service_name[]" class="srv_name_hidden"><label style="margin-top:5px;">Price</label><input name="service_price[]" class="form-control srv_p" readonly style="background:#e9ecef">';c.appendChild(d);}
function addDrugRow(){const c=document.getElementById('drugs_container');const d=document.createElement('div');d.className='billing-row';d.innerHTML='<button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button><input list="drug_options" class="form-control" onchange="updateDrugData(this)" placeholder="Search drug..."><input type="hidden" name="drug_id[]" class="drg_id"><input type="hidden" name="drug_name[]" class="drg_name_hidden"><label style="margin-top:5px;">Price</label><input name="drug_price[]" class="form-control drg_p" readonly style="background:#e9ecef">';c.appendChild(d);}
function updateServiceData(inputEl){const p=inputEl.parentElement;const val=inputEl.value;const opts=document.getElementById('service_options').options;for(let i=0;i<opts.length;i++){if(opts[i].value===val){p.querySelector('.srv_id').value=opts[i].getAttribute('data-id');p.querySelector('.srv_name_hidden').value=val;p.querySelector('.srv_p').value=opts[i].getAttribute('data-price');break;}}}
function updateDrugData(inputEl){const p=inputEl.parentElement;const val=inputEl.value;const opts=document.getElementById('drug_options').options;for(let i=0;i<opts.length;i++){if(opts[i].value===val){p.querySelector('.drg_id').value=opts[i].getAttribute('data-id');p.querySelector('.drg_name_hidden').value=val;p.querySelector('.drg_p').value=opts[i].getAttribute('data-price');break;}}}
<?php if ($printMode): ?>window.print();<?php endif; ?>
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
