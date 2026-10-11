<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_login();
require_module_access($conn, 'staff_roster', 'view');

$canCreate = can_module_action($conn, 'staff_roster', 'create');
$canEdit = can_module_action($conn, 'staff_roster', 'edit');
$csrf = csrf_token();
$error = '';
$message = '';
$tablesReady = false;
$tableCheck = $conn->query("SHOW TABLES LIKE 'hr_staff_roster'");
$staffCheck = $conn->query("SHOW TABLES LIKE 'hr_staff'");
$tablesReady = $tableCheck && $tableCheck->num_rows > 0 && $staffCheck && $staffCheck->num_rows > 0;
$shiftTypes = ['Day','Evening','Night','On-call','Weekend','Other'];
$statuses = ['Scheduled','Confirmed','Completed','Cancelled'];
$statusTransitions = ['Scheduled' => ['Confirmed' => 'Confirm', 'Cancelled' => 'Cancel'], 'Confirmed' => ['Completed' => 'Complete', 'Cancelled' => 'Cancel']];
$e = static fn($value): string => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
$parseDateTime = static function(string $value): ?string {
    $date = DateTime::createFromFormat('!Y-m-d\\TH:i', $value);
    $errors = DateTime::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $date->format('Y-m-d\\TH:i') !== $value) return null;
    return $date->format('Y-m-d H:i:s');
};

if ($tablesReady && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your security token expired. Refresh the page and try again.';
    } else {
        $action = (string)($_POST['action'] ?? 'save');
        $id = (int)($_POST['id'] ?? 0);
        if ($action === 'status') {
            if (!$canEdit) { http_response_code(403); exit('Forbidden: Staff roster edit permission is required.'); }
            $newStatus = (string)($_POST['status'] ?? '');
            $currentStatus = '';
            if ($id > 0) {
                $currentStmt = $conn->prepare('SELECT status FROM hr_staff_roster WHERE id=? LIMIT 1');
                if ($currentStmt) {
                    $currentStmt->bind_param('i', $id);
                    $currentStmt->execute();
                    $currentRow = $currentStmt->get_result()->fetch_assoc();
                    $currentStmt->close();
                    $currentStatus = (string)($currentRow['status'] ?? '');
                }
            }
            $allowedTransitions = ['Scheduled' => ['Confirmed','Cancelled'], 'Confirmed' => ['Completed','Cancelled']];
            if ($id < 1 || !isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus], true)) {
                $error = 'That roster status transition is not allowed. Refresh the register and try again.';
            } else {
                $stmt = $conn->prepare('UPDATE hr_staff_roster SET status=?, updated_by=? WHERE id=? AND status=?');
                if ($stmt) {
                    $actor = (int)($_SESSION['user_id'] ?? 0);
                    $stmt->bind_param('siis', $newStatus, $actor, $id, $currentStatus);
                    if ($stmt->execute() && $stmt->affected_rows === 1) $message = 'Roster status updated.';
                    else $error = 'The roster status changed before this action could be saved. Refresh and try again.';
                    $stmt->close();
                } else $error = 'Unable to prepare the roster status update.';
            }
        } elseif ($action === 'save') {
            if (($id > 0 && !$canEdit) || ($id === 0 && !$canCreate)) {
                http_response_code(403); exit('Forbidden: Staff roster permission is required.');
            }
            $staffId = (int)($_POST['staff_id'] ?? 0);
            $shiftType = (string)($_POST['shift_type'] ?? 'Day');
            $start = $parseDateTime(trim((string)($_POST['start_at'] ?? '')));
            $end = $parseDateTime(trim((string)($_POST['end_at'] ?? '')));
            $notes = trim((string)($_POST['notes'] ?? ''));
            if ($staffId < 1 || !in_array($shiftType, $shiftTypes, true) || !$start || !$end || $end <= $start) {
                $error = 'Select a staff member and valid shift details. The end time must be after the start time.';
            } elseif (strlen($notes) > 1000) {
                $error = 'Notes cannot exceed 1,000 characters.';
            } else {
                $staffStmt = $conn->prepare("SELECT id FROM hr_staff WHERE id=? AND status='Active' LIMIT 1");
                if (!$staffStmt) $error = 'Unable to validate the selected staff member.';
                else {
                    $staffStmt->bind_param('i', $staffId);
                    $staffStmt->execute();
                    $validStaff = $staffStmt->get_result()->fetch_assoc();
                    $staffStmt->close();
                    if (!$validStaff) $error = 'Only an active employee can be assigned a shift.';
                    else {
                        $overlapSql = "SELECT id FROM hr_staff_roster
                            WHERE staff_id=? AND status <> 'Cancelled'
                              AND start_at < ? AND end_at > ?";
                        if ($id > 0) $overlapSql .= " AND id <> ?";
                        $overlapSql .= " LIMIT 1";
                        $overlap = $conn->prepare($overlapSql);
                        if (!$overlap) $error = 'Unable to check shift conflicts.';
                        else {
                            if ($id > 0) $overlap->bind_param('issi', $staffId, $end, $start, $id);
                            else $overlap->bind_param('iss', $staffId, $end, $start);
                            $overlap->execute();
                            $conflict = $overlap->get_result()->fetch_assoc();
                            $overlap->close();
                            if ($conflict) $error = 'This employee already has a scheduled shift that overlaps the selected time.';
                            else {
                                $notesValue = $notes !== '' ? $notes : null;
                                $actor = (int)($_SESSION['user_id'] ?? 0);
                                if ($id > 0) {
                                    $stmt = $conn->prepare("UPDATE hr_staff_roster SET staff_id=?, shift_type=?, start_at=?, end_at=?, notes=?, updated_by=? WHERE id=?");
                                    if ($stmt) {
                                        $stmt->bind_param('issssii', $staffId, $shiftType, $start, $end, $notesValue, $actor, $id);
                                        if ($stmt->execute()) {
                                            $message = 'Staff shift updated.';
                                            hms_notify_staff_member($conn, $staffId, 'Shift assignment updated', 'Your ' . $shiftType . ' shift has been updated to ' . date('D, d M Y H:i', strtotime($start)) . ' – ' . date('D, d M Y H:i', strtotime($end)) . '.', 'staff_roster', 'info', '/hospital_system/hr/rostering.php', 'roster-update-' . $id . '-' . date('YmdHis'));
                                        } else $error = 'Unable to update the staff shift.';
                                        $stmt->close();
                                    } else $error = 'Unable to prepare the shift update.';
                                } else {
                                    $stmt = $conn->prepare("INSERT INTO hr_staff_roster (staff_id, shift_type, start_at, end_at, notes, created_by, updated_by) VALUES (?,?,?,?,?,?,?)");
                                    if ($stmt) {
                                        $stmt->bind_param('issssii', $staffId, $shiftType, $start, $end, $notesValue, $actor, $actor);
                                        if ($stmt->execute()) {
                                            $newRosterId = (int)$stmt->insert_id;
                                            $message = 'Staff shift scheduled.';
                                            hms_notify_staff_member($conn, $staffId, 'New shift assigned', 'You have been assigned a ' . $shiftType . ' shift from ' . date('D, d M Y H:i', strtotime($start)) . ' to ' . date('D, d M Y H:i', strtotime($end)) . '.', 'staff_roster', 'info', '/hospital_system/hr/rostering.php', 'roster-new-' . $newRosterId);
                                        } else $error = 'Unable to schedule the staff shift.';
                                        $stmt->close();
                                    } else $error = 'Unable to prepare the shift schedule.';
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $error = 'Unsupported roster action.';
        }
    }
}

$editRow = null;
if ($tablesReady && isset($_GET['edit']) && $canEdit) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $conn->prepare("SELECT * FROM hr_staff_roster WHERE id=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $editId);
            $stmt->execute();
            $editRow = $stmt->get_result()->fetch_assoc() ?: null;
            $stmt->close();
        }
    }
}
$staffRows = null;
$rosterRows = null;
$metrics = ['today'=>0,'upcoming'=>0,'active_staff'=>0,'conflicts'=>0];
if ($tablesReady) {
    $staffRows = $conn->query("SELECT id, staff_no, first_name, middle_name, last_name, department, job_title FROM hr_staff WHERE status='Active' ORDER BY department, last_name, first_name");
    $rosterRows = $conn->query("SELECT r.*, s.staff_no, s.first_name, s.middle_name, s.last_name, s.department, s.job_title
        FROM hr_staff_roster r JOIN hr_staff s ON s.id=r.staff_id
        WHERE r.start_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          AND r.start_at < DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        ORDER BY r.start_at ASC LIMIT 300");
    $q = $conn->query("SELECT
        SUM(CASE WHEN DATE(start_at)=CURDATE() AND status <> 'Cancelled' THEN 1 ELSE 0 END) AS today_count,
        SUM(CASE WHEN start_at>=NOW() AND status IN ('Scheduled','Confirmed') THEN 1 ELSE 0 END) AS upcoming_count
        FROM hr_staff_roster");
    if ($q && ($m=$q->fetch_assoc())) { $metrics['today']=(int)$m['today_count']; $metrics['upcoming']=(int)$m['upcoming_count']; }
    $q = $conn->query("SELECT COUNT(*) AS n FROM hr_staff WHERE status='Active'");
    if ($q && ($m=$q->fetch_assoc())) $metrics['active_staff']=(int)$m['n'];
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.roster-page{padding:24px;max-width:1500px;margin:0 auto}.roster-hero{background:linear-gradient(120deg,#0b3d75,#087f8c);color:#fff;border-radius:18px;padding:25px 28px;margin-bottom:22px;display:flex;justify-content:space-between;gap:18px;align-items:center}.roster-hero h1{font-size:clamp(1.45rem,2.4vw,2.15rem);font-weight:800;margin:5px 0 8px}.roster-hero p{margin:0;color:#e1f5ff;max-width:720px}.roster-kicker{text-transform:uppercase;font-size:.72rem;font-weight:800;letter-spacing:1.4px;color:#b7f1ff}.roster-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:22px}.roster-card,.roster-panel{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:20px;box-shadow:0 5px 18px rgba(15,42,74,.045);min-width:0}.roster-label{font-size:.78rem;color:#64748b;font-weight:700}.roster-value{font-size:1.9rem;font-weight:800;color:#123b63;margin-top:5px}.roster-panel{margin-bottom:20px}.roster-panel h2{font-size:1.1rem;font-weight:800;color:#123b63;margin:0 0 16px}.roster-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.roster-form label{display:block;font-size:.8rem;font-weight:700;color:#334155;margin-bottom:6px}.roster-form input,.roster-form select,.roster-form textarea{width:100%;border:1px solid #cbd5e1;border-radius:9px;padding:10px 11px;background:#fff;color:#0f172a}.roster-form textarea{min-height:42px;resize:vertical}.roster-span{grid-column:1/-1}.roster-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.roster-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:0;border-radius:9px;padding:9px 13px;font-weight:700;font-size:.83rem;text-decoration:none;cursor:pointer}.roster-primary{background:#0b477b;color:#fff}.roster-muted{background:#edf2f7;color:#334155}.roster-table-wrap{overflow-x:auto}.roster-table{width:100%;border-collapse:collapse;font-size:.86rem}.roster-table th,.roster-table td{padding:12px 10px;border-bottom:1px solid #e8eef5;text-align:left;vertical-align:top}.roster-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;background:#f8fafc}.roster-name{display:block;font-weight:800;color:#123b63}.roster-sub{display:block;color:#64748b;font-size:.78rem;margin-top:3px}.roster-pill{display:inline-block;padding:4px 8px;border-radius:999px;font-size:.72rem;font-weight:800;background:#eaf2ff;color:#1d4ed8}.roster-pill-Completed{background:#dcfce7;color:#166534}.roster-pill-Cancelled{background:#fee2e2;color:#991b1b}.roster-pill-Confirmed{background:#cffafe;color:#155e75}.roster-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px}.roster-alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}.roster-alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}.roster-alert-warning{background:#fffbeb;color:#92400e;border:1px solid #fde68a}@media(max-width:900px){.roster-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.roster-form{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.roster-page{padding:14px}.roster-hero{padding:20px;align-items:flex-start}.roster-grid,.roster-form{grid-template-columns:1fr}.roster-span{grid-column:auto}}
</style>
<div class="main-content"><main class="roster-page">
<section class="roster-hero"><div><div class="roster-kicker">Staff &amp; HR Operations</div><h1>Staff Rostering &amp; Shift Coverage</h1><p>Plan employee shifts, check for overlapping assignments, and track scheduled, confirmed, completed and cancelled shifts.</p></div></section>
<?php if ($error !== ''): ?><div class="roster-alert roster-alert-error"><?= $e($error) ?></div><?php endif; ?>
<?php if ($message !== ''): ?><div class="roster-alert roster-alert-success"><?= $e($message) ?></div><?php endif; ?>
<?php if (!$tablesReady): ?>
<div class="roster-alert roster-alert-warning">The rostering tables are not available yet. Back up the database, apply <code>database/hr_staff_master_migration.sql</code> if needed, then apply <code>database/hr_staff_rostering_migration.sql</code>. Do not apply migrations directly to production without review.</div>
<?php else: ?>
<section class="roster-grid">
<div class="roster-card"><div class="roster-label">Shifts Starting Today</div><div class="roster-value"><?= $metrics['today'] ?></div></div>
<div class="roster-card"><div class="roster-label">Upcoming Scheduled / Confirmed</div><div class="roster-value"><?= $metrics['upcoming'] ?></div></div>
<div class="roster-card"><div class="roster-label">Active Employees Available</div><div class="roster-value"><?= $metrics['active_staff'] ?></div></div>
</section>
<?php if ($canCreate || ($canEdit && $editRow)): ?>
<section class="roster-panel" id="shift-form"><h2><?= $editRow ? 'Edit Staff Shift' : 'Schedule a Shift' ?></h2>
<form method="post" class="roster-form">
<input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($editRow['id'] ?? 0) ?>">
<div><label for="staff_id">Employee</label><select id="staff_id" name="staff_id" required><option value="">Choose active employee</option><?php if ($staffRows): while ($staff=$staffRows->fetch_assoc()): ?><option value="<?= (int)$staff['id'] ?>" <?= (int)($editRow['staff_id'] ?? 0)===(int)$staff['id']?'selected':'' ?>><?= $e(trim(($staff['staff_no'] ? $staff['staff_no'].' · ' : '').$staff['first_name'].' '.$staff['middle_name'].' '.$staff['last_name'])) ?> — <?= $e($staff['department']) ?></option><?php endwhile; $staffRows->data_seek(0); endif; ?></select></div>
<div><label for="shift_type">Shift Type</label><select id="shift_type" name="shift_type" required><?php foreach($shiftTypes as $type): ?><option value="<?= $e($type) ?>" <?= ($editRow['shift_type'] ?? 'Day')===$type?'selected':'' ?>><?= $e($type) ?></option><?php endforeach; ?></select></div>
<div><label for="start_at">Start Date &amp; Time</label><input id="start_at" name="start_at" type="datetime-local" required value="<?= $e(isset($editRow['start_at']) ? date('Y-m-d\\TH:i',strtotime($editRow['start_at'])) : '') ?>"></div>
<div><label for="end_at">End Date &amp; Time</label><input id="end_at" name="end_at" type="datetime-local" required value="<?= $e(isset($editRow['end_at']) ? date('Y-m-d\\TH:i',strtotime($editRow['end_at'])) : '') ?>"></div>
<div class="roster-span"><label for="notes">Handover / Coverage Notes (optional)</label><textarea id="notes" name="notes" maxlength="1000"><?= $e($editRow['notes'] ?? '') ?></textarea></div>
<div class="roster-span roster-actions"><button class="roster-btn roster-primary" type="submit"><i class="fas fa-calendar-plus"></i> <?= $editRow ? 'Save Shift Changes' : 'Schedule Shift' ?></button><?php if ($editRow): ?><a class="roster-btn roster-muted" href="rostering.php">Cancel Edit</a><?php endif; ?></div>
</form></section>
<?php endif; ?>
<section class="roster-panel"><h2>Roster Register</h2><p class="roster-sub">Shows shifts from the past 30 days through the next 90 days. Overlapping assignments are blocked for the same employee. This register does not replace clock-in attendance capture or clinical staffing approval.</p><div class="roster-table-wrap"><table class="roster-table"><thead><tr><th>Employee</th><th>Department / Role</th><th>Shift</th><th>Start</th><th>End</th><th>Status</th><th>Notes / Actions</th></tr></thead><tbody>
<?php if ($rosterRows && $rosterRows->num_rows): while($row=$rosterRows->fetch_assoc()): ?><tr><td><span class="roster-name"><?= $e(trim($row['first_name'].' '.$row['middle_name'].' '.$row['last_name'])) ?></span><span class="roster-sub"><?= $e($row['staff_no'] ?: 'No staff number') ?></span></td><td><?= $e($row['department']) ?><span class="roster-sub"><?= $e($row['job_title']) ?></span></td><td><?= $e($row['shift_type']) ?></td><td><?= $e(date('D, d M Y H:i',strtotime($row['start_at']))) ?></td><td><?= $e(date('D, d M Y H:i',strtotime($row['end_at']))) ?></td><td><span class="roster-pill roster-pill-<?= $e($row['status']) ?>"><?= $e($row['status']) ?></span></td><td><?= $e($row['notes'] ?: '—') ?><?php if($canEdit && $row['status']!=='Cancelled'): ?><div class="roster-actions" style="margin-top:8px"><?php if($row['status']!=='Completed'): ?><a class="roster-btn roster-muted" href="?edit=<?= (int)$row['id'] ?>#shift-form">Edit</a><?php endif; ?><?php if(isset($statusTransitions[$row['status']])): foreach($statusTransitions[$row['status']] as $targetStatus=>$targetLabel): ?><form method="post" style="display:inline"><input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="status" value="<?= $e($targetStatus) ?>"><button class="roster-btn roster-muted" type="submit"><?= $e($targetLabel) ?></button></form><?php endforeach; endif; ?></div><?php endif; ?></td></tr><?php endwhile; else: ?><tr><td colspan="7">No roster shifts found in this date range. Schedule the first shift above.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php endif; ?>
</main></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
