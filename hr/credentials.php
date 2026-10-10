<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'hr_staff', 'view');
$canCreate = can_module_action($conn, 'hr_staff', 'create');
$canEdit = can_module_action($conn, 'hr_staff', 'edit');
$csrf = csrf_token();
$error = ''; $message = '';
$types = ['Professional Licence','Practice Licence','Academic Qualification','CPR / BLS','Specialist Certification','Training Certificate','Other'];
$statuses = ['Active','Suspended','Revoked'];
$validDate = static function(string $v): bool { if ($v === '') return true; $d = DateTime::createFromFormat('!Y-m-d',$v); return $d instanceof DateTime && $d->format('Y-m-d') === $v; };
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $error = 'Your security token expired. Refresh and try again.';
    elseif (($id > 0 && !$canEdit) || ($id === 0 && !$canCreate)) { http_response_code(403); exit('Forbidden: HR credential permission required.'); }
    else {
        $staffId = (int)($_POST['staff_id'] ?? 0);
        $type = trim((string)($_POST['credential_type'] ?? ''));
        $name = trim((string)($_POST['credential_name'] ?? ''));
        $authority = trim((string)($_POST['issuing_authority'] ?? ''));
        $registration = trim((string)($_POST['registration_number'] ?? ''));
        $issue = trim((string)($_POST['issue_date'] ?? ''));
        $expiry = trim((string)($_POST['expiry_date'] ?? ''));
        $status = (string)($_POST['status'] ?? 'Active');
        $notes = trim((string)($_POST['notes'] ?? ''));
        if ($staffId <= 0 || $type === '' || !in_array($type,$types,true) || $name === '' || !in_array($status,$statuses,true)) $error = 'Choose a staff member and provide valid credential details.';
        elseif (!$validDate($issue) || !$validDate($expiry) || ($issue !== '' && $expiry !== '' && $expiry < $issue)) $error = 'Check the credential dates; expiry cannot precede issue date.';
        elseif (strlen($authority)>160 || strlen($registration)>100 || strlen($notes)>500) $error = 'One or more credential fields exceed the allowed length.';
        else {
            $check = $conn->prepare('SELECT id FROM hr_staff WHERE id=? LIMIT 1');
            if (!$check) $error = 'Unable to validate the selected staff record.';
            else {
                $check->bind_param('i',$staffId); $check->execute(); $validStaff = $check->get_result()->fetch_assoc(); $check->close();
                if (!$validStaff) $error = 'Selected staff record was not found.';
                else {
                    $authorityValue = $authority !== '' ? $authority : null; $registrationValue = $registration !== '' ? $registration : null;
                    $issueValue = $issue !== '' ? $issue : null; $expiryValue = $expiry !== '' ? $expiry : null; $notesValue = $notes !== '' ? $notes : null;
                    $actor = (int)($_SESSION['user_id'] ?? 0);
                    if ($id > 0) {
                        $stmt = $conn->prepare('UPDATE hr_staff_credentials SET staff_id=?,credential_type=?,credential_name=?,issuing_authority=?,registration_number=?,issue_date=?,expiry_date=?,status=?,notes=?,updated_by=? WHERE id=?');
                        if ($stmt) {
                            $stmt->bind_param('issssssssii',$staffId,$type,$name,$authorityValue,$registrationValue,$issueValue,$expiryValue,$status,$notesValue,$actor,$id);
                            if ($stmt->execute() && $stmt->affected_rows >= 0) { $message='Credential record updated.'; if (function_exists('audit')) audit('hr_credential_updated','credential_id='.$id.',staff_id='.$staffId.',status='.$status); }
                            else $error='Credential record could not be updated.';
                            $stmt->close();
                        } else $error='Unable to prepare credential update.';
                    } else {
                        $stmt = $conn->prepare('INSERT INTO hr_staff_credentials (staff_id,credential_type,credential_name,issuing_authority,registration_number,issue_date,expiry_date,status,notes,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                        if ($stmt) {
                            $stmt->bind_param('issssssssii',$staffId,$type,$name,$authorityValue,$registrationValue,$issueValue,$expiryValue,$status,$notesValue,$actor,$actor);
                            if ($stmt->execute()) { $newId=(int)$conn->insert_id; $message='Credential record added.'; if (function_exists('audit')) audit('hr_credential_created','credential_id='.$newId.',staff_id='.$staffId.',type='.$type); }
                            else $error='Credential record could not be saved.';
                            $stmt->close();
                        } else $error='Unable to prepare credential record.';
                    }
                }
            }
        }
    }
}
$editId=(int)($_GET['edit']??0); $edit=null;
if ($editId>0 && $canEdit) { $stmt=$conn->prepare('SELECT * FROM hr_staff_credentials WHERE id=? LIMIT 1'); if($stmt){$stmt->bind_param('i',$editId);$stmt->execute();$edit=$stmt->get_result()->fetch_assoc();$stmt->close();} }
$staff=$conn->query("SELECT id,staff_no,first_name,middle_name,last_name,department,job_title FROM hr_staff WHERE status <> 'Separated' ORDER BY department,last_name,first_name");
$rows=$conn->query("SELECT c.*,s.staff_no,s.first_name,s.middle_name,s.last_name,s.department,s.job_title, CASE WHEN c.status <> 'Active' THEN c.status WHEN c.expiry_date IS NOT NULL AND c.expiry_date < CURDATE() THEN 'Expired' WHEN c.expiry_date IS NOT NULL AND c.expiry_date <= DATE_ADD(CURDATE(),INTERVAL 30 DAY) THEN 'Expiring soon' ELSE 'Current' END AS expiry_state FROM hr_staff_credentials c JOIN hr_staff s ON s.id=c.staff_id ORDER BY CASE WHEN c.status <> 'Active' THEN 3 WHEN c.expiry_date < CURDATE() THEN 0 WHEN c.expiry_date <= DATE_ADD(CURDATE(),INTERVAL 30 DAY) THEN 1 ELSE 2 END,c.expiry_date,s.last_name");
$summary=$conn->query("SELECT COUNT(*) total,SUM(status='Active' AND expiry_date IS NOT NULL AND expiry_date<CURDATE()) expired,SUM(status='Active' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)) expiring FROM hr_staff_credentials");
$counts=$summary?$summary->fetch_assoc():['total'=>0,'expired'=>0,'expiring'=>0];
$e=static fn($v)=>htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');
include __DIR__.'/../includes/header.php'; include __DIR__.'/../includes/sidebar.php';
?>
<style>
.hrc-page{padding:28px 24px 50px;background:#f3f6fb;min-height:calc(100vh - 75px)}.hrc-wrap{max-width:1450px;margin:auto}.hrc-hero{padding:27px 30px;border-radius:20px;color:#fff;background:linear-gradient(125deg,#083d7b,#1264bd 60%,#13a8b8);margin-bottom:20px;display:flex;justify-content:space-between;gap:15px;align-items:center;flex-wrap:wrap}.hrc-hero h2{margin:0;color:#fff;font-size:1.7rem;font-weight:800}.hrc-hero p{margin:7px 0 0;color:#e1efff}.hrc-card{background:#fff;border:1px solid #e1e8f0;border-radius:16px;overflow:hidden;box-shadow:0 6px 22px rgba(20,40,70,.05);margin-bottom:18px}.hrc-head{padding:16px 20px;border-bottom:1px solid #e9eef4;font-weight:800;color:#172b45}.hrc-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:18px}.hrc-metric{background:#fff;border:1px solid #e1e8f0;border-radius:14px;padding:18px}.hrc-metric small{display:block;color:#68778b;text-transform:uppercase;font-size:.68rem;font-weight:800}.hrc-metric strong{display:block;font-size:1.5rem;color:#172b45;margin-top:6px}.hrc-form{padding:20px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px}.hrc-field label{display:block;font-size:.68rem;text-transform:uppercase;font-weight:800;color:#65748a;margin-bottom:6px}.hrc-field input,.hrc-field select,.hrc-field textarea{width:100%;border:1px solid #d6e0eb;border-radius:9px;padding:10px 11px;background:#fff}.hrc-wide{grid-column:1/-1}.hrc-btn{border:0;border-radius:9px;padding:10px 13px;font-weight:800;display:inline-flex;align-items:center;gap:7px;text-decoration:none;cursor:pointer}.hrc-primary{background:#104d91;color:#fff}.hrc-muted{background:#edf3fa;color:#24415f}.hrc-alert{padding:12px 15px;border-radius:10px;margin-bottom:15px;font-weight:700}.hrc-error{background:#fff1f1;color:#a11}.hrc-success{background:#ecfdf5;color:#087443}.hrc-tablewrap{overflow:auto}.hrc-table{width:100%;border-collapse:collapse;min-width:900px}.hrc-table th,.hrc-table td{text-align:left;padding:12px 14px;border-bottom:1px solid #edf1f5;font-size:.83rem}.hrc-table th{background:#f7f9fc;color:#68778b;text-transform:uppercase;font-size:.65rem;letter-spacing:.06em}.hrc-name{font-weight:800;color:#172b45}.hrc-sub{display:block;color:#77869a;font-size:.74rem;margin-top:3px}.hrc-pill{display:inline-block;padding:5px 8px;border-radius:99px;background:#edf2f7;color:#475569;font-size:.66rem;font-weight:800}.hrc-current{background:#e9fbf1;color:#087443}.hrc-expired{background:#fff0f0;color:#a22}.hrc-soon{background:#fff7e6;color:#996300}@media(max-width:850px){.hrc-form{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.hrc-page{padding:18px 12px}.hrc-form,.hrc-metrics{grid-template-columns:1fr}.hrc-hero{padding:22px}}
</style>
<div class="hrc-page"><div class="hrc-wrap"><section class="hrc-hero"><div><div style="font-size:.68rem;letter-spacing:.15em;text-transform:uppercase;font-weight:800;margin-bottom:7px;opacity:.8">HR compliance</div><h2>Professional Credentials</h2><p>Track staff licences, registrations, training certificates and expiry dates.</p></div><a class="hrc-btn" style="background:#fff;color:#0b4e8b" href="/hospital_system/hr/index.php"><i class="fas fa-arrow-left"></i> Staff Directory</a></section>
<?php if($error!==''):?><div class="hrc-alert hrc-error"><?= $e($error) ?></div><?php endif;?><?php if($message!==''):?><div class="hrc-alert hrc-success"><?= $e($message) ?></div><?php endif;?>
<section class="hrc-metrics"><div class="hrc-metric"><small>Credential records</small><strong><?= (int)($counts['total']??0) ?></strong></div><div class="hrc-metric"><small>Expired active credentials</small><strong><?= (int)($counts['expired']??0) ?></strong></div><div class="hrc-metric"><small>Expiring in 30 days</small><strong><?= (int)($counts['expiring']??0) ?></strong></div></section>
<?php if(($canCreate&&!$edit)||($edit&&$canEdit)):?><section class="hrc-card"><div class="hrc-head"><?= $edit?'Edit credential':'Add credential' ?></div><form method="post" class="hrc-form"><input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
<div class="hrc-field"><label>Staff member *</label><select name="staff_id" required><option value="">Select employee</option><?php if($staff):while($s=$staff->fetch_assoc()):?><option value="<?= (int)$s['id'] ?>" <?= ((int)($edit['staff_id']??0)===(int)$s['id'])?'selected':'' ?>><?= $e(($s['staff_no']?:'STAFF'). ' · '.$s['first_name'].' '.$s['last_name'].' — '.$s['department']) ?></option><?php endwhile;$staff->data_seek(0);endif;?></select></div>
<div class="hrc-field"><label>Credential type *</label><select name="credential_type" required><?php foreach($types as $t):?><option value="<?= $e($t) ?>" <?= (($edit['credential_type']??'')===$t)?'selected':'' ?>><?= $e($t) ?></option><?php endforeach;?></select></div><div class="hrc-field"><label>Credential / qualification *</label><input name="credential_name" required maxlength="160" value="<?= $e($edit['credential_name']??'') ?>" placeholder="e.g. Nursing Council practising licence"></div>
<div class="hrc-field"><label>Issuing authority</label><input name="issuing_authority" maxlength="160" value="<?= $e($edit['issuing_authority']??'') ?>"></div><div class="hrc-field"><label>Registration number</label><input name="registration_number" maxlength="100" value="<?= $e($edit['registration_number']??'') ?>"></div><div class="hrc-field"><label>Record status</label><select name="status"><?php foreach($statuses as $st):?><option value="<?= $e($st) ?>" <?= (($edit['status']??'Active')===$st)?'selected':'' ?>><?= $e($st) ?></option><?php endforeach;?></select></div>
<div class="hrc-field"><label>Issue date</label><input type="date" name="issue_date" value="<?= $e($edit['issue_date']??'') ?>"></div><div class="hrc-field"><label>Expiry date</label><input type="date" name="expiry_date" value="<?= $e($edit['expiry_date']??'') ?>"></div><div class="hrc-field hrc-wide"><label>Notes</label><textarea name="notes" maxlength="500"><?= $e($edit['notes']??'') ?></textarea></div><div class="hrc-wide"><button class="hrc-btn hrc-primary" type="submit"><i class="fas fa-save"></i> Save credential</button><?php if($edit):?> <a class="hrc-btn hrc-muted" href="credentials.php">Cancel</a><?php endif;?></div></form></section><?php endif;?>
<section class="hrc-card"><div class="hrc-head">Credential register · expiry exceptions appear first</div><div class="hrc-tablewrap"><table class="hrc-table"><thead><tr><th>Staff member</th><th>Credential</th><th>Authority / number</th><th>Issue / expiry</th><th>Compliance status</th><th>Action</th></tr></thead><tbody><?php if($rows&&$rows->num_rows):while($r=$rows->fetch_assoc()):$state=(string)$r['expiry_state'];$class=$state==='Expired'?'hrc-expired':($state==='Expiring soon'?'hrc-soon':($state==='Current'?'hrc-current':''));?><tr><td><span class="hrc-name"><?= $e(trim($r['first_name'].' '.$r['middle_name'].' '.$r['last_name'])) ?></span><span class="hrc-sub"><?= $e($r['staff_no'].' · '.$r['department'].' · '.$r['job_title']) ?></span></td><td><span class="hrc-name"><?= $e($r['credential_name']) ?></span><span class="hrc-sub"><?= $e($r['credential_type']) ?></span></td><td><?= $e($r['issuing_authority']?:'—') ?><span class="hrc-sub"><?= $e($r['registration_number']?:'No number recorded') ?></span></td><td><?= $e($r['issue_date']?:'—') ?><span class="hrc-sub">Expires: <?= $e($r['expiry_date']?:'Not recorded') ?></span></td><td><span class="hrc-pill <?= $class ?>"><?= $e($state) ?></span></td><td><?php if($canEdit):?><a class="hrc-btn hrc-muted" href="?edit=<?= (int)$r['id'] ?>"><i class="fas fa-pen"></i> Edit</a><?php else:?>—<?php endif;?></td></tr><?php endwhile;else:?><tr><td colspan="6" style="text-align:center;padding:35px;color:#77869a">No credential records yet. Add professional registrations and training certificates as appropriate.</td></tr><?php endif;?></tbody></table></div></section></div></div>
