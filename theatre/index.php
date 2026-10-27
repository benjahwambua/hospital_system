<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'theatre', 'view');

function theatre_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function theatre_has_current_professional_licence(mysqli $conn, int $userId): ?bool {
    $sql = "SELECT c.id FROM hr_staff s JOIN hr_staff_credentials c ON c.staff_id=s.id WHERE s.linked_user_id=? AND s.status='Active' AND c.credential_type='Professional Licence' AND c.status='Active' AND c.registration_number IS NOT NULL AND TRIM(c.registration_number)<>'' AND (c.expiry_date IS NULL OR c.expiry_date>=CURDATE()) LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { error_log('Theatre credential validation prepare failed: '.$conn->error); return null; }
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) { error_log('Theatre credential validation execute failed: '.$stmt->error); $stmt->close(); return null; }
    $valid = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $valid;
}
$canCreate = can_module_action($conn, 'theatre', 'create');
$canEdit = can_module_action($conn, 'theatre', 'edit');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'create') {
        if (!$canCreate) { http_response_code(403); exit('Forbidden: theatre create permission required.'); }
        $patientId = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT) ?: 0;
        $procedure = trim((string)($_POST['procedure_name'] ?? ''));
        $urgency = (string)($_POST['urgency'] ?? 'Elective');
        $scheduled = trim((string)($_POST['scheduled_at'] ?? ''));
        $surgeonRaw = trim((string)($_POST['surgeon_id'] ?? ''));
        $anesthetistRaw = trim((string)($_POST['anesthetist_id'] ?? ''));
        $surgeonId = $surgeonRaw === '' ? null : filter_var($surgeonRaw, FILTER_VALIDATE_INT);
        $anesthetistId = $anesthetistRaw === '' ? null : filter_var($anesthetistRaw, FILTER_VALIDATE_INT);
        $notes = trim((string)($_POST['clinical_notes'] ?? ''));
        if ($patientId <= 0 || $procedure === '' || $scheduled === '') {
            $error = 'Patient, planned procedure and scheduled date/time are required.';
        } elseif (($surgeonRaw !== '' && ($surgeonId === false || $surgeonId <= 0))
            || ($anesthetistRaw !== '' && ($anesthetistId === false || $anesthetistId <= 0))) {
            $error = 'Select a valid surgeon and anaesthesia provider, or leave the assignments unassigned.';
        } elseif ($surgeonId !== null && $surgeonId !== false && $anesthetistId !== null && $anesthetistId !== false && (int)$surgeonId === (int)$anesthetistId) {
            $error = 'Assign different staff members as lead surgeon and anaesthesia provider.';
        } elseif (strlen($notes) > 4000) {
            $error = 'Clinical notes must not exceed 4,000 characters.';
        } elseif (!in_array($urgency, ['Emergency','Urgent','Elective'], true)) {
            $error = 'Select a valid theatre urgency.';
        } else {
            $patientLookupAvailable = true;
            $check = $conn->prepare('SELECT id FROM patients WHERE id=? LIMIT 1');
            if (!$check) {
                error_log('Theatre patient validation prepare failed for patient id '.$patientId.': '.$conn->error);
                $error = 'Unable to validate the selected patient. Please try again.';
                $patientLookupAvailable = false;
                $patientExists = false;
            } else {
                $check->bind_param('i', $patientId);
                if (!$check->execute()) {
                    error_log('Theatre patient validation execute failed for patient id '.$patientId.': '.$check->error);
                    $check->close();
                    $error = 'Unable to validate the selected patient. Please try again.';
                    $patientLookupAvailable = false;
                    $patientExists = false;
                } else {
                    $patientExists = (bool)$check->get_result()->fetch_assoc();
                    $check->close();
                }
            }
            $surgeonLookupAvailable = true;
            $validSurgeon = true;
            if ($surgeonId && $surgeonId > 0) {
                $staffCheck = $conn->prepare("SELECT id FROM users WHERE id=? AND COALESCE(status,'Active') NOT IN ('Inactive','Disabled') LIMIT 1");
                if (!$staffCheck) { error_log('Theatre surgeon validation prepare failed for user id '.$surgeonId.': '.$conn->error); $surgeonLookupAvailable = false; $validSurgeon = false; }
                else {
                    $staffCheck->bind_param('i', $surgeonId);
                    if (!$staffCheck->execute()) {
                        error_log('Theatre surgeon validation execute failed for user id '.$surgeonId.': '.$staffCheck->error);
                        $surgeonLookupAvailable = false;
                        $validSurgeon = false;
                    } else {
                        $validSurgeon = (bool)$staffCheck->get_result()->fetch_assoc();
                    }
                    $staffCheck->close();
                }
            }
            $anesthetistLookupAvailable = true;
            $validAnesthetist = true;
            if ($anesthetistId && $anesthetistId > 0) {
                $staffCheck = $conn->prepare("SELECT id FROM users WHERE id=? AND COALESCE(status,'Active') NOT IN ('Inactive','Disabled') LIMIT 1");
                if (!$staffCheck) { error_log('Theatre anaesthesia validation prepare failed for user id '.$anesthetistId.': '.$conn->error); $anesthetistLookupAvailable = false; $validAnesthetist = false; }
                else {
                    $staffCheck->bind_param('i', $anesthetistId);
                    if (!$staffCheck->execute()) {
                        error_log('Theatre anaesthesia validation execute failed for user id '.$anesthetistId.': '.$staffCheck->error);
                        $anesthetistLookupAvailable = false;
                        $validAnesthetist = false;
                    } else {
                        $validAnesthetist = (bool)$staffCheck->get_result()->fetch_assoc();
                    }
                    $staffCheck->close();
                }
            }
            $surgeonLicenceStatus = ($surgeonId && $surgeonId > 0 && $validSurgeon) ? theatre_has_current_professional_licence($conn, (int)$surgeonId) : true;
            $anesthetistLicenceStatus = ($anesthetistId && $anesthetistId > 0 && $validAnesthetist) ? theatre_has_current_professional_licence($conn, (int)$anesthetistId) : true;
            $validSchedule = DateTime::createFromFormat('!Y-m-d\TH:i', $scheduled);
            $dateErrors = DateTime::getLastErrors();
            $scheduleIsValid = $validSchedule instanceof DateTime
                && $validSchedule->format('Y-m-d\TH:i') === $scheduled
                && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0));
            if (!$patientLookupAvailable) {
                // Keep the database lookup error; a failed lookup is not proof that the patient is missing.
            } elseif (!$patientExists) {
                $error = 'The selected patient was not found.';
            } elseif (!$surgeonLookupAvailable || !$anesthetistLookupAvailable) {
                $error = 'Unable to validate the assigned theatre team. Please retry after the database issue is resolved.';
            } elseif (!$validSurgeon || !$validAnesthetist) {
                $error = 'Select active surgeon and anaesthesia accounts, or leave the assignments unassigned.';
            } elseif ($surgeonLicenceStatus !== true || $anesthetistLicenceStatus !== true) {
                $error = 'Assigned theatre clinicians must have an active HR staff record and a current Professional Licence with a registration number. Verify the HR staff and credential migrations and records; leave the assignment unassigned if not yet verified.';
            } elseif (strlen($procedure) > 180) {
                $error = 'The planned procedure name must not exceed 180 characters.';
            } elseif (!$scheduleIsValid) {
                $error = 'Enter a valid scheduled date and time.';
            } else {
                $scheduleSql = $validSchedule->format('Y-m-d H:i:s');
                $surgeonId = $surgeonId && $surgeonId > 0 ? $surgeonId : null;
                $anesthetistId = $anesthetistId && $anesthetistId > 0 ? $anesthetistId : null;
                $caseNumber = 'TH-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                // Link the surgical case to the patient's current open encounter when one exists,
                // so downstream billing can reuse the correct visit invoice.
                $visitId = null;
                $visitStmt = $conn->prepare("SELECT id FROM visits WHERE patient_id=? AND status IN ('Open','In Progress') ORDER BY visit_date DESC,visit_time DESC,id DESC LIMIT 1");
                if (!$visitStmt) {
                    error_log('Theatre visit lookup prepare failed for patient id '.$patientId.': '.$conn->error);
                    $error = 'Unable to link the theatre case to the current patient visit. Please retry.';
                } else {
                    $visitStmt->bind_param('i', $patientId);
                    if (!$visitStmt->execute()) {
                        error_log('Theatre visit lookup failed for patient id '.$patientId.': '.$visitStmt->error);
                        $error = 'Unable to link the theatre case to the current patient visit. Please retry.';
                    } else {
                        $visitRow = $visitStmt->get_result()->fetch_assoc();
                        if ($visitRow) $visitId = (int)$visitRow['id'];
                    }
                    $visitStmt->close();
                }
                if ($error === '') $stmt = $conn->prepare("INSERT INTO theatre_cases (case_number,patient_id,visit_id,procedure_name,urgency,scheduled_at,surgeon_id,anesthetist_id,clinical_notes,status,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?, 'Planned',?,?)");
                else $stmt = false;
                if (!$stmt) {
                    error_log('Theatre case insert prepare failed for patient id '.$patientId.': '.$conn->error);
                    $error = 'Unable to prepare the theatre case. Confirm the theatre migration has been applied.';
                } else {
                    $uid = (int)($_SESSION['user_id'] ?? 0);
                    $stmt->bind_param('siisssiisii', $caseNumber, $patientId, $visitId, $procedure, $urgency, $scheduleSql, $surgeonId, $anesthetistId, $notes, $uid, $uid);
                    if ($stmt->execute()) {
                        $newId = (int)$stmt->insert_id;
                        if (function_exists('audit')) audit('theatre_case_created', 'case_id='.$newId.',case_number='.$caseNumber.',patient_id='.$patientId);
                        $message = 'Theatre case ' . $caseNumber . ' was scheduled.';
                    } else {
                        error_log('Theatre case insert failed for patient id '.$patientId.': '.$stmt->error);
                        $error = 'Theatre case could not be saved. Check that the schema migration is applied and required fields are valid.';
                    }
                    $stmt->close();
                }
            }
        }
    } elseif (($_POST['action'] ?? '') === 'update') {
        if (!$canEdit) { http_response_code(403); exit('Forbidden: theatre edit permission required.'); }
        $caseId = filter_input(INPUT_POST, 'case_id', FILTER_VALIDATE_INT) ?: 0;
        $targetStatus = (string)($_POST['status'] ?? 'Planned');
        $allowedStatuses = ['Planned','Pre-op','In Theatre','Recovery','Completed','Cancelled'];
        $checks = [
            'consent_confirmed' => isset($_POST['consent_confirmed']) ? 1 : 0,
            'identity_confirmed' => isset($_POST['identity_confirmed']) ? 1 : 0,
            'site_marked' => isset($_POST['site_marked']) ? 1 : 0,
            'allergies_reviewed' => isset($_POST['allergies_reviewed']) ? 1 : 0,
            'fasting_confirmed' => isset($_POST['fasting_confirmed']) ? 1 : 0,
            'anesthesia_reviewed' => isset($_POST['anesthesia_reviewed']) ? 1 : 0
        ];
        $intraop = trim((string)($_POST['intraoperative_notes'] ?? ''));
        $anesthesia = trim((string)($_POST['anesthesia_notes'] ?? ''));
        $recovery = trim((string)($_POST['recovery_notes'] ?? ''));
        $outcome = (string)($_POST['outcome'] ?? '');
        $cancelReason = trim((string)($_POST['cancellation_reason'] ?? ''));
        if ($caseId <= 0 || !in_array($targetStatus, $allowedStatuses, true)) {
            $error = 'Invalid theatre case or status.';
        } elseif ($targetStatus === 'Cancelled' && $cancelReason === '') {
            $error = 'A cancellation reason is required.';
        } elseif (strlen($intraop) > 5000 || strlen($anesthesia) > 3000 || strlen($recovery) > 4000 || strlen($cancelReason) > 1000) {
            $error = 'The submitted theatre notes exceed the allowed length. Shorten the notes and try again.';
        } else {
            $transactionStarted = false;
            try {
                if (!$conn->begin_transaction()) {
                    error_log('Theatre transaction could not start for case id '.$caseId.': '.$conn->error);
                    throw new RuntimeException('The theatre case update could not start. Please retry.');
                }
                $transactionStarted = true;
                $lock = $conn->prepare('SELECT id,patient_id,status,outcome,surgeon_id,anesthetist_id,consent_confirmed,identity_confirmed,site_marked,allergies_reviewed,fasting_confirmed,anesthesia_reviewed,intraoperative_notes,anesthesia_notes,recovery_notes FROM theatre_cases WHERE id=? FOR UPDATE');
                if (!$lock) {
                    error_log('Theatre case lock prepare failed for case id '.$caseId.': '.$conn->error);
                    throw new RuntimeException('Unable to lock the theatre case.');
                }
                $lock->bind_param('i', $caseId);
                if (!$lock->execute()) {
                    error_log('Theatre case lock query failed for case id '.$caseId.': '.$lock->error);
                    $lock->close();
                    throw new RuntimeException('Unable to load and lock the theatre case. Please retry.');
                }
                $current = $lock->get_result()->fetch_assoc();
                $lock->close();
                if (!$current) throw new RuntimeException('Theatre case was not found.');
                $oldStatus = (string)$current['status'];
                // The checklist records pre-operative confirmations. It may be corrected
                // before surgery starts, but must remain an accurate historical record once
                // the case enters theatre or recovery.
                if (in_array($oldStatus, ['In Theatre', 'Recovery'], true)) {
                    foreach (array_keys($checks) as $field) {
                        if ((int)$checks[$field] !== (int)$current[$field]) {
                            throw new RuntimeException('Pre-operative safety confirmations are locked after theatre starts. Record any changed clinical condition in the appropriate clinical notes instead of rewriting the pre-operative record.');
                        }
                    }
                }
                $effectiveOutcome = $outcome !== '' ? $outcome : (string)($current['outcome'] ?? '');
                $effectiveIntraop = $intraop !== '' ? $intraop : (string)($current['intraoperative_notes'] ?? '');
                $effectiveAnesthesia = $anesthesia !== '' ? $anesthesia : (string)($current['anesthesia_notes'] ?? '');
                $effectiveRecovery = $recovery !== '' ? $recovery : (string)($current['recovery_notes'] ?? '');
                if (in_array($targetStatus, ['Recovery','Completed'], true)
                    && ($effectiveIntraop === '' || $effectiveAnesthesia === '')) {
                    throw new RuntimeException('Before handing over to recovery or completing the case, record both intra-operative findings and anaesthesia notes.');
                }
                if (in_array($targetStatus, ['Recovery','Completed'], true)
                    && ($effectiveRecovery === '' || !in_array($effectiveOutcome, ['Recovered','Transferred','Admitted','Other'], true))) {
                    throw new RuntimeException('Before handing over to recovery or completing the case, record recovery / handover notes and a valid post-operative outcome.');
                }
                if ($targetStatus === 'Completed' && $effectiveOutcome === 'Admitted') {
                    $patientId = (int)$current['patient_id'];
                    $admissionCheck = $conn->prepare("SELECT id FROM admissions WHERE patient_id=? AND status='Admitted' LIMIT 1 FOR UPDATE");
                    if (!$admissionCheck) {
                        error_log('Theatre admission verification prepare failed for case id '.$caseId.': '.$conn->error);
                        throw new RuntimeException('Unable to verify the inpatient admission before completing this theatre case.');
                    }
                    $admissionCheck->bind_param('i', $patientId);
                    if (!$admissionCheck->execute()) {
                        error_log('Theatre admission verification failed for case id '.$caseId.': '.$admissionCheck->error);
                        $admissionCheck->close();
                        throw new RuntimeException('Unable to verify the inpatient admission before completing this theatre case. Please retry.');
                    }
                    $activeAdmission = $admissionCheck->get_result()->fetch_assoc();
                    $admissionCheck->close();
                    if (!$activeAdmission) throw new RuntimeException('This case outcome is Admitted, but the patient has no active inpatient admission. Complete the ward admission first, then complete the theatre case.');
                }
                // The legacy six-item pre-operative gate remains required. The phased
                // WHO-style checklist is a separate auditable record and must also be complete.
                if ($targetStatus === 'In Theatre' && in_array(0, $checks, true)) {
                    throw new RuntimeException('The patient-safety gate is incomplete. Confirm consent, identity, site, allergies, fasting and anaesthesia review before starting theatre.');
                }
                if (in_array($targetStatus, ['In Theatre','Recovery'], true)) {
                    $requiredPhases = $targetStatus === 'In Theatre' ? ['Sign In','Time Out'] : ['Sign In','Time Out','Sign Out'];
                    $phaseStmt = $conn->prepare("SELECT phase, completed_at, items_json FROM theatre_safety_checklist_phases WHERE case_id=? AND completed_at IS NOT NULL FOR UPDATE");
                    if (!$phaseStmt) {
                        error_log('Theatre phased safety checklist lookup failed: '.$conn->error);
                        throw new RuntimeException('The phased surgical safety checklist is not installed. Apply database/theatre_safety_checklist_migration.sql before progressing the case.');
                    }
                    $phaseStmt->bind_param('i', $caseId);
                    if (!$phaseStmt->execute()) {
                        $phaseStmt->close();
                        throw new RuntimeException('Unable to verify the phased surgical safety checklist. Please retry.');
                    }
                    $phaseResult = $phaseStmt->get_result();
                    $completedPhases = [];
                    while ($phaseRow = $phaseResult->fetch_assoc()) {
                        $items = json_decode((string)$phaseRow['items_json'], true);
                        if (is_array($items) && count($items) > 0) $completedPhases[(string)$phaseRow['phase']] = true;
                    }
                    $phaseStmt->close();
                    foreach ($requiredPhases as $requiredPhase) {
                        if (empty($completedPhases[$requiredPhase])) {
                            throw new RuntimeException('Complete and save the '.$requiredPhase.' surgical safety checklist before moving this case to '.$targetStatus.'. Use the Phased Safety Checklist link on the case.');
                        }
                    }
                }
                if ($targetStatus === 'In Theatre') {
                    $surgeonId = (int)($current['surgeon_id'] ?? 0);
                    $anesthetistId = (int)($current['anesthetist_id'] ?? 0);
                    if ($surgeonId <= 0 || $anesthetistId <= 0) {
                        throw new RuntimeException('Assign both a lead surgeon and an anaesthesia provider before starting the operation.');
                    }
                    $staffCheck = $conn->prepare("SELECT COUNT(*) active_count FROM users WHERE id IN (?,?) AND COALESCE(status,'Active') NOT IN ('Inactive','Disabled')");
                    if (!$staffCheck) {
                        error_log('Theatre active team verification prepare failed for case id '.$caseId.': '.$conn->error);
                        throw new RuntimeException('Unable to verify the assigned theatre team.');
                    }
                    $staffCheck->bind_param('ii', $surgeonId, $anesthetistId);
                    if (!$staffCheck->execute()) {
                        error_log('Theatre team verification failed for case id '.$caseId.': '.$staffCheck->error);
                        $staffCheck->close();
                        throw new RuntimeException('Unable to verify the assigned theatre team. Please retry.');
                    }
                    $staffRow = $staffCheck->get_result()->fetch_assoc();
                    $staffCheck->close();
                    if ((int)($staffRow['active_count'] ?? 0) !== 2) {
                        throw new RuntimeException('The assigned surgeon and anaesthesia provider must both be active user accounts before the operation can start.');
                    }
                }
                if (in_array($oldStatus, ['Completed','Cancelled'], true)) {
                    throw new RuntimeException('This theatre case is closed and cannot be edited. Create a documented follow-up workflow if further action is required.');
                }
                $transitions = [
                    'Planned' => ['Planned','Pre-op','Cancelled'],
                    'Pre-op' => ['Pre-op','Planned','In Theatre','Cancelled'],
                    'In Theatre' => ['In Theatre','Recovery'],
                    'Recovery' => ['Recovery','Completed'],
                    'Completed' => ['Completed'],
                    'Cancelled' => ['Cancelled']
                ];
                if (!in_array($targetStatus, $transitions[$oldStatus] ?? [], true)) {
                    throw new RuntimeException('This status transition is not allowed. Follow the theatre lifecycle in order.');
                }
                $uid = (int)($_SESSION['user_id'] ?? 0);
                $startSql = $targetStatus === 'In Theatre' && $oldStatus !== 'In Theatre' ? date('Y-m-d H:i:s') : null;
                $endSql = $targetStatus === 'Recovery' && $oldStatus === 'In Theatre' ? date('Y-m-d H:i:s') : null;
                $recoverySql = in_array($targetStatus, ['Recovery','Completed'], true) && $recovery !== '' ? $recovery : null;
                $outcomeSql = in_array($targetStatus, ['Recovery','Completed'], true) && $outcome !== '' ? $outcome : null;
                $cancelSql = $targetStatus === 'Cancelled' ? $cancelReason : null;
                $sql = "UPDATE theatre_cases SET consent_confirmed=?,identity_confirmed=?,site_marked=?,allergies_reviewed=?,fasting_confirmed=?,anesthesia_reviewed=?,intraoperative_notes=COALESCE(NULLIF(?,''),intraoperative_notes),anesthesia_notes=COALESCE(NULLIF(?,''),anesthesia_notes),recovery_notes=COALESCE(NULLIF(?,''),recovery_notes),outcome=COALESCE(?,outcome),cancellation_reason=COALESCE(?,cancellation_reason),status=?,theatre_started_at=COALESCE(theatre_started_at,?),theatre_ended_at=COALESCE(theatre_ended_at,?),updated_by=?,updated_at=NOW() WHERE id=?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    error_log('Theatre case update prepare failed for case id '.$caseId.': '.$conn->error);
                    throw new RuntimeException('Unable to prepare the case update. Confirm the theatre migration.');
                }
                $stmt->bind_param('iiiiiissssssssii', $checks['consent_confirmed'],$checks['identity_confirmed'],$checks['site_marked'],$checks['allergies_reviewed'],$checks['fasting_confirmed'],$checks['anesthesia_reviewed'],$intraop,$anesthesia,$recoverySql,$outcomeSql,$cancelSql,$targetStatus,$startSql,$endSql,$uid,$caseId);
                if (!$stmt->execute()) {
                    error_log('Theatre case update failed for case id '.$caseId.': '.$stmt->error);
                    $stmt->close();
                    throw new RuntimeException('The theatre case update failed. Please retry.');
                }
                $stmt->close();
                if (!$conn->commit()) {
                    error_log('Theatre transaction commit failed for case id '.$caseId.': '.$conn->error);
                    throw new RuntimeException('The theatre case update could not be confirmed. Refresh the case before retrying.');
                }
                $transactionStarted = false;
                if (function_exists('audit')) audit('theatre_case_updated', 'case_id='.$caseId.',from='.$oldStatus.',to='.$targetStatus);
                $message = 'Theatre case updated successfully.';
            } catch (Throwable $e) {
                if ($transactionStarted) {
                    if (!$conn->rollback()) error_log('Theatre transaction rollback failed for case id '.$caseId.': '.$conn->error);
                }
                if (!($e instanceof RuntimeException)) error_log('Unexpected theatre update error for case id '.$caseId.': '.$e->getMessage());
                $error = $e instanceof RuntimeException ? $e->getMessage() : 'The theatre case could not be updated.';
            }
        }
    }
}

$pageLoadErrors = [];
$patients = $conn->query('SELECT id,patient_number,full_name FROM patients ORDER BY full_name ASC LIMIT 2000');
if (!$patients) { error_log('Theatre patient list query failed: '.$conn->error); $pageLoadErrors[] = 'Patient list could not be loaded. Scheduling is unavailable until the database issue is resolved.'; }
$staff = $conn->query("SELECT id,full_name FROM users WHERE COALESCE(status,'Active') NOT IN ('Inactive','Disabled') ORDER BY full_name ASC");
if (!$staff) { error_log('Theatre staff list query failed: '.$conn->error); $pageLoadErrors[] = 'Theatre staff list could not be loaded. Verify the user table and database connection before assigning a team.'; }
$statsAvailable = false;
$stats = ['today'=>'Unavailable','planned'=>'Unavailable','in_theatre'=>'Unavailable','recovery'=>'Unavailable'];
$q = $conn->query("SELECT SUM(DATE(scheduled_at)=CURDATE() AND status NOT IN ('Cancelled')) today_count,SUM(status IN ('Planned','Pre-op')) planned_count,SUM(status='In Theatre') theatre_count,SUM(status='Recovery') recovery_count FROM theatre_cases");
if ($q) {
    $s=$q->fetch_assoc() ?: [];
    $stats=['today'=>(int)($s['today_count']??0),'planned'=>(int)($s['planned_count']??0),'in_theatre'=>(int)($s['theatre_count']??0),'recovery'=>(int)($s['recovery_count']??0)];
    $statsAvailable = true;
} else {
    error_log('Theatre metrics query failed: '.$conn->error);
    $pageLoadErrors[] = 'Theatre metrics could not be loaded. Counts are marked unavailable rather than shown as zero.';
}
$cases = $conn->query("SELECT t.*,p.full_name,p.patient_number,s.full_name surgeon_name,a.full_name anesthetist_name FROM theatre_cases t JOIN patients p ON p.id=t.patient_id LEFT JOIN users s ON s.id=t.surgeon_id LEFT JOIN users a ON a.id=t.anesthetist_id ORDER BY FIELD(t.status,'In Theatre','Recovery','Pre-op','Planned','Completed','Cancelled'),t.scheduled_at ASC LIMIT 150");
if (!$cases) { error_log('Theatre case list query failed: '.$conn->error); $pageLoadErrors[] = 'Theatre cases could not be loaded. The list below is not a reliable indication that no cases exist.'; }
$pageLoadError = implode(' ', $pageLoadErrors);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.theatre-page{padding:26px;background:#f4f7fb;min-height:calc(100vh - 60px)}.theatre-shell{max-width:1550px;margin:auto}.theatre-hero{background:linear-gradient(135deg,#063b73,#075b9d);border-radius:20px;padding:28px 30px;color:#fff;display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:20px;box-shadow:0 10px 28px rgba(6,59,115,.15)}.theatre-hero h1{margin:4px 0 8px;font-size:28px;color:#fff}.theatre-hero p{margin:0;color:#d9eaff;font-size:14px}.theatre-kicker{font-size:10px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#9ee7ff}.theatre-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.theatre-stat,.theatre-panel{background:#fff;border:1px solid #e3eaf3;border-radius:14px;padding:18px;box-shadow:0 3px 12px rgba(22,44,75,.04)}.theatre-stat small{color:#758399;text-transform:uppercase;font-size:10px;letter-spacing:1px;font-weight:800}.theatre-stat strong{display:block;font-size:28px;color:#12385f;margin-top:8px}.theatre-layout{display:grid;grid-template-columns:minmax(300px,.82fr) minmax(0,1.7fr);gap:18px;align-items:start}.theatre-panel h2{font-size:17px;color:#173b62;margin:0 0 15px}.theatre-form label{display:block;font-size:12px;font-weight:700;color:#43536a;margin:12px 0 5px}.theatre-form input,.theatre-form select,.theatre-form textarea{width:100%;border:1px solid #d7e0ec;border-radius:8px;padding:10px 11px;font:inherit;font-size:13px;background:#fff;box-sizing:border-box}.theatre-form textarea{min-height:70px;resize:vertical}.theatre-btn{border:0;border-radius:8px;padding:10px 14px;background:#075b9d;color:#fff;font-weight:800;cursor:pointer;margin-top:12px}.theatre-btn:hover{background:#063b73}.theatre-table-wrap{overflow:auto}.theatre-table{width:100%;border-collapse:collapse;font-size:12px;min-width:850px}.theatre-table th,.theatre-table td{padding:12px 10px;border-bottom:1px solid #e9eef5;text-align:left;vertical-align:top}.theatre-table th{background:#f7f9fc;color:#66758b;text-transform:uppercase;letter-spacing:.6px;font-size:10px}.theatre-case{font-weight:800;color:#12385f}.theatre-muted{color:#758399;font-size:11px;margin-top:4px}.theatre-pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#e8f1fb;color:#174e7e;font-size:10px;font-weight:800}.theatre-pill.alert{background:#fff0d7;color:#895700}.theatre-pill.good{background:#dff6eb;color:#146345}.theatre-alert{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:13px}.theatre-alert.error{background:#fff0f0;color:#922b2b;border:1px solid #f4d0d0}.theatre-alert.success{background:#e5f7ee;color:#176345;border:1px solid #c5ead7}.theatre-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:8px}.theatre-checks label{display:flex;gap:7px;align-items:flex-start;font-size:11px;margin:0;font-weight:600}.theatre-checks input{width:auto;margin:2px 0 0}.theatre-edit{min-width:290px}.theatre-edit details{border:1px solid #e2eaf3;border-radius:9px;padding:9px;margin-top:8px}.theatre-edit summary{cursor:pointer;color:#075b9d;font-weight:800}.theatre-empty{padding:28px;text-align:center;color:#758399}@media(max-width:1100px){.theatre-layout{grid-template-columns:1fr}.theatre-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.theatre-page{padding:14px}.theatre-hero{padding:22px;align-items:flex-start;flex-direction:column}.theatre-grid{gap:8px}.theatre-stat{padding:13px}.theatre-stat strong{font-size:23px}}
</style>
<div class="theatre-page"><div class="theatre-shell">
  <section class="theatre-hero"><div><div class="theatre-kicker">Clinical Services / Peri-operative Care</div><h1>Theatre &amp; Surgery</h1><p>Schedule surgical cases, verify the pre-operative safety gate, and track theatre-to-recovery handover.</p></div><div class="theatre-pill">Initial workflow · Clinical validation required</div></section>
  <p style="margin:0 0 16px"><a href="clinical_records.php" style="display:inline-block;background:#075b9d;color:#fff;text-decoration:none;padding:9px 13px;border-radius:8px;font-weight:800">Open PACU &amp; Implant Records</a> <span class="theatre-muted">Recovery observations and device/lot traceability</span></p>
  <?php if ($error !== ''): ?><div class="theatre-alert error"><?= theatre_h($error) ?></div><?php endif; ?>
  <?php if ($pageLoadError !== ''): ?><div class="theatre-alert error"><?= theatre_h($pageLoadError) ?></div><?php endif; ?>
  <?php if ($message !== ''): ?><div class="theatre-alert success"><?= theatre_h($message) ?></div><?php endif; ?>
  <div class="theatre-grid"><div class="theatre-stat"><small>Cases scheduled today</small><strong><?= $stats['today'] ?></strong></div><div class="theatre-stat"><small>Planned / Pre-op</small><strong><?= $stats['planned'] ?></strong></div><div class="theatre-stat"><small>In theatre</small><strong><?= $stats['in_theatre'] ?></strong></div><div class="theatre-stat"><small>Recovery</small><strong><?= $stats['recovery'] ?></strong></div></div>
  <div class="theatre-layout">
    <section class="theatre-panel"><h2>Schedule a case</h2>
    <?php if ($canCreate && $patients && $staff): ?>
      <form class="theatre-form" method="post"><input type="hidden" name="csrf_token" value="<?= theatre_h(csrf_token()) ?>"><input type="hidden" name="action" value="create">
        <label for="patient_id">Registered patient *</label><select id="patient_id" name="patient_id" required><option value="">Select patient</option><?php if($patients): while($p=$patients->fetch_assoc()): ?><option value="<?= (int)$p['id'] ?>"><?= theatre_h($p['patient_number'].' — '.$p['full_name']) ?></option><?php endwhile; endif; ?></select>
        <label for="procedure_name">Planned procedure *</label><input id="procedure_name" name="procedure_name" maxlength="180" required placeholder="e.g. Open reduction and internal fixation">
        <label for="urgency">Priority *</label><select id="urgency" name="urgency"><option>Elective</option><option>Urgent</option><option>Emergency</option></select>
        <label for="scheduled_at">Scheduled date and time *</label><input id="scheduled_at" type="datetime-local" name="scheduled_at" required>
        <label for="surgeon_id">Lead surgeon</label><select id="surgeon_id" name="surgeon_id"><option value="">Unassigned</option><?php if($staff): while($u=$staff->fetch_assoc()): ?><option value="<?= (int)$u['id'] ?>"><?= theatre_h($u['full_name']) ?></option><?php endwhile; endif; ?></select>
        <?php if($staff) $staff->data_seek(0); ?>
        <label for="anesthetist_id">Anaesthesia provider</label><select id="anesthetist_id" name="anesthetist_id"><option value="">Unassigned</option><?php if($staff): while($u=$staff->fetch_assoc()): ?><option value="<?= (int)$u['id'] ?>"><?= theatre_h($u['full_name']) ?></option><?php endwhile; endif; ?></select>
        <label for="clinical_notes">Clinical notes / indication</label><textarea id="clinical_notes" name="clinical_notes" maxlength="4000"></textarea>
        <button class="theatre-btn" type="submit">Schedule theatre case</button>
      </form>
    <?php elseif ($canCreate): ?><p class="theatre-muted">Scheduling is temporarily unavailable because the patient or active theatre-team list could not be loaded. Restore the lookup before creating a case.</p><?php else: ?><p class="theatre-muted">You have view access only. Request Theatre Create permission to schedule cases.</p><?php endif; ?>
    </section>
    <section class="theatre-panel"><h2>Case list &amp; peri-operative tracking</h2><div class="theatre-table-wrap"><table class="theatre-table"><thead><tr><th>Case / Patient</th><th>Schedule / Team</th><th>Status / Safety</th><th>Manage</th></tr></thead><tbody>
    <?php if($cases && $cases->num_rows): while($c=$cases->fetch_assoc()): $safetyCount=(int)$c['consent_confirmed']+(int)$c['identity_confirmed']+(int)$c['site_marked']+(int)$c['allergies_reviewed']+(int)$c['fasting_confirmed']+(int)$c['anesthesia_reviewed']; ?>
      <tr><td><div class="theatre-case"><?= theatre_h($c['case_number']) ?></div><div><?= theatre_h($c['procedure_name']) ?></div><div class="theatre-muted"><?= theatre_h($c['patient_number'].' — '.$c['full_name']) ?></div><div class="theatre-muted">Priority: <?= theatre_h($c['urgency']) ?></div></td>
      <td><?= theatre_h(date('d M Y, H:i',strtotime($c['scheduled_at']))) ?><div class="theatre-muted">Surgeon: <?= theatre_h($c['surgeon_name'] ?: 'Unassigned') ?></div><div class="theatre-muted">Anaesthesia: <?= theatre_h($c['anesthetist_name'] ?: 'Unassigned') ?></div></td>
      <td><span class="theatre-pill <?= in_array($c['status'],['Completed'],true)?'good':(in_array($c['status'],['In Theatre','Recovery'],true)?'alert':'') ?>"><?= theatre_h($c['status']) ?></span><div class="theatre-muted">Pre-op safety: <?= $safetyCount ?>/6</div><?php if($c['cancellation_reason']): ?><div class="theatre-muted">Cancelled: <?= theatre_h($c['cancellation_reason']) ?></div><?php endif; ?></td>
      <td class="theatre-edit"><a href="safety_checklist.php?case_id=<?= (int)$c['id'] ?>" style="display:inline-block;margin:0 0 8px;padding:6px 9px;border-radius:6px;background:#e8f2fb;color:#075b9d;text-decoration:none;font-weight:700">Phased Safety Checklist</a><br><a href="clinical_records.php?case_id=<?= (int)$c['id'] ?>" style="display:inline-block;margin:0 0 8px;padding:6px 9px;border-radius:6px;background:#eef5ee;color:#28613a;text-decoration:none;font-weight:700">PACU &amp; Implants</a><br><a href="billing.php?case_id=<?= (int)$c['id'] ?>" style="display:inline-block;margin:0 0 8px;padding:6px 9px;border-radius:6px;background:#fff4df;color:#7a4a00;text-decoration:none;font-weight:700">Procedure Billing</a><br><a href="stock_usage.php?case_id=<?= (int)$c['id'] ?>" style="display:inline-block;margin:0 0 8px;padding:6px 9px;border-radius:6px;background:#f0eaff;color:#533a91;text-decoration:none;font-weight:700">Theatre Stock Usage</a><br><?php if($canEdit && !in_array($c['status'],['Completed','Cancelled'],true)): ?>
        <details><summary>Update checklist &amp; status</summary><form class="theatre-form" method="post"><input type="hidden" name="csrf_token" value="<?= theatre_h(csrf_token()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="case_id" value="<?= (int)$c['id'] ?>">
          <label>Patient safety gate (all six required before In Theatre)</label><div class="theatre-checks">
          <?php foreach(['consent_confirmed'=>'Consent confirmed','identity_confirmed'=>'Identity confirmed','site_marked'=>'Site / side marked','allergies_reviewed'=>'Allergies reviewed','fasting_confirmed'=>'Fasting status confirmed','anesthesia_reviewed'=>'Anaesthesia review complete'] as $field=>$label): ?><label><input type="checkbox" name="<?= theatre_h($field) ?>" value="1" <?= !empty($c[$field])?'checked':'' ?>><?= theatre_h($label) ?></label><?php endforeach; ?>
          </div>
          <label for="status_<?= (int)$c['id'] ?>">Case status</label><select id="status_<?= (int)$c['id'] ?>" name="status"><?php foreach(['Planned','Pre-op','In Theatre','Recovery','Completed','Cancelled'] as $st): if($st===$c['status'] || in_array($st,(['Planned'=>['Planned','Pre-op','Cancelled'],'Pre-op'=>['Pre-op','Planned','In Theatre','Cancelled'],'In Theatre'=>['In Theatre','Recovery'],'Recovery'=>['Recovery','Completed']][$c['status']]??[]),true)): ?><option value="<?= theatre_h($st) ?>" <?= $st===$c['status']?'selected':'' ?>><?= theatre_h($st) ?></option><?php endif; endforeach; ?></select>
          <label>Intra-operative notes</label><textarea name="intraoperative_notes" maxlength="5000"><?= theatre_h($c['intraoperative_notes']) ?></textarea><label>Anaesthesia notes</label><textarea name="anesthesia_notes" maxlength="3000"><?= theatre_h($c['anesthesia_notes']) ?></textarea>
          <label>Recovery / handover notes (required for recovery and completion)</label><textarea name="recovery_notes" maxlength="4000"><?= theatre_h($c['recovery_notes']) ?></textarea><label>Post-operative outcome</label><select name="outcome"><option value="">Select outcome</option><?php foreach(['Recovered','Transferred','Admitted','Other'] as $o): ?><option value="<?= theatre_h($o) ?>" <?= $c['outcome']===$o?'selected':'' ?>><?= theatre_h($o) ?></option><?php endforeach; ?></select>
          <label>Cancellation reason (required to cancel)</label><textarea name="cancellation_reason" maxlength="1000"><?= theatre_h($c['cancellation_reason']) ?></textarea><button class="theatre-btn" type="submit">Save case update</button>
        </form></details><?php else: ?><span class="theatre-muted"><?= in_array($c['status'],['Completed','Cancelled'],true)?'Case closed':($canEdit?'':'Edit permission required') ?></span><?php endif; ?>
      </td></tr>
    <?php endwhile; elseif($cases): ?><tr><td colspan="4" class="theatre-empty">No theatre cases yet. Schedule the first case to begin the theatre register.</td></tr><?php else: ?><tr><td colspan="4" class="theatre-empty">Theatre case list unavailable. Do not treat this as an empty register.</td></tr><?php endif; ?>
    </tbody></table></div></section>
  </div>
  <p class="theatre-muted" style="margin-top:16px">Clinical safety notice: this initial register does not replace an approved WHO Surgical Safety Checklist, anaesthetic chart, operative report, implant/lot traceability, blood product checks, PACU observation chart, or clinician-led clinical decision-making. Validate the workflow with theatre and anaesthesia staff before production use.</p>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
