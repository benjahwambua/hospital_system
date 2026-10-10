<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'hr_staff', 'view');

$canCreate = can_module_action($conn, 'hr_staff', 'create');
$canEdit = can_module_action($conn, 'hr_staff', 'edit');
$csrf = csrf_token();
$message = '';
$error = '';
$departments = ['Administration','Clinical','Nursing','Maternity','Laboratory','Radiology','Pharmacy','Reception','Finance','Procurement','Central Stores','Housekeeping','Security','IT','Other'];
$employmentTypes = ['Permanent','Contract','Locum','Casual','Intern','Volunteer'];
$statuses = ['Active','On Leave','Separated'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'save');
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your security token expired. Refresh the page and try again.';
    } elseif ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && !$canEdit) {
            http_response_code(403); exit('Forbidden: HR edit permission is required.');
        }
        if ($id === 0 && !$canCreate) {
            http_response_code(403); exit('Forbidden: HR create permission is required.');
        }
        $first = trim((string)($_POST['first_name'] ?? ''));
        $middle = trim((string)($_POST['middle_name'] ?? ''));
        $last = trim((string)($_POST['last_name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $department = trim((string)($_POST['department'] ?? ''));
        $jobTitle = trim((string)($_POST['job_title'] ?? ''));
        $employmentType = (string)($_POST['employment_type'] ?? 'Permanent');
        $hireDate = trim((string)($_POST['hire_date'] ?? ''));
        $contractEnd = trim((string)($_POST['contract_end_date'] ?? ''));
        $status = (string)($_POST['status'] ?? 'Active');
        $notes = trim((string)($_POST['notes'] ?? ''));
        $linkedUser = (int)($_POST['linked_user_id'] ?? 0);
        $validDate = static function (string $value): bool { if ($value === '') return true; $d = DateTime::createFromFormat('!Y-m-d', $value); return $d instanceof DateTime && $d->format('Y-m-d') === $value; };

        if ($first === '' || $last === '' || $department === '' || $jobTitle === '') {
            $error = 'First name, last name, department and job title are required.';
        } elseif (!in_array($employmentType, $employmentTypes, true) || !in_array($status, $statuses, true) || !in_array($department, $departments, true)) {
            $error = 'Select a valid department, employment type and status.';
        } elseif ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) {
            $error = 'Enter a valid email address.';
        } elseif (!$validDate($hireDate) || !$validDate($contractEnd) || ($hireDate !== '' && $contractEnd !== '' && $contractEnd < $hireDate)) {
            $error = 'Check the employment dates; contract end cannot precede the hire date.';
        } elseif (strlen($phone) > 30 || strlen($notes) > 1000) {
            $error = 'Phone or notes exceed the allowed length.';
        } else {
            $linkedUserValue = $linkedUser > 0 ? $linkedUser : null;
            $hireDateValue = $hireDate !== '' ? $hireDate : null;
            $contractEndValue = $contractEnd !== '' ? $contractEnd : null;
            $middleValue = $middle !== '' ? $middle : null;
            $phoneValue = $phone !== '' ? $phone : null;
            $emailValue = $email !== '' ? $email : null;
            $notesValue = $notes !== '' ? $notes : null;
            $actor = (int)($_SESSION['user_id'] ?? 0);
            try {
                if ($linkedUserValue !== null) {
                    $check = $conn->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
                    if (!$check) throw new RuntimeException('Unable to validate the linked account.');
                    $check->bind_param('i', $linkedUserValue); $check->execute(); $exists = $check->get_result()->fetch_assoc(); $check->close();
                    if (!$exists) throw new InvalidArgumentException('The selected login account does not exist.');
                }
                if ($id > 0) {
                    $stmt = $conn->prepare('UPDATE hr_staff SET linked_user_id=?, first_name=?, middle_name=?, last_name=?, phone=?, email=?, department=?, job_title=?, employment_type=?, hire_date=?, contract_end_date=?, status=?, notes=?, updated_by=? WHERE id=?');
                    if (!$stmt) throw new RuntimeException('Unable to prepare the staff update.');
                    $stmt->bind_param('issssssssssssii', $linkedUserValue, $first, $middleValue, $last, $phoneValue, $emailValue, $department, $jobTitle, $employmentType, $hireDateValue, $contractEndValue, $status, $notesValue, $actor, $id);
                    $stmt->execute(); $stmt->close();
                    if (function_exists('audit')) audit('hr_staff_updated', 'staff_id='.$id.',department='.$department.',status='.$status);
                    $message = 'Staff record updated.';
                } else {
                    $conn->begin_transaction();
                    $stmt = $conn->prepare('INSERT INTO hr_staff (linked_user_id, first_name, middle_name, last_name, phone, email, department, job_title, employment_type, hire_date, contract_end_date, status, notes, created_by, updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    if (!$stmt) throw new RuntimeException('Unable to prepare the staff record.');
                    $stmt->bind_param('issssssssssssii', $linkedUserValue, $first, $middleValue, $last, $phoneValue, $emailValue, $department, $jobTitle, $employmentType, $hireDateValue, $contractEndValue, $status, $notesValue, $actor, $actor);
                    $stmt->execute(); $newId = (int)$conn->insert_id; $stmt->close();
                    $staffNo = 'STAFF-'.str_pad((string)$newId, 5, '0', STR_PAD_LEFT);
                    $up = $conn->prepare('UPDATE hr_staff SET staff_no=? WHERE id=?');
                    if (!$up) throw new RuntimeException('Unable to assign the staff number.');
                    $up->bind_param('si', $staffNo, $newId); $up->execute(); $up->close();
                    $conn->commit();
                    if (function_exists('audit')) audit('hr_staff_created', 'staff_id='.$newId.',staff_no='.$staffNo.',department='.$department);
                    $message = 'Staff record created: '.$staffNo.'.';
                }
            } catch (InvalidArgumentException $e) {
                if ($conn->errno) $conn->rollback();
                $error = $e->getMessage();
            } catch (Throwable $e) {
                if ($conn->thread_id) { try { $conn->rollback(); } catch (Throwable $ignored) {} }
                error_log('HR staff save failed: '.$e->getMessage());
                $error = 'The staff record could not be saved. Check that the linked login account is not already assigned to another staff record.';
            }
        }
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId > 0 && $canEdit) {
    $stmt = $conn->prepare('SELECT * FROM hr_staff WHERE id=? LIMIT 1');
    if ($stmt) { $stmt->bind_param('i', $editId); $stmt->execute(); $edit = $stmt->get_result()->fetch_assoc(); $stmt->close(); }
}
$users = $conn->query('SELECT id, full_name, username FROM users ORDER BY full_name');
$filters = [];
$where = '1=1';
$search = trim((string)($_GET['q'] ?? ''));
$filterDepartment = trim((string)($_GET['department'] ?? ''));
$filterStatus = trim((string)($_GET['status'] ?? ''));
if ($search !== '') { $where .= ' AND (s.staff_no LIKE ? OR s.first_name LIKE ? OR s.middle_name LIKE ? OR s.last_name LIKE ? OR s.phone LIKE ? OR s.email LIKE ? OR s.job_title LIKE ?)'; $term = '%'.$search.'%'; array_push($filters, $term, $term, $term, $term, $term, $term, $term); }
if (in_array($filterDepartment, $departments, true)) { $where .= ' AND s.department=?'; $filters[] = $filterDepartment; }
if (in_array($filterStatus, $statuses, true)) { $where .= ' AND s.status=?'; $filters[] = $filterStatus; }
$sql = 'SELECT s.*, u.full_name AS account_name FROM hr_staff s LEFT JOIN users u ON u.id=s.linked_user_id WHERE '.$where.' ORDER BY FIELD(s.status, \'Active\', \'On Leave\', \'Separated\'), s.department, s.last_name, s.first_name';
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($filters) { $types = str_repeat('s', count($filters)); $stmt->bind_param($types, ...$filters); }
    $stmt->execute(); $staffRows = $stmt->get_result();
} else { $staffRows = false; $error = $error ?: 'Unable to load the staff directory. Apply the HR migration and reload.'; }
$countResult = $conn->query("SELECT COUNT(*) total, SUM(status='Active') active_count, SUM(status='On Leave') leave_count, SUM(status='Separated') separated_count FROM hr_staff");
$counts = $countResult ? $countResult->fetch_assoc() : ['total'=>0,'active_count'=>0,'leave_count'=>0,'separated_count'=>0];
$e = static fn($value) => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.hr-page{padding:28px 24px 50px;background:#f3f6fb;min-height:calc(100vh - 75px)}.hr-wrap{max-width:1480px;margin:auto}.hr-hero{padding:28px 30px;border-radius:20px;color:#fff;background:linear-gradient(125deg,#083d7b,#1264bd 60%,#13a8b8);box-shadow:0 14px 34px rgba(14,72,139,.18);margin-bottom:20px;display:flex;justify-content:space-between;gap:20px;align-items:center;flex-wrap:wrap}.hr-hero h2{margin:0;color:#fff;font-size:1.8rem;font-weight:800}.hr-hero p{margin:7px 0 0;color:#e1efff}.hr-kicker{text-transform:uppercase;letter-spacing:.15em;font-size:.68rem;font-weight:800;opacity:.8;margin-bottom:7px}.hr-btn{border:0;border-radius:10px;padding:10px 14px;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;gap:8px;cursor:pointer}.hr-btn-primary{background:#fff;color:#0b4e8b}.hr-btn-dark{background:#104d91;color:#fff}.hr-btn-muted{background:#edf3fa;color:#24415f}.hr-card{background:#fff;border:1px solid #e1e8f0;border-radius:16px;overflow:hidden;box-shadow:0 6px 22px rgba(20,40,70,.055);margin-bottom:18px}.hr-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.hr-metric{background:#fff;border:1px solid #e1e8f0;border-radius:15px;padding:18px}.hr-metric span{display:block;color:#697586;text-transform:uppercase;font-size:.68rem;letter-spacing:.07em;font-weight:800}.hr-metric strong{display:block;color:#172b45;font-size:1.55rem;margin-top:7px}.hr-card-head{padding:17px 20px;border-bottom:1px solid #e9eef4;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap}.hr-card-head strong{color:#172b45}.hr-card-head small{display:block;color:#738196;margin-top:3px}.hr-form{padding:20px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px}.hr-field label{display:block;font-size:.68rem;text-transform:uppercase;font-weight:800;letter-spacing:.04em;color:#65748a;margin-bottom:6px}.hr-field input,.hr-field select,.hr-field textarea{width:100%;border:1px solid #d6e0eb;border-radius:9px;padding:10px 11px;background:#fff;color:#172b45;min-height:41px}.hr-field textarea{min-height:84px;resize:vertical}.hr-field-wide{grid-column:1/-1}.hr-form-actions{grid-column:1/-1;display:flex;gap:9px;flex-wrap:wrap}.hr-alert{padding:12px 15px;border-radius:10px;margin-bottom:15px;font-weight:700}.hr-error{background:#fff1f1;border:1px solid #ffd4d4;color:#a11}.hr-success{background:#ecfdf5;border:1px solid #b9efd2;color:#087443}.hr-filters{padding:15px 20px;display:flex;gap:10px;flex-wrap:wrap}.hr-filters input,.hr-filters select{min-height:40px;padding:8px 10px;border:1px solid #d6e0eb;border-radius:9px}.hr-filters input{flex:1;min-width:220px}.hr-table-wrap{overflow-x:auto}.hr-table{width:100%;border-collapse:collapse;min-width:1020px}.hr-table th,.hr-table td{text-align:left;padding:12px 14px;border-bottom:1px solid #edf1f5;vertical-align:middle}.hr-table th{font-size:.66rem;text-transform:uppercase;letter-spacing:.06em;color:#68778b;background:#f7f9fc}.hr-table td{font-size:.84rem;color:#35465d}.hr-name{font-weight:800;color:#172b45}.hr-muted{display:block;font-size:.74rem;color:#77869a;margin-top:3px}.hr-status{display:inline-block;padding:5px 9px;border-radius:99px;font-size:.66rem;font-weight:800;background:#eef2f7;color:#475569}.hr-status-active{background:#e9fbf1;color:#087443}.hr-status-leave{background:#fff7e6;color:#996300}.hr-status-separated{background:#fff0f0;color:#a22}.hr-empty{text-align:center;padding:38px 15px;color:#77869a}@media(max-width:900px){.hr-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.hr-form{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.hr-page{padding:18px 12px 35px}.hr-hero{padding:22px}.hr-hero h2{font-size:1.45rem}.hr-form{grid-template-columns:1fr}.hr-metrics{gap:9px}.hr-metric{padding:14px}.hr-metric strong{font-size:1.25rem}}
</style>
<div class="hr-page"><div class="hr-wrap">
<section class="hr-hero"><div><div class="hr-kicker">People operations · Human resources</div><h2>Staff Directory</h2><p>One controlled employee register for departments, employment details and staff status.</p></div><?php if ($canCreate): ?><a class="hr-btn hr-btn-primary" href="#staff-form"><i class="fas fa-user-plus"></i> Add staff member</a><?php endif; ?></section>
<?php if ($error !== ''): ?><div class="hr-alert hr-error"><?= $e($error) ?></div><?php endif; ?><?php if ($message !== ''): ?><div class="hr-alert hr-success"><?= $e($message) ?></div><?php endif; ?>
<section class="hr-metrics"><div class="hr-metric"><span>Total employee records</span><strong><?= (int)($counts['total'] ?? 0) ?></strong></div><div class="hr-metric"><span>Active</span><strong><?= (int)($counts['active_count'] ?? 0) ?></strong></div><div class="hr-metric"><span>On leave</span><strong><?= (int)($counts['leave_count'] ?? 0) ?></strong></div><div class="hr-metric"><span>Separated</span><strong><?= (int)($counts['separated_count'] ?? 0) ?></strong></div></section>
<?php if (($canCreate && !$edit) || ($edit && $canEdit)): ?><section class="hr-card" id="staff-form"><div class="hr-card-head"><div><strong><?= $edit ? 'Edit employee record' : 'Create employee record' ?></strong><small>Login accounts are optional; an employee record does not create login access.</small></div></div>
<form method="post" class="hr-form"><input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<div class="hr-field"><label>First name *</label><input name="first_name" required maxlength="80" value="<?= $e($edit['first_name'] ?? '') ?>"></div><div class="hr-field"><label>Middle name</label><input name="middle_name" maxlength="80" value="<?= $e($edit['middle_name'] ?? '') ?>"></div><div class="hr-field"><label>Last name *</label><input name="last_name" required maxlength="80" value="<?= $e($edit['last_name'] ?? '') ?>"></div>
<div class="hr-field"><label>Department *</label><select name="department" required><option value="">Select department</option><?php foreach ($departments as $item): ?><option value="<?= $e($item) ?>" <?= (($edit['department'] ?? '') === $item) ? 'selected' : '' ?>><?= $e($item) ?></option><?php endforeach; ?></select></div><div class="hr-field"><label>Job title *</label><input name="job_title" required maxlength="120" value="<?= $e($edit['job_title'] ?? '') ?>" placeholder="e.g. Staff Nurse"></div><div class="hr-field"><label>Employment type</label><select name="employment_type"><?php foreach ($employmentTypes as $item): ?><option value="<?= $e($item) ?>" <?= (($edit['employment_type'] ?? 'Permanent') === $item) ? 'selected' : '' ?>><?= $e($item) ?></option><?php endforeach; ?></select></div>
<div class="hr-field"><label>Phone</label><input name="phone" maxlength="30" value="<?= $e($edit['phone'] ?? '') ?>"></div><div class="hr-field"><label>Work email</label><input type="email" name="email" maxlength="190" value="<?= $e($edit['email'] ?? '') ?>"></div><div class="hr-field"><label>Linked login account</label><select name="linked_user_id"><option value="0">No linked account</option><?php if ($users): while ($u = $users->fetch_assoc()): ?><option value="<?= (int)$u['id'] ?>" <?= ((int)($edit['linked_user_id'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>><?= $e($u['full_name'].' · '.$u['username']) ?></option><?php endwhile; $users->data_seek(0); endif; ?></select></div>
<div class="hr-field"><label>Hire date</label><input type="date" name="hire_date" value="<?= $e($edit['hire_date'] ?? '') ?>"></div><div class="hr-field"><label>Contract end date</label><input type="date" name="contract_end_date" value="<?= $e($edit['contract_end_date'] ?? '') ?>"></div><div class="hr-field"><label>Employment status</label><select name="status"><?php foreach ($statuses as $item): ?><option value="<?= $e($item) ?>" <?= (($edit['status'] ?? 'Active') === $item) ? 'selected' : '' ?>><?= $e($item) ?></option><?php endforeach; ?></select></div>
<div class="hr-field hr-field-wide"><label>HR notes (avoid passwords or unnecessary sensitive data)</label><textarea name="notes" maxlength="1000"><?= $e($edit['notes'] ?? '') ?></textarea></div><div class="hr-form-actions"><button class="hr-btn hr-btn-dark" type="submit"><i class="fas fa-save"></i> Save employee</button><?php if ($edit): ?><a class="hr-btn hr-btn-muted" href="index.php">Cancel edit</a><?php endif; ?></div></form></section><?php endif; ?>
<section class="hr-card"><div class="hr-card-head"><div><strong>Employee register</strong><small>Search by staff number, name, contact, role or filter by department/status.</small></div></div><form method="get" class="hr-filters"><input type="search" name="q" value="<?= $e($search) ?>" placeholder="Search staff directory"><select name="department"><option value="">All departments</option><?php foreach ($departments as $item): ?><option value="<?= $e($item) ?>" <?= $filterDepartment === $item ? 'selected' : '' ?>><?= $e($item) ?></option><?php endforeach; ?></select><select name="status"><option value="">All statuses</option><?php foreach ($statuses as $item): ?><option value="<?= $e($item) ?>" <?= $filterStatus === $item ? 'selected' : '' ?>><?= $e($item) ?></option><?php endforeach; ?></select><button class="hr-btn hr-btn-dark" type="submit"><i class="fas fa-search"></i> Search</button><a class="hr-btn hr-btn-muted" href="index.php">Reset</a></form><div class="hr-table-wrap"><table class="hr-table"><thead><tr><th>Staff number / name</th><th>Department / title</th><th>Contact</th><th>Employment</th><th>Linked account</th><th>Status</th><th>Action</th></tr></thead><tbody><?php if ($staffRows && $staffRows->num_rows > 0): while ($row = $staffRows->fetch_assoc()): ?><tr><td><span class="hr-name"><?= $e($row['staff_no'] ?: 'Pending number') ?></span><span class="hr-muted"><?= $e(trim($row['first_name'].' '.$row['middle_name'].' '.$row['last_name'])) ?></span></td><td><span class="hr-name"><?= $e($row['department']) ?></span><span class="hr-muted"><?= $e($row['job_title']) ?></span></td><td><?= $e($row['phone'] ?: '—') ?><span class="hr-muted"><?= $e($row['email'] ?: 'No email') ?></span></td><td><?= $e($row['employment_type']) ?><span class="hr-muted">Hired: <?= $e($row['hire_date'] ?: 'Not recorded') ?></span></td><td><?= $e($row['account_name'] ?: 'Not linked') ?></td><td><span class="hr-status <?= $row['status'] === 'Active' ? 'hr-status-active' : ($row['status'] === 'On Leave' ? 'hr-status-leave' : 'hr-status-separated') ?>"><?= $e($row['status']) ?></span></td><td><?php if ($canEdit): ?><a class="hr-btn hr-btn-muted" href="?edit=<?= (int)$row['id'] ?>#staff-form"><i class="fas fa-pen"></i> Edit</a><?php else: ?>—<?php endif; ?></td></tr><?php endwhile; else: ?><tr><td class="hr-empty" colspan="7">No staff records found. Add your first employee or change the search filters.</td></tr><?php endif; ?></tbody></table></div></section>
</div></div>
