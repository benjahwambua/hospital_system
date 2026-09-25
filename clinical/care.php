<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin','doctor','nurse']);

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

$patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$visitId = (int)($_GET['visit_id'] ?? $_POST['visit_id'] ?? 0);
$message = '';

if ($patientId <= 0) {
    header('Location: consultations.php');
    exit;
}

$stmt = $conn->prepare("SELECT p.*, u.full_name AS doctor_name, u.specialization
                        FROM patients p
                        LEFT JOIN users u ON u.id=p.doctor_id
                        WHERE p.id=? LIMIT 1");
$stmt->bind_param('i', $patientId);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    header('Location: consultations.php');
    exit;
}

if ($visitId > 0) {
    $stmt = $conn->prepare("SELECT * FROM visits WHERE id=? AND patient_id=? LIMIT 1");
    $stmt->bind_param('ii', $visitId, $patientId);
    $stmt->execute();
    $visit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$visit) $visitId = 0;
}

if ($visitId <= 0) {
    $message = "<div class='alert alert-warning'>No visit was supplied. Open this patient from the Doctor's Consultation Queue so the same visit is preserved.</div>";
    $visit = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_visit'])) {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $message = "<div class='alert alert-danger'>Invalid security token. Please try again.</div>";
    } elseif ($visitId <= 0) {
        $message = "<div class='alert alert-danger'>No valid visit was selected.</div>";
    } else {
        $pendingLab = 0;
        $pendingRad = 0;
        $pendingPharmacy = 0;

        $q = $conn->prepare("SELECT COUNT(*) AS total FROM patient_services WHERE patient_id=? AND visit_id=? AND category='lab' AND COALESCE(status,'Pending') NOT IN ('Completed','Cancelled')");
        if ($q) { $q->bind_param('ii',$patientId,$visitId); $q->execute(); $pendingLab=(int)($q->get_result()->fetch_assoc()['total']??0); $q->close(); }

        $q = $conn->prepare("SELECT COUNT(*) AS total FROM patient_services WHERE patient_id=? AND visit_id=? AND category='radiology' AND COALESCE(status,'Pending') NOT IN ('Completed','Cancelled')");
        if ($q) { $q->bind_param('ii',$patientId,$visitId); $q->execute(); $pendingRad=(int)($q->get_result()->fetch_assoc()['total']??0); $q->close(); }

        $queueCheck = $conn->query("SHOW TABLES LIKE 'pharmacy_queue'");
        if ($queueCheck && $queueCheck->num_rows > 0) {
            $q = $conn->prepare("SELECT COUNT(*) AS total FROM pharmacy_queue WHERE patient_id=? AND visit_id=? AND status='pending'");
            if ($q) { $q->bind_param('ii',$patientId,$visitId); $q->execute(); $pendingPharmacy=(int)($q->get_result()->fetch_assoc()['total']??0); $q->close(); }
        }

        $pendingTotal = $pendingLab + $pendingRad + $pendingPharmacy;
        $outstanding = 0.0;
        if ($visitId > 0) {
            $invoiceCheck = $conn->prepare("SELECT id, COALESCE(total,0) AS total FROM invoices WHERE visit_id=? ORDER BY id DESC LIMIT 1");
            if ($invoiceCheck) {
                $invoiceCheck->bind_param('i', $visitId);
                $invoiceCheck->execute();
                $invoiceRow = $invoiceCheck->get_result()->fetch_assoc();
                $invoiceCheck->close();
                if ($invoiceRow) {
                    $paidCheck = $conn->prepare("SELECT COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id=?),0) - COALESCE((SELECT SUM(amount) FROM payment_refunds WHERE invoice_id=? AND status='Approved'),0) AS paid");
                    if ($paidCheck) {
                        $invoiceIdForBalance = (int)$invoiceRow['id'];
                        $paidCheck->bind_param('ii', $invoiceIdForBalance, $invoiceIdForBalance);
                        $paidCheck->execute();
                        $paidRow = $paidCheck->get_result()->fetch_assoc();
                        $paidCheck->close();
                        $outstanding = max((float)$invoiceRow['total'] - (float)($paidRow['paid'] ?? 0), 0.0);
                    }
                }
            }
        }

        if ($pendingTotal > 0) {
            $parts = [];
            if ($pendingLab) $parts[] = $pendingLab . ' laboratory order(s)';
            if ($pendingRad) $parts[] = $pendingRad . ' radiology order(s)';
            if ($pendingPharmacy) $parts[] = $pendingPharmacy . ' pharmacy order(s)';
            $message = "<div class='alert alert-warning'><strong>Visit cannot be completed yet.</strong> Pending: " . htmlspecialchars(implode(', ', $parts)) . ". Complete the outstanding department work first.</div>";
        } elseif ($outstanding > 0.009) {
            $message = "<div class='alert alert-warning'><strong>Visit is ready for billing but cannot be completed yet.</strong> Outstanding balance: KES " . number_format($outstanding, 2) . ". Send the patient to Central Cashier for payment, then complete the visit.</div>";
        } else {
            $stmt = $conn->prepare("UPDATE visits SET status='Completed', updated_at=NOW() WHERE id=? AND patient_id=? AND status <> 'Cancelled'");
            if ($stmt && $stmt->bind_param('ii',$visitId,$patientId) && $stmt->execute()) {
                $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Visit completed successfully. All department orders are closed.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Unable to complete the visit.</div>";
            }
            if ($stmt) $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_clinical_care'])) {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $message = "<div class='alert alert-danger'>Invalid security token. Please try again.</div>";
    } elseif (($visit['status'] ?? '') === 'Completed') {
        $message = "<div class='alert alert-warning'>This visit is already completed. Start a new visit before adding another clinical encounter.</div>";
    } else {
        $fields = [
            trim($_POST['presenting_complaint'] ?? ''),
            trim($_POST['hpc'] ?? ''),
            trim($_POST['medical_history'] ?? ''),
            trim($_POST['surgical_history'] ?? ''),
            trim($_POST['family_history'] ?? ''),
            trim($_POST['drug_history'] ?? ''),
            trim($_POST['allergies'] ?? ''),
            trim($_POST['social_history'] ?? ''),
            trim($_POST['review_systems'] ?? ''),
            trim($_POST['physical_exam'] ?? ''),
            trim($_POST['diagnosis'] ?? ''),
            trim($_POST['differential_diagnosis'] ?? ''),
            trim($_POST['investigations'] ?? ''),
            trim($_POST['management_plan'] ?? ''),
            trim($_POST['prescription_instructions'] ?? ''),
            trim($_POST['doctor_notes'] ?? '')
        ];

        if ($visitId > 0 && ensure_encounter_visit_column($conn)) {
            $sql = "INSERT INTO encounters
                (patient_id, visit_id, presenting_complaint, hpc, medical_history, surgical_history,
                 family_history, drug_history, allergies, social_history, review_systems, physical_exam,
                 diagnosis, differential_diagnosis, investigations, management_plan,
                 prescription_instructions, doctor_notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $params = [$patientId, $visitId, ...$fields];
                $stmt->bind_param('iissssssssssssssss', ...$params);
            }
        } else {
            $sql = "INSERT INTO encounters
                (patient_id, presenting_complaint, hpc, medical_history, surgical_history,
                 family_history, drug_history, allergies, social_history, review_systems, physical_exam,
                 diagnosis, differential_diagnosis, investigations, management_plan,
                 prescription_instructions, doctor_notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $params = [$patientId, ...$fields];
                $stmt->bind_param('isssssssssssssssss', ...$params);
            }
        }

        if (!$stmt) {
            $message = "<div class='alert alert-danger'>Unable to prepare clinical record: " . htmlspecialchars($conn->error) . "</div>";
        } elseif (!$stmt->execute()) {
            $message = "<div class='alert alert-danger'>Unable to save clinical record: " . htmlspecialchars($stmt->error) . "</div>";
            $stmt->close();
        } else {
            $stmt->close();
            if ($visitId > 0) {
                $doctorId = (int)($patient['doctor_id'] ?? 0);
                $v = $conn->prepare("UPDATE visits SET status='In Progress', doctor_id=COALESCE(NULLIF(?,0),doctor_id), updated_at=NOW() WHERE id=?");
                if ($v) {
                    $v->bind_param('ii', $doctorId, $visitId);
                    $v->execute();
                    $v->close();
                }
            }
            header("Location: care.php?patient_id={$patientId}&visit_id={$visitId}&saved=1");
            exit;
        }
    }
}

$hasVisitVitals = false;
$vitalColumns = $conn->query("SHOW COLUMNS FROM vitals");
if ($vitalColumns) {
    while ($col = $vitalColumns->fetch_assoc()) {
        if (($col['Field'] ?? '') === 'visit_id') { $hasVisitVitals = true; break; }
    }
}

$tempColumn = 'NULL';
$tempCheck = $conn->query("SHOW COLUMNS FROM vitals");
if ($tempCheck) {
    while ($col = $tempCheck->fetch_assoc()) {
        if (($col['Field'] ?? '') === 'temp') { $tempColumn = 'v.temp'; break; }
        if (($col['Field'] ?? '') === 'temperature') $tempColumn = 'v.temperature';
    }
}

if ($hasVisitVitals && $visitId > 0) {
    $sql = "SELECT v.*, {$tempColumn} AS temp_value
            FROM vitals v WHERE v.visit_id=? ORDER BY v.id DESC LIMIT 1";
    $vstmt = $conn->prepare($sql);
    $vstmt->bind_param('i', $visitId);
    $vstmt->execute();
    $vitals = $vstmt->get_result()->fetch_assoc();
    $vstmt->close();
} else {
    $vstmt = $conn->prepare("SELECT *, {$tempColumn} AS temp_value FROM vitals WHERE patient_id=? ORDER BY id DESC LIMIT 1");
    $vstmt->bind_param('i', $patientId);
    $vstmt->execute();
    $vitals = $vstmt->get_result()->fetch_assoc();
    $vstmt->close();
}

$encounters = null;
if (ensure_encounter_visit_column($conn) && $visitId > 0) {
    $stmt = $conn->prepare("SELECT * FROM encounters WHERE patient_id=? AND visit_id=? ORDER BY id DESC");
    $stmt->bind_param('ii', $patientId, $visitId);
} else {
    $stmt = $conn->prepare("SELECT * FROM encounters WHERE patient_id=? ORDER BY id DESC LIMIT 10");
    $stmt->bind_param('i', $patientId);
}
$stmt->execute();
$encounters = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid pt-4">
    <?php if (isset($_GET['saved'])): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Clinical encounter saved successfully.</div><?php endif; ?>
    <?= $message ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="font-weight-bold text-gray-800 mb-1">Clinical Care</h4>
            <div class="text-muted">Doctor workspace · <?= htmlspecialchars($visit['visit_number'] ?? 'Visit not assigned') ?></div>
        </div>
        <div><a href="orders.php?patient_id=<?= $patientId ?>&visit_id=<?= $visitId ?>" class="btn btn-success mr-2"><i class="fas fa-flask"></i> Orders & Referrals</a><a href="consultations.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> Consultation Queue</a></div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>Patient:</strong><br><?= htmlspecialchars($patient['full_name']) ?><br><small><?= htmlspecialchars($patient['patient_number'] ?? '') ?> · <?= htmlspecialchars($patient['gender'] ?? '') ?> · <?= (int)($patient['age'] ?? 0) ?> yrs</small></div>
                <div class="col-md-4"><strong>Visit:</strong><br><?= htmlspecialchars($visit['visit_number'] ?? 'N/A') ?><br><small><?= htmlspecialchars($visit['visit_type'] ?? 'Outpatient') ?> · <?= htmlspecialchars($visit['clinic_category'] ?? 'General') ?></small></div>
                <div class="col-md-4"><strong>Vitals:</strong><br>
                    BP <?= htmlspecialchars($vitals['bp'] ?? '') ?> · Temp <?= htmlspecialchars($vitals['temp_value'] ?? '') ?> · Pulse <?= htmlspecialchars($vitals['pulse'] ?? '') ?> · Weight <?= htmlspecialchars($vitals['weight'] ?? '') ?>
                </div>
            </div>
        </div>
    </div>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="patient_id" value="<?= $patientId ?>">
        <input type="hidden" name="visit_id" value="<?= $visitId ?>">

        <?php
        $sections = [
            ['History', [
                'presenting_complaint'=>'Presenting Complaint',
                'hpc'=>'History of Presenting Complaint',
                'medical_history'=>'Medical History',
                'surgical_history'=>'Surgical History',
                'family_history'=>'Family History',
                'drug_history'=>'Drug History / Current Medication',
                'allergies'=>'Allergies',
                'social_history'=>'Social History',
                'review_systems'=>'Review of Systems'
            ]],
            ['Examination & Assessment', [
                'physical_exam'=>'Physical Examination',
                'diagnosis'=>'Diagnosis',
                'differential_diagnosis'=>'Differential Diagnosis',
                'investigations'=>'Investigations / Tests Required'
            ]],
            ['Plan', [
                'management_plan'=>'Treatment / Management Plan',
                'prescription_instructions'=>'Prescription Instructions',
                'doctor_notes'=>'Doctor Notes'
            ]]
        ];
        foreach ($sections as $section):
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><h5 class="mb-0 font-weight-bold text-primary"><?= htmlspecialchars($section[0]) ?></h5></div>
            <div class="card-body">
                <div class="row">
                <?php foreach ($section[1] as $name=>$label): ?>
                    <div class="col-md-<?= in_array($name, ['presenting_complaint','diagnosis','differential_diagnosis','investigations','management_plan']) ? '12' : '6' ?> mb-3">
                        <label class="small font-weight-bold"><?= htmlspecialchars($label) ?></label>
                        <textarea name="<?= htmlspecialchars($name) ?>" class="form-control" rows="<?= in_array($name, ['presenting_complaint','diagnosis','management_plan','physical_exam']) ? '3' : '2' ?>"></textarea>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="text-right mb-5">
            <button type="submit" name="save_clinical_care" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Clinical Encounter</button>
        </div>
    </form>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><h5 class="mb-0 font-weight-bold text-primary">Visit Completion</h5></div>
        <div class="card-body">
            <?php
            $pendingLabNow=0; $pendingRadNow=0; $pendingPharmacyNow=0;
            if($visitId>0){
                $q=$conn->prepare("SELECT COUNT(*) total FROM patient_services WHERE patient_id=? AND visit_id=? AND category='lab' AND COALESCE(status,'Pending') NOT IN ('Completed','Cancelled')");
                if($q){$q->bind_param('ii',$patientId,$visitId);$q->execute();$pendingLabNow=(int)($q->get_result()->fetch_assoc()['total']??0);$q->close();}
                $q=$conn->prepare("SELECT COUNT(*) total FROM patient_services WHERE patient_id=? AND visit_id=? AND category='radiology' AND COALESCE(status,'Pending') NOT IN ('Completed','Cancelled')");
                if($q){$q->bind_param('ii',$patientId,$visitId);$q->execute();$pendingRadNow=(int)($q->get_result()->fetch_assoc()['total']??0);$q->close();}
                $qc=$conn->query("SHOW TABLES LIKE 'pharmacy_queue'");
                if($qc && $qc->num_rows){$q=$conn->prepare("SELECT COUNT(*) total FROM pharmacy_queue WHERE patient_id=? AND visit_id=? AND status='pending'");if($q){$q->bind_param('ii',$patientId,$visitId);$q->execute();$pendingPharmacyNow=(int)($q->get_result()->fetch_assoc()['total']??0);$q->close();}}
            }
            $canComplete=($pendingLabNow+$pendingRadNow+$pendingPharmacyNow)===0;
            ?>
            <div class="row">
                <div class="col-md-4"><strong>Lab:</strong> <?= $pendingLabNow ? $pendingLabNow.' pending' : 'Complete' ?></div>
                <div class="col-md-4"><strong>Radiology:</strong> <?= $pendingRadNow ? $pendingRadNow.' pending' : 'Complete' ?></div>
                <div class="col-md-4"><strong>Pharmacy:</strong> <?= $pendingPharmacyNow ? $pendingPharmacyNow.' pending' : 'Complete' ?></div>
            </div>
            <hr>
            <?php if (($visit['status'] ?? '') === 'Completed'): ?>
                <div class="alert alert-success mb-0">This visit is completed.</div>
            <?php elseif ($canComplete): ?>
                <form method="post" class="mb-0" onsubmit="return confirm('Complete this visit? This will close the current visit.');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="patient_id" value="<?= $patientId ?>">
                    <input type="hidden" name="visit_id" value="<?= $visitId ?>">
                    <button type="submit" name="complete_visit" class="btn btn-success"><i class="fas fa-check-circle"></i> Complete Visit</button>
                </form>
            <?php else: ?>
                <div class="text-muted"><i class="fas fa-lock"></i> Complete the pending department orders before closing this visit.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><h5 class="mb-0 font-weight-bold text-primary">Department Results — This Visit</h5></div>
        <div class="card-body">
            <?php
            $clinicalResults = [];
            if ($visitId > 0) {
                $rs = $conn->prepare("SELECT ps.category, sm.service_name, ps.results, ps.status, ps.created_at FROM patient_services ps JOIN services_master sm ON sm.id=ps.service_id WHERE ps.patient_id=? AND ps.visit_id=? AND ps.category IN ('lab','radiology') ORDER BY ps.id DESC");
                if ($rs) { $rs->bind_param('ii',$patientId,$visitId); $rs->execute(); $rr=$rs->get_result(); while($row=$rr->fetch_assoc()) $clinicalResults[]=$row; $rs->close(); }
            }
            ?>
            <?php if ($clinicalResults): ?>
            <div class="table-responsive"><table class="table table-sm table-bordered">
                <thead><tr><th>Department</th><th>Investigation</th><th>Status</th><th>Result / Findings</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($clinicalResults as $r): ?><tr>
                    <td><?= htmlspecialchars(ucfirst($r['category'])) ?></td>
                    <td><?= htmlspecialchars($r['service_name']) ?></td>
                    <td><?= htmlspecialchars($r['status'] ?? 'Pending') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['results'] ?? 'Awaiting result')) ?></td>
                    <td><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
                </tr><?php endforeach; ?>
                </tbody>
            </table></div>
            <?php else: ?><div class="text-muted">No laboratory or radiology results have been returned for this visit yet.</div><?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-white"><h5 class="mb-0 font-weight-bold">This Visit's Clinical History</h5></div>
        <div class="card-body">
            <?php if ($encounters && $encounters->num_rows > 0): while ($e=$encounters->fetch_assoc()): ?>
                <div class="border-bottom pb-3 mb-3">
                    <div class="small text-muted"><?= htmlspecialchars($e['created_at'] ?? '') ?></div>
                    <strong>Diagnosis:</strong> <?= nl2br(htmlspecialchars($e['diagnosis'] ?? 'Not recorded')) ?><br>
                    <strong>Plan:</strong> <?= nl2br(htmlspecialchars($e['management_plan'] ?? 'Not recorded')) ?><br>
                    <strong>Notes:</strong> <?= nl2br(htmlspecialchars($e['doctor_notes'] ?? '')) ?>
                </div>
            <?php endwhile; else: ?>
                <div class="text-muted">No previous clinical record for this visit.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>