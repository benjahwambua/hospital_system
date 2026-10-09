<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_module_access($conn, 'clinical', 'view');

$canDischarge = can_module_action($conn, 'clinical', 'approve');
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$message = '';
$canTransfer = can_module_action($conn, 'clinical', 'edit');
$transferId = (int)($_GET['transfer_id'] ?? $_POST['transfer_id'] ?? 0);
$transferAdmission = null;
$dischargeId = (int)($_GET['discharge_id'] ?? $_POST['discharge_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'transfer_bed') {
    if (!$canTransfer) {
        $message = "<div class='alert alert-danger'>You do not have permission to transfer inpatient beds.</div>";
    } elseif (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $message = "<div class='alert alert-danger'>Invalid security token. Please refresh and try again.</div>";
    } else {
        $transferId = (int)($_POST['transfer_id'] ?? 0);
        $toWard = trim((string)($_POST['to_ward'] ?? ''));
        $toBed = (int)($_POST['to_bed'] ?? 0);
        $reason = trim((string)($_POST['transfer_reason'] ?? ''));
        $destinationValid = false;
        if ($toWard !== '' && $toBed > 0) {
            $check = $conn->prepare("SELECT b.id FROM inpatient_wards w JOIN inpatient_beds b ON b.ward_id=w.id WHERE w.name=? AND w.is_active=1 AND b.bed_number=? AND b.is_active=1 LIMIT 1");
            if ($check) {
                $check->bind_param('si', $toWard, $toBed);
                $check->execute();
                $destinationValid = (bool)$check->get_result()->fetch_assoc();
                $check->close();
            }
        }
        if ($transferId <= 0 || !$destinationValid || $reason === '') {
            $message = "<div class='alert alert-danger'>Select an active destination ward and bed, and provide a transfer reason.</div>";
        } else {
            $conn->begin_transaction();
            try {
                $s = $conn->prepare("SELECT id,patient_id,ward_name,bed_number FROM admissions WHERE id=? AND status='Admitted' LIMIT 1 FOR UPDATE");
                if (!$s) throw new Exception('Unable to load active admission.');
                $s->bind_param('i', $transferId);
                $s->execute();
                $source = $s->get_result()->fetch_assoc();
                $s->close();
                if (!$source) throw new Exception('Active admission not found.');
                if ($source['ward_name'] === $toWard && (int)$source['bed_number'] === $toBed) throw new Exception('Choose a different destination bed.');
                $s = $conn->prepare("SELECT id FROM admissions WHERE ward_name=? AND bed_number=? AND status='Admitted' AND id<>? LIMIT 1 FOR UPDATE");
                if (!$s) throw new Exception('Unable to verify destination bed.');
                $s->bind_param('sii', $toWard, $toBed, $transferId);
                $s->execute();
                $occupied = $s->get_result()->fetch_assoc();
                $s->close();
                if ($occupied) throw new Exception('The selected destination bed is occupied.');
                $userId = (int)($_SESSION['user_id'] ?? 0);
                $s = $conn->prepare("INSERT INTO inpatient_bed_transfers(admission_id,patient_id,from_ward,from_bed,to_ward,to_bed,transfer_reason,transferred_by) VALUES(?,?,?,?,?,?,?,?)");
                if (!$s) throw new Exception('Unable to prepare transfer history.');
                $s->bind_param('iisisisi', $transferId, $source['patient_id'], $source['ward_name'], $source['bed_number'], $toWard, $toBed, $reason, $userId);
                if (!$s->execute()) throw new Exception('Unable to record transfer history.');
                $s->close();
                $s = $conn->prepare("UPDATE admissions SET ward_name=?,bed_number=? WHERE id=? AND status='Admitted'");
                if (!$s) throw new Exception('Unable to prepare bed update.');
                $s->bind_param('sii', $toWard, $toBed, $transferId);
                if (!$s->execute() || $s->affected_rows !== 1) throw new Exception('Admission bed could not be updated.');
                $s->close();
                if (function_exists('audit')) audit('inpatient_bed_transfer', "admission_id={$transferId},from={$source['ward_name']}:{$source['bed_number']},to={$toWard}:{$toBed}");
                $conn->commit();
                $_SESSION['msg_success'] = 'Bed transfer completed and recorded.';
                header('Location: ward_management.php?transferred=1');
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('Inpatient bed transfer error: '.$e->getMessage());
                $message = "<div class='alert alert-danger'>Unable to transfer the patient. The admission was not changed.</div>";
            }
        }
    }
}

if ($transferId > 0) {
    $s = $conn->prepare("SELECT a.*,p.full_name,p.patient_number FROM admissions a JOIN patients p ON p.id=a.patient_id WHERE a.id=? AND a.status='Admitted' LIMIT 1");
    if ($s) {
        $s->bind_param('i', $transferId);
        $s->execute();
        $transferAdmission = $s->get_result()->fetch_assoc();
        $s->close();
    }
    if (!$transferAdmission) $transferId = 0;
}
$dischargeAdmission = null;

// Discharge is deliberately handled inside Ward / IPD.
// This keeps the inpatient lifecycle in one workspace:
// Admission -> Ward/Bed -> Ongoing stay -> Discharge.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_discharge'])) {
    if (!$canDischarge) {
        $message = "<div class='alert alert-danger'>You do not have permission to discharge patients.</div>";
    } elseif (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $message = "<div class='alert alert-danger'>Invalid security token. Please refresh and try again.</div>";
    } else {
        $dischargeId = (int)($_POST['discharge_id'] ?? 0);
        $diagnosis = trim((string)($_POST['discharge_diagnosis'] ?? ''));
        $notes = trim((string)($_POST['discharge_notes'] ?? ''));
        $followUp = trim((string)($_POST['follow_up'] ?? ''));
        $financialReviewed = (string)($_POST['discharge_financial_reviewed'] ?? '') === '1';
        $medicationReconciled = (string)($_POST['discharge_medication_reconciled'] ?? '') === '1';
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if ($dischargeId <= 0 || $diagnosis === '' || $notes === '' || !$financialReviewed || !$medicationReconciled) {
            $message = "<div class='alert alert-danger'>Discharge requires diagnosis, clinical notes, financial review and medication reconciliation confirmations.</div>";
        } else {
            $stmt = $conn->prepare("SELECT a.*, p.full_name, p.patient_number FROM admissions a JOIN patients p ON p.id=a.patient_id WHERE a.id=? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $dischargeId);
                $stmt->execute();
                $dischargeAdmission = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }

            if (!$dischargeAdmission) {
                $message = "<div class='alert alert-danger'>Admission record not found.</div>";
            } elseif (($dischargeAdmission['status'] ?? '') !== 'Admitted') {
                $message = "<div class='alert alert-warning'>This admission is already closed.</div>";
                $dischargeId = 0;
                $dischargeAdmission = null;
            } else {
                $conn->begin_transaction();
                try {
                    $cols = [];
                    $has = $conn->query("SHOW COLUMNS FROM admissions");
                    if ($has) {
                        while ($col = $has->fetch_assoc()) {
                            $cols[$col['Field']] = true;
                        }
                    }

                    $set = ["status='Discharged'"];
                    $types = '';
                    $values = [];

                    if (isset($cols['discharge_date'])) {
                        $set[] = 'discharge_date=NOW()';
                    }
                    if (isset($cols['discharged_by'])) {
                        $set[] = 'discharged_by=?';
                        $types .= 'i';
                        $values[] = $userId;
                    }
                    if (isset($cols['discharge_diagnosis'])) {
                        $set[] = 'discharge_diagnosis=?';
                        $types .= 's';
                        $values[] = $diagnosis;
                    }
                    if (isset($cols['discharge_notes'])) {
                        $set[] = 'discharge_notes=?';
                        $types .= 's';
                        $values[] = $notes;
                    }
                    if (isset($cols['follow_up'])) {
                        $set[] = 'follow_up=?';
                        $types .= 's';
                        $values[] = $followUp;
                    }
                    if (isset($cols['discharge_financial_reviewed'])) $set[] = 'discharge_financial_reviewed=1';
                    if (isset($cols['discharge_medication_reconciled'])) $set[] = 'discharge_medication_reconciled=1';
                    if (isset($cols['discharge_checklist_by'])) { $set[] = 'discharge_checklist_by=?'; $types .= 'i'; $values[] = $userId; }
                    if (isset($cols['discharge_checklist_at'])) $set[] = 'discharge_checklist_at=NOW()';

                    $sql = "UPDATE admissions SET " . implode(',', $set) . " WHERE id=? AND status='Admitted'";
                    $types .= 'i';
                    $values[] = $dischargeId;

                    $u = $conn->prepare($sql);
                    if (!$u) {
                        throw new Exception('Unable to prepare discharge: ' . $conn->error);
                    }
                    $u->bind_param($types, ...$values);
                    if (!$u->execute() || $u->affected_rows !== 1) {
                        throw new Exception('Admission could not be discharged.');
                    }
                    $u->close();

                    if (!empty($dischargeAdmission['visit_id'])) {
                        $v = $conn->prepare("UPDATE visits SET status='Completed', updated_at=NOW() WHERE id=? AND patient_id=? AND status<>'Cancelled'");
                        if ($v) {
                            $v->bind_param('ii', $dischargeAdmission['visit_id'], $dischargeAdmission['patient_id']);
                            $v->execute();
                            $v->close();
                        }
                    }

                    $visitId = (int)($dischargeAdmission['visit_id'] ?? 0);
                    $appointmentCols = $conn->query("SHOW COLUMNS FROM appointments LIKE 'visit_id'");
                    if ($visitId > 0 && $appointmentCols && $appointmentCols->num_rows > 0) {
                        $a = $conn->prepare("UPDATE appointments SET status='Closed' WHERE patient_id=? AND visit_id=? AND status NOT IN ('Closed','Cancelled','Completed')");
                        if ($a) {
                            $a->bind_param('ii', $dischargeAdmission['patient_id'], $visitId);
                            $a->execute();
                            $a->close();
                        }
                    }

                    $mcol = $conn->query("SHOW COLUMNS FROM maternity_admissions LIKE 'admission_id'");
                    if ($mcol && $mcol->num_rows > 0) {
                        $m = $conn->prepare("UPDATE maternity_admissions SET status='Discharged' WHERE admission_id=? AND status<>'Discharged'");
                        if ($m) {
                            $m->bind_param('i', $dischargeId);
                            $m->execute();
                            $m->close();
                        }
                    }

                    if (function_exists('audit')) {
                        audit('patient_discharge', "admission_id={$dischargeId},patient_id={$dischargeAdmission['patient_id']}");
                    }

                    $conn->commit();
                    $_SESSION['msg_success'] = 'Patient discharged successfully and the ward bed is now available.';
                    header('Location: ward_management.php?status=discharged');
                    exit;
                } catch (Throwable $e) {
                    $conn->rollback();
                    error_log('Ward discharge error: ' . $e->getMessage());
                    $message = "<div class='alert alert-danger'>Unable to complete discharge. No changes were saved.</div>";
                }
            }
        }
    }
}

// Load an admission selected for discharge.
if ($dischargeId > 0 && !$dischargeAdmission) {
    $stmt = $conn->prepare("SELECT a.*, p.full_name, p.patient_number FROM admissions a JOIN patients p ON p.id=a.patient_id WHERE a.id=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $dischargeId);
        $stmt->execute();
        $dischargeAdmission = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if (!$dischargeAdmission || ($dischargeAdmission['status'] ?? '') !== 'Admitted') {
        $dischargeId = 0;
        $dischargeAdmission = null;
    }
}

// Ward and bed master is the source of truth for active capacity.
$hospital_wards = [];
$bed_catalog = [];
$wardQuery = $conn->query("SELECT id,name FROM inpatient_wards WHERE is_active=1 ORDER BY name");
if ($wardQuery) {
    while ($wardRow = $wardQuery->fetch_assoc()) {
        $wardName = (string)$wardRow['name'];
        $hospital_wards[$wardName] = 0;
        $bedStmt = $conn->prepare("SELECT bed_number,label FROM inpatient_beds WHERE ward_id=? AND is_active=1 ORDER BY bed_number");
        if ($bedStmt) {
            $wardId = (int)$wardRow['id'];
            $bedStmt->bind_param('i', $wardId);
            $bedStmt->execute();
            $bedRows = $bedStmt->get_result();
            while ($bedRow = $bedRows->fetch_assoc()) {
                $bed_catalog[$wardName][] = $bedRow;
                $hospital_wards[$wardName]++;
            }
            $bedStmt->close();
        }
    }
}

// Recent occupancy movements remain visible for traceability.
$recentTransfers = [];
$historyQuery = $conn->query("SELECT t.id,t.admission_id,t.patient_id,t.from_ward,t.from_bed,t.to_ward,t.to_bed,t.transfer_reason,t.transferred_by,t.transferred_at,p.full_name,p.patient_number FROM inpatient_bed_transfers t LEFT JOIN patients p ON p.id=t.patient_id ORDER BY t.transferred_at DESC,t.id DESC LIMIT 25");
if ($historyQuery) {
    while ($historyRow = $historyQuery->fetch_assoc()) $recentTransfers[] = $historyRow;
}

// Active admissions determine occupied beds.
$sql = "SELECT a.id, a.patient_id, a.ward_name, a.bed_number, p.full_name AS patient_name,
               a.admit_date, a.attending_doctor, a.reason
        FROM admissions a
        LEFT JOIN patients p ON a.patient_id = p.id
        WHERE a.status = 'Admitted'";
$result = $conn->query($sql);

$occupied_beds = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $wardName = trim((string)$row['ward_name']);
        $bedNumber = (int)$row['bed_number'];

        $occupied_beds[$wardName][$bedNumber] = [
            'id'         => (int)$row['id'],
            'patient_id' => (int)$row['patient_id'],
            'name'       => $row['patient_name'] ?? 'Unknown Patient',
            'date'       => $row['admit_date'],
            'doctor'     => $row['attending_doctor'] ?? 'Not Assigned',
            'reason'     => $row['reason'] ?? 'N/A'
        ];
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
.main-content{background:#f5f7fb;min-height:calc(100vh - 72px)}
.main-content>.container-fluid{max-width:1500px}
.main-content .card{border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 18px rgba(31,45,61,.05);overflow:hidden}
.main-content .card-header{background:#fff;border-bottom:1px solid #edf0f5;color:#25324a}
.main-content .form-control{border-color:#d7dee8;border-radius:9px}
.main-content .form-control:focus{border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}
.main-content .btn{border-radius:8px;font-weight:700}
.bed-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:20px;justify-content:center;max-width:1100px;margin:0 auto}
.bed-box{aspect-ratio:1.1/1;border-radius:12px;transition:all .25s ease;display:flex;flex-direction:column;align-items:center;justify-content:center;text-decoration:none!important;box-shadow:0 2px 5px rgba(0,0,0,.05)}
.bed-box:hover{transform:translateY(-5px);box-shadow:0 10px 18px rgba(0,0,0,.12)}
.bed-occupied{background:linear-gradient(135deg,#f35a4a,#d92d1c);color:#fff;border:none}
.bed-available{background:linear-gradient(135deg,#24dca0,#17a673);color:#fff;border:none}
.bed-icon{font-size:2rem;margin-bottom:5px;opacity:.9}
.bed-number{font-size:1.1rem;font-weight:900;letter-spacing:1px}
.popover{border:none;box-shadow:0 1rem 3rem rgba(0,0,0,.3);min-width:290px}
.popover-header{background:#4e73df;color:#fff;font-weight:700;padding:12px;text-align:center}
.popover-body{padding:15px;font-size:.9rem;line-height:1.6}
.pop-btn-group{margin-top:15px;padding-top:12px;border-top:1px solid #eee}
.discharge-panel{border:1px solid #f0d6d6;border-radius:14px;background:#fff;margin-bottom:22px;box-shadow:0 4px 18px rgba(31,45,61,.05);overflow:hidden}
.discharge-panel-head{padding:17px 21px;background:#fff7f7;border-bottom:1px solid #f0d6d6;color:#8f2424;font-weight:800}
.discharge-panel-body{padding:21px}
.discharge-label{font-size:12px;text-transform:uppercase;letter-spacing:.45px;font-weight:800;color:#566474;margin-bottom:7px}
</style>

<div class="main-content">
  <div class="container-fluid pt-4 pb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
      <div>
        <div class="text-uppercase text-muted small font-weight-bold" style="letter-spacing:1.3px;">Clinical · Inpatient Care</div>
        <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Ward / IPD Management</h1>
        <p class="text-muted mb-0">Manage admissions, beds, ongoing inpatient stays and discharge from one workspace.</p>
      </div>
      <div class="mt-3 mt-sm-0 d-flex flex-wrap" style="gap:8px;">
        <a href="inpatient_charges.php" class="btn btn-outline-success shadow-sm"><i class="fas fa-file-invoice-dollar mr-2"></i>Daily Charges</a>
        <a href="ward_configuration.php" class="btn btn-outline-primary shadow-sm"><i class="fas fa-sliders-h mr-2"></i>Ward &amp; Bed Setup</a>
        <a href="admit_patient.php" class="btn btn-primary shadow-sm"><i class="fas fa-plus mr-2"></i>New Admission</a>
      </div>
    </div>

    <?php if (!empty($_SESSION['msg_success'])): ?>
      <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?=htmlspecialchars($_SESSION['msg_success'])?></div>
      <?php unset($_SESSION['msg_success']); ?>
    <?php endif; ?>
    <?=$message?>

    <?php if ($transferAdmission && $canTransfer): ?>
      <div id="transfer" class="discharge-panel" style="border-color:#c8def2;">
        <div class="discharge-panel-head" style="background:#f2f8ff;border-color:#c8def2;color:#075b9d;"><i class="fas fa-exchange-alt mr-2"></i>Transfer Inpatient Bed</div>
        <div class="discharge-panel-body">
          <p class="mb-3"><strong><?=htmlspecialchars($transferAdmission['full_name'])?></strong> · <?=htmlspecialchars($transferAdmission['patient_number'] ?? '')?> — Current location: <?=htmlspecialchars($transferAdmission['ward_name'])?>, Bed <?= (int)$transferAdmission['bed_number'] ?></p>
          <form method="post" action="ward_management.php?transfer_id=<?= (int)$transferId ?>#transfer">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
            <input type="hidden" name="action" value="transfer_bed">
            <input type="hidden" name="transfer_id" value="<?= (int)$transferId ?>">
            <div class="row">
              <div class="col-md-5 form-group"><label class="discharge-label">Destination Ward</label><select name="to_ward" class="form-control" required><?php foreach ($hospital_wards as $wardName => $totalBeds): ?><option value="<?=htmlspecialchars($wardName)?>" <?=($transferAdmission['ward_name']===$wardName?'disabled':'')?>><?=htmlspecialchars($wardName)?></option><?php endforeach; ?></select></div>
              <div class="col-md-2 form-group"><label class="discharge-label">Bed number</label><input type="number" min="1" name="to_bed" class="form-control" required><small class="text-muted">Choose an active bed in the selected ward.</small></div>
              <div class="col-md-5 form-group"><label class="discharge-label">Reason for Transfer</label><input name="transfer_reason" class="form-control" maxlength="500" required placeholder="Clinical need / bed management reason"></div>
            </div>
            <div class="d-flex justify-content-between mt-3"><a href="ward_management.php" class="btn btn-light border">Cancel</a><button class="btn btn-primary" onclick="return confirm('Confirm transfer to the selected ward and bed?');"><i class="fas fa-exchange-alt mr-1"></i>Confirm Transfer</button></div>
          </form>
        </div>
      </div>
    <?php elseif ($transferId > 0 && !$canTransfer): ?><div class="alert alert-danger">You do not have permission to transfer inpatient beds.</div><?php endif; ?>

    <?php if ($dischargeAdmission && $canDischarge): ?>
      <div id="discharge" class="discharge-panel">
        <div class="discharge-panel-head"><i class="fas fa-sign-out-alt mr-2"></i>Discharge Patient</div>
        <div class="discharge-panel-body">
          <div class="row mb-3">
            <div class="col-md-4"><strong>Patient</strong><div><?=htmlspecialchars($dischargeAdmission['full_name'])?><small class="d-block text-muted"><?=htmlspecialchars($dischargeAdmission['patient_number'] ?? '')?></small></div></div>
            <div class="col-md-4"><strong>Ward / Bed</strong><div><?=htmlspecialchars($dischargeAdmission['ward_name'])?> · Bed <?=htmlspecialchars($dischargeAdmission['bed_number'])?></div></div>
            <div class="col-md-4"><strong>Admitted</strong><div><?=htmlspecialchars($dischargeAdmission['admit_date'])?></div></div>
          </div>
          <form method="post" action="ward_management.php?discharge_id=<?= (int)$dischargeId ?>#discharge">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
            <input type="hidden" name="discharge_id" value="<?= (int)$dischargeId ?>">
            <div class="form-group">
              <label class="discharge-label">Discharge Diagnosis</label>
              <textarea name="discharge_diagnosis" class="form-control" rows="3" required><?=htmlspecialchars($_POST['discharge_diagnosis'] ?? '')?></textarea>
            </div>
            <div class="form-group">
              <label class="discharge-label">Condition at Discharge / Clinical Notes</label>
              <textarea name="discharge_notes" class="form-control" rows="4" required><?=htmlspecialchars($_POST['discharge_notes'] ?? '')?></textarea>
            </div>
            <div class="form-group mb-3">
              <label class="discharge-label">Follow-up & Patient Instructions</label>
              <textarea name="follow_up" class="form-control" rows="3"><?=htmlspecialchars($_POST['follow_up'] ?? '')?></textarea>
            </div>
            <div class="border rounded p-3 mb-3 bg-light">
              <strong class="d-block mb-2">Discharge readiness confirmations</strong>
              <div class="custom-control custom-checkbox mb-2"><input class="custom-control-input" type="checkbox" id="discharge_financial_reviewed" name="discharge_financial_reviewed" value="1" required <?=!empty($_POST['discharge_financial_reviewed']) ? 'checked' : ''?>><label class="custom-control-label" for="discharge_financial_reviewed">I have reviewed outstanding inpatient charges and billing status.</label></div>
              <div class="custom-control custom-checkbox"><input class="custom-control-input" type="checkbox" id="discharge_medication_reconciled" name="discharge_medication_reconciled" value="1" required <?=!empty($_POST['discharge_medication_reconciled']) ? 'checked' : ''?>><label class="custom-control-label" for="discharge_medication_reconciled">I have reviewed medication reconciliation and discharge instructions.</label></div>
              <small class="text-muted d-block mt-2">These are accountable confirmations, not automated proof that invoices or medication orders are reconciled.</small>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3" style="border-top:1px solid #edf0f5;">
              <a href="ward_management.php" class="btn btn-light border">Cancel</a>
              <button name="confirm_discharge" class="btn btn-danger px-4" onclick="return confirm('Confirm discharge of this patient?');">
                <i class="fas fa-sign-out-alt mr-1"></i>Confirm Discharge
              </button>
            </div>
          </form>
        </div>
      </div>
    <?php elseif ($dischargeId > 0 && !$canDischarge): ?>
      <div class="alert alert-danger">You do not have permission to discharge patients.</div>
    <?php endif; ?>

    <?php foreach ($hospital_wards as $wardName => $totalBeds): ?>
      <?php
        $currentWardOccupied = $occupied_beds[$wardName] ?? [];
        $occupiedCount = count($currentWardOccupied);
        $availableCount = max(0, $totalBeds - $occupiedCount);
      ?>
      <div class="card shadow mb-5 border-0">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
          <h5 class="m-0 font-weight-bold text-dark">
            <i class="fas fa-hospital-symbol text-primary mr-2"></i><?=htmlspecialchars($wardName)?>
          </h5>
          <div class="d-none d-sm-block">
            <span class="badge badge-pill badge-success px-3 py-2 mr-2"><?=$availableCount?> Free</span>
            <span class="badge badge-pill badge-danger px-3 py-2"><?=$occupiedCount?> Occupied</span>
          </div>
        </div>
        <div class="card-body bg-light py-4">
          <div class="bed-grid">
            <?php foreach (($bed_catalog[$wardName] ?? []) as $bedRow): ?>
              <?php $i = (int)$bedRow['bed_number']; ?>
              <?php
                $isOccupied = isset($currentWardOccupied[$i]);
                $bedClass = $isOccupied ? 'bed-occupied' : 'bed-available';

                if ($isOccupied) {
                    $p = $currentWardOccupied[$i];
                    $link = "../patients/patient_dashboard.php?id=" . $p['patient_id'];
                    $popTitle = "Bed ".$i.": ".htmlspecialchars($p['name']);
                    $popContent = "<div><b>Doctor:</b> ".htmlspecialchars($p['doctor'])."</div>";
                    $popContent .= "<div><b>Since:</b> ".date('d M Y, H:i', strtotime($p['date']))."</div>";
                    $popContent .= "<div><b>Reason:</b> ".htmlspecialchars($p['reason'])."</div>";
                    $popContent .= "<div class='pop-btn-group d-flex justify-content-between'>";
                    $popContent .= "<a href='../patients/patient_dashboard.php?id=".$p['patient_id']."' class='btn btn-sm btn-outline-primary'><i class='fas fa-user-injured mr-1'></i> Patient Record</a>";
                    if ($canTransfer) {
                        $popContent .= "<a href='ward_management.php?transfer_id=".$p['id']."#transfer' class='btn btn-sm btn-outline-primary ml-2'><i class='fas fa-exchange-alt mr-1'></i> Transfer</a>";
                    }
                    if ($canDischarge) {
                        $popContent .= "<a href='ward_management.php?discharge_id=".$p['id']."#discharge' class='btn btn-sm btn-danger ml-2'><i class='fas fa-sign-out-alt mr-1'></i> Discharge</a>";
                    }
                    $popContent .= "</div>";
                    $attr = 'data-toggle="popover" data-trigger="hover" data-html="true" title="'.$popTitle.'" data-content="'.htmlspecialchars($popContent, ENT_QUOTES).' "';
                } else {
                    $link = "admit_patient.php?ward=".urlencode($wardName)."&bed=".$i;
                    $attr = 'data-toggle="tooltip" title="Assign Patient to Bed '.$i.'"';
                }
              ?>
              <a href="<?=$link?>" class="bed-box <?=$bedClass?>" <?=$attr?>>
                <i class="fas fa-bed bed-icon"></i>
                <span class="bed-number">BED <?=$i?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="card shadow mb-4">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-history text-primary mr-2"></i>Recent Bed Transfer History</h5>
        <span class="badge badge-light">Latest 25</span>
      </div>
      <div class="card-body">
        <?php if (!$recentTransfers): ?>
          <p class="text-muted mb-0">No bed transfers have been recorded yet.</p>
        <?php else: ?>
          <div class="table-responsive"><table class="table table-sm table-hover">
            <thead><tr><th>When</th><th>Patient</th><th>From</th><th>To</th><th>Reason</th><th>Recorded by (user ID)</th></tr></thead>
            <tbody><?php foreach ($recentTransfers as $movement): ?>
              <tr>
                <td><?=htmlspecialchars($movement['transferred_at'] ?? '')?></td>
                <td><?=htmlspecialchars($movement['full_name'] ?? ('Patient #'.(int)$movement['patient_id']))?><small class="d-block text-muted"><?=htmlspecialchars($movement['patient_number'] ?? '')?></small></td>
                <td><?=htmlspecialchars($movement['from_ward'])?> · Bed <?= (int)$movement['from_bed'] ?></td>
                <td><?=htmlspecialchars($movement['to_ward'])?> · Bed <?= (int)$movement['to_bed'] ?></td>
                <td><?=htmlspecialchars($movement['transfer_reason'])?></td>
                <td><?= (int)$movement['id'] ?></td>
              </tr>
            <?php endforeach; ?></tbody>
          </table></div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
$(document).ready(function() {
  $('[data-toggle="tooltip"]').tooltip();
  $('[data-toggle="popover"]').popover({
    placement:'top',
    boundary:'viewport',
    sanitize:false,
    delay:{show:100,hide:400}
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>