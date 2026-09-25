<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin','doctor','nurse']);

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
$message = '';
$recordedBy = (int)($_SESSION['user_id'] ?? 0);

$vitalsHasStatus = false;
$vc = $conn->query("SHOW COLUMNS FROM vitals");
if ($vc) {
    while ($col = $vc->fetch_assoc()) {
        if (($col['Field'] ?? '') === 'status') { $vitalsHasStatus = true; break; }
    }
}
$hasVisitColumn = ($x = $conn->query("SHOW COLUMNS FROM vitals LIKE 'visit_id'")) && $x->num_rows > 0;

$selectedPatientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$selectedVisitId = (int)($_GET['visit_id'] ?? $_POST['visit_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $message = "<div class='alert alert-danger'>Invalid security token. Please refresh and try again.</div>";
    } else {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $postedVisitId = (int)($_POST['visit_id'] ?? 0);
        $bp = trim($_POST['bp'] ?? '');
        $temp = trim($_POST['temp'] ?? '');
        $weight = trim($_POST['weight'] ?? '');
        $pulse = trim($_POST['pulse'] ?? '');
        $complaints = trim($_POST['complaints'] ?? '');
        $clinic = trim($_POST['clinic_category'] ?? 'General');
        $priority = trim($_POST['priority'] ?? 'Routine');
        if (!in_array($priority, ['Routine','Urgent','Emergency'], true)) $priority = 'Routine';

        if ($patientId <= 0 || $postedVisitId <= 0) {
            $message = "<div class='alert alert-danger'>Select a patient from the active Triage Queue.</div>";
        } else {
            $vs = $conn->prepare(
                "SELECT id, patient_id, clinic_category, visit_type
                 FROM visits
                 WHERE id=? AND patient_id=? AND visit_date=CURDATE()
                   AND status IN ('Open','In Progress')
                   AND visit_type <> 'Walk-in'
                   AND NOT EXISTS (SELECT 1 FROM vitals vt WHERE vt.visit_id=visits.id)
                 LIMIT 1"
            );
            $validVisit = null;
            if ($vs) {
                $vs->bind_param('ii', $postedVisitId, $patientId);
                $vs->execute();
                $validVisit = $vs->get_result()->fetch_assoc();
                $vs->close();
            }

            if (!$validVisit) {
                $message = "<div class='alert alert-warning'>This visit is no longer waiting for triage. Please refresh the queue.</div>";
            } else {
                try {
                    $visitId = (int)$validVisit['id'];
                    if ($clinic === 'General' && !empty($validVisit['clinic_category'])) {
                        $clinic = (string)$validVisit['clinic_category'];
                    }

                    if ($hasVisitColumn && $vitalsHasStatus) {
                        $status = 'pending';
                        $stmt = $conn->prepare("INSERT INTO vitals (patient_id,bp,temp,weight,pulse,complaints,recorded_by,visit_id,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
                        $stmt->bind_param('isssssiis', $patientId,$bp,$temp,$weight,$pulse,$complaints,$recordedBy,$visitId,$status);
                    } elseif ($hasVisitColumn) {
                        $stmt = $conn->prepare("INSERT INTO vitals (patient_id,bp,temp,weight,pulse,complaints,recorded_by,visit_id,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
                        $stmt->bind_param('isssssii', $patientId,$bp,$temp,$weight,$pulse,$complaints,$recordedBy,$visitId);
                    } else {
                        $stmt = null;
                        throw new Exception('The Vitals table is missing the visit_id column. Run the HMS workflow migration before recording triage.');
                    }

                    if (!$stmt || !$stmt->execute()) throw new Exception($stmt ? $stmt->error : $conn->error);
                    if ($stmt) $stmt->close();

                    $v = $conn->prepare("UPDATE visits SET clinic_category=?, triage_priority=?, status='Open', updated_at=NOW() WHERE id=? AND patient_id=?");
                    if ($v) {
                        $v->bind_param('ssii', $clinic, $priority, $visitId, $patientId);
                        $v->execute();
                        $v->close();
                    }

                    header("Location: consultations.php?triage=success");
                    exit;
                } catch (Throwable $e) {
                    $message = "<div class='alert alert-danger'>Unable to record triage: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }
    }
}

/* Only active visits that actually require triage are shown.
 * Walk-in visits are deliberately excluded because triage is optional for them.
 */
$waiting = [];
$waitingSql = "SELECT v.id AS visit_id, v.visit_number, v.visit_time, v.visit_type,
                      v.clinic_category, p.id AS patient_id, p.full_name,
                      p.patient_number, p.gender, p.age, p.phone,
                      TIMESTAMPDIFF(MINUTE, TIMESTAMP(v.visit_date, v.visit_time), NOW()) AS waiting_minutes,
                      COALESCE(v.triage_priority, 'Routine') AS triage_priority
               FROM visits v
               INNER JOIN patients p ON p.id=v.patient_id
               WHERE v.visit_date=CURDATE()
                 AND v.status IN ('Open','In Progress')
                 AND v.visit_type <> 'Walk-in'
                 AND NOT EXISTS (SELECT 1 FROM vitals vt WHERE vt.visit_id=v.id)
               ORDER BY FIELD(COALESCE(v.triage_priority,'Routine'),'Emergency','Urgent','Routine'), v.visit_time ASC, v.id ASC";
$waitingResult = $conn->query($waitingSql);
if ($waitingResult) {
    while ($row = $waitingResult->fetch_assoc()) $waiting[] = $row;
}

$selected = null;
if ($selectedVisitId > 0) {
    foreach ($waiting as $row) {
        if ((int)$row['visit_id'] === $selectedVisitId && (int)$row['patient_id'] === $selectedPatientId) {
            $selected = $row;
            break;
        }
    }
}
if (!$selected && count($waiting) === 1) $selected = $waiting[0];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.triage-page{padding:28px 0 50px}
.triage-hero{background:linear-gradient(135deg,#0d6efd,#174ea6);color:#fff;border-radius:16px;padding:24px 28px;box-shadow:0 10px 30px rgba(13,110,253,.16)}
.triage-hero h3{margin:0 0 6px;font-weight:700}.triage-hero p{margin:0;opacity:.9}
.stat-card{border:0;border-radius:14px;box-shadow:0 4px 18px rgba(0,0,0,.07);height:100%}.stat-number{font-size:28px;font-weight:700}
.queue-card,.form-card{border:0;border-radius:14px;box-shadow:0 4px 18px rgba(0,0,0,.07)}
.patient-row{cursor:pointer;border-radius:10px;margin-bottom:7px;padding:12px;border:1px solid #edf0f4;background:#fff}
.patient-row:hover,.patient-row.active{background:#f2f7ff;border-color:#9ec5fe}
.patient-avatar{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#e7f1ff;color:#0d6efd;font-weight:700}
.vital-box{background:#f8fafc;border:1px solid #e9ecef;border-radius:12px;padding:16px}
.vital-box label{font-size:12px;font-weight:700;color:#6c757d;text-transform:uppercase}
.vital-box input{font-size:18px;font-weight:600}
.priority-routine{background:#e9f7ef;color:#146c43}.priority-urgent{background:#fff3cd;color:#856404}.priority-emergency{background:#f8d7da;color:#842029}
.waiting-emergency{border-left:4px solid #dc3545!important}.waiting-urgent{border-left:4px solid #ffc107!important}
.section-label{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6c757d}
</style>

<div class="main-content">
<div class="container-fluid triage-page">
    <div class="triage-hero mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h3><i class="fas fa-heartbeat mr-2"></i>Triage & Vitals</h3>
            <p>Assess patients who have been routed to triage. Walk-in treatment visits are not forced through this screen.</p>
        </div>
        <a href="consultations.php" class="btn btn-light"><i class="fas fa-user-md mr-1"></i> Doctor Queue</a>
    </div>

    <?= $message ?>

    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card stat-card"><div class="card-body">
                <div class="text-muted small">WAITING FOR TRIAGE</div>
                <div class="stat-number text-primary"><?= count($waiting) ?></div>
                <div class="small text-muted">Active visits requiring vitals</div>
            </div></div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card stat-card"><div class="card-body">
                <div class="text-muted small">SELECTED PATIENT</div>
                <div class="font-weight-bold mt-2"><?= $selected ? htmlspecialchars($selected['full_name']) : 'None selected' ?></div>
                <div class="small text-muted"><?= $selected ? htmlspecialchars($selected['visit_number']) : 'Choose from the queue' ?></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card"><div class="card-body">
                <div class="text-muted small">WORKFLOW</div>
                <div class="font-weight-bold mt-2">Triage → Doctor</div>
                <div class="small text-muted">One visit is preserved throughout</div>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card queue-card h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="font-weight-bold mb-1">Triage Queue</h5>
                    <div class="small text-muted">Only today's active visits needing triage</div>
                </div>
                <div class="card-body px-3">
                    <?php if ($waiting): ?>
                        <?php foreach ($waiting as $row): ?>
                            <a href="?patient_id=<?= (int)$row['patient_id'] ?>&visit_id=<?= (int)$row['visit_id'] ?>" class="text-decoration-none text-dark">
                                <div class="patient-row <?= strtolower($row['triage_priority']) === 'emergency' ? 'waiting-emergency' : (strtolower($row['triage_priority']) === 'urgent' ? 'waiting-urgent' : '') ?> <?= $selected && (int)$selected['visit_id']===(int)$row['visit_id'] ? 'active' : '' ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="patient-avatar mr-3"><?= htmlspecialchars(strtoupper(substr($row['full_name'],0,1))) ?></div>
                                        <div class="flex-grow-1">
                                            <div class="font-weight-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($row['patient_number']) ?> · <?= htmlspecialchars($row['visit_number']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($row['clinic_category'] ?? 'General') ?> · <?= htmlspecialchars($row['visit_time']) ?></div>
                                            <div class="small mt-1"><span class="badge priority-<?= strtolower($row['triage_priority']) ?>"><?= htmlspecialchars($row['triage_priority']) ?></span> <span class="text-muted ml-1"><?= max(0,(int)$row['waiting_minutes']) ?> min waiting</span></div>
                                        </div>
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <h6 class="font-weight-bold">Triage queue is clear</h6>
                            <p class="small mb-0">No active visits currently require triage.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card form-card">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <?php if ($selected): ?>
                        <div class="section-label">Selected Patient</div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <div>
                                <h5 class="font-weight-bold mb-1"><?= htmlspecialchars($selected['full_name']) ?></h5>
                                <div class="small text-muted"><?= htmlspecialchars($selected['patient_number']) ?> · <?= htmlspecialchars($selected['gender'] ?? '') ?> · <?= htmlspecialchars($selected['age'] ?? '') ?> yrs · Visit <?= htmlspecialchars($selected['visit_number']) ?></div>
                            </div>
                            <span class="badge badge-warning px-3 py-2">Waiting for Triage</span>
                            <div class="small text-muted mt-2">Waiting <?= $selected ? max(0,(int)$selected['waiting_minutes']) : 0 ?> min</div>
                        </div>
                    <?php else: ?>
                        <h5 class="font-weight-bold mb-1">Record Triage</h5>
                        <div class="small text-muted">Select a patient from the Triage Queue to begin.</div>
                    <?php endif; ?>
                </div>

                <div class="card-body p-4">
                    <?php if ($selected): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="patient_id" value="<?= (int)$selected['patient_id'] ?>">
                        <input type="hidden" name="visit_id" value="<?= (int)$selected['visit_id'] ?>">

                        <div class="section-label mb-3">Triage Priority</div>
                        <div class="row mb-3"><div class="col-md-8"><div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                            <label class="btn btn-outline-success flex-fill active"><input type="radio" name="priority" value="Routine" autocomplete="off" checked> Routine</label>
                            <label class="btn btn-outline-warning flex-fill"><input type="radio" name="priority" value="Urgent" autocomplete="off"> Urgent</label>
                            <label class="btn btn-outline-danger flex-fill"><input type="radio" name="priority" value="Emergency" autocomplete="off"> Emergency</label>
                        </div><small class="text-muted">Use Emergency for patients needing immediate attention.</small></div></div>

                        <div class="section-label mb-3">Vital Signs</div>
                        <div class="row">
                            <div class="col-md-6 col-lg-3 mb-3"><div class="vital-box"><label>Blood Pressure</label><input name="bp" class="form-control border-0 bg-transparent px-0" placeholder="120/80"></div></div>
                            <div class="col-md-6 col-lg-3 mb-3"><div class="vital-box"><label>Temperature °C</label><input name="temp" type="number" step="0.1" class="form-control border-0 bg-transparent px-0" placeholder="36.5"></div></div>
                            <div class="col-md-6 col-lg-3 mb-3"><div class="vital-box"><label>Weight kg</label><input name="weight" type="number" step="0.1" class="form-control border-0 bg-transparent px-0" placeholder="70.0"></div></div>
                            <div class="col-md-6 col-lg-3 mb-3"><div class="vital-box"><label>Pulse bpm</label><input name="pulse" type="number" class="form-control border-0 bg-transparent px-0" placeholder="72"></div></div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-6 mb-3">
                                <label class="section-label">Clinic / Department</label>
                                <select name="clinic_category" class="form-control">
                                    <?php foreach (['General','Outpatient','Dental','Maternal','Pediatric','Emergency','Specialist'] as $option): ?>
                                        <option <?= (($selected['clinic_category'] ?? 'General') === $option) ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="section-label">Triage Outcome</label>
                                <input class="form-control" value="Ready for Doctor assessment" readonly>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="section-label">Chief Complaints / Triage Notes</label>
                            <textarea name="complaints" class="form-control mt-2" rows="5" placeholder="Symptoms, duration, immediate observations, urgency or other notes..."></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted"><i class="fas fa-info-circle mr-1"></i>Saving this record moves the same Visit to the Doctor Queue.</div>
                            <button class="btn btn-primary px-4 py-2 font-weight-bold"><i class="fas fa-check mr-1"></i> Complete Triage</button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-stethoscope fa-3x mb-3"></i>
                            <h5 class="font-weight-bold">No patient selected</h5>
                            <p class="mb-0">Choose a patient from the left-hand Triage Queue.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
