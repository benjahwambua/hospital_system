<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_module_access($conn, 'clinical', 'view');
$canPost = can_module_action($conn, 'finance', 'create');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
$message = '';
$chargeDate = (string)($_POST['charge_date'] ?? $_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $chargeDate) || $chargeDate > date('Y-m-d')) $chargeDate = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'post_daily_charge') {
    if (!$canPost) {
        $message = "<div class='alert alert-danger'>Finance Create permission is required to post inpatient charges.</div>";
    } elseif (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $message = "<div class='alert alert-danger'>Invalid security token. Refresh and try again.</div>";
    } else {
        $admissionId = (int)($_POST['admission_id'] ?? 0);
        $chargeDate = trim((string)($_POST['charge_date'] ?? date('Y-m-d')));
        try {
            if ($admissionId <= 0 || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $chargeDate) || $chargeDate > date('Y-m-d')) {
                throw new RuntimeException('Choose a valid charge date that is not in the future.');
            }
            $conn->begin_transaction();
            $stmt = $conn->prepare("SELECT a.id,a.patient_id,a.visit_id,a.admit_date,a.ward_name,a.bed_number,w.id AS ward_id,w.daily_rate FROM admissions a JOIN inpatient_wards w ON w.name=a.ward_name AND w.is_active=1 JOIN inpatient_beds b ON b.ward_id=w.id AND b.bed_number=a.bed_number AND b.is_active=1 WHERE a.id=? AND a.status='Admitted' LIMIT 1 FOR UPDATE");
            if (!$stmt) throw new RuntimeException('Unable to load active inpatient stay.');
            $stmt->bind_param('i', $admissionId);
            $stmt->execute();
            $stay = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$stay) throw new RuntimeException('Active inpatient stay or active ward/bed configuration was not found.');
            if (substr((string)$stay['admit_date'], 0, 10) > $chargeDate) throw new RuntimeException('Charge date cannot be before admission.');
            $rate = (float)$stay['daily_rate'];
            if ($rate <= 0) throw new RuntimeException('This ward has no daily rate configured. Set the rate in Ward & Bed Configuration first.');
            $stmt = $conn->prepare("SELECT id FROM inpatient_daily_charges WHERE admission_id=? AND charge_date=? LIMIT 1");
            if (!$stmt) throw new RuntimeException('Unable to check duplicate daily charge.');
            $stmt->bind_param('is', $admissionId, $chargeDate);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($existing) throw new RuntimeException('A daily ward charge has already been posted for this admission and date.');
            $patientId = (int)$stay['patient_id'];
            $visitId = (int)($stay['visit_id'] ?? 0);
            $invoiceId = $visitId > 0 ? get_or_create_invoice($conn, $patientId, null, $visitId) : create_invoice($conn, $patientId, null, null, 'unpaid');
            if ($invoiceId <= 0) throw new RuntimeException('Unable to create or locate an invoice.');
            $description = 'Inpatient ward stay — ' . $stay['ward_name'] . ', bed ' . (int)$stay['bed_number'] . ' (' . $chargeDate . ')';
            $itemId = add_invoice_item($conn, $invoiceId, $description, 1, $rate, 'service');
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $stmt = $conn->prepare("INSERT INTO inpatient_daily_charges(admission_id,patient_id,ward_id,ward_name,bed_number,charge_date,daily_rate,invoice_id,invoice_item_id,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)");
            if (!$stmt) throw new RuntimeException('Unable to prepare daily-charge ledger entry.');
            $wardId = (int)$stay['ward_id']; $bedNumber = (int)$stay['bed_number'];
            $stmt->bind_param('iis sisdiii', $admissionId, $patientId, $wardId, $stay['ward_name'], $bedNumber, $chargeDate, $rate, $invoiceId, $itemId, $userId);
            // Bind parameter type strings cannot contain spaces.
            if (!$stmt->execute()) throw new RuntimeException('Unable to save the daily-charge ledger entry.');
            $stmt->close();
            if (function_exists('audit')) audit('inpatient_daily_charge_posted', "admission_id={$admissionId},charge_date={$chargeDate},invoice_id={$invoiceId},amount={$rate}");
            $conn->commit();
            $_SESSION['msg_success'] = 'Daily ward charge posted to invoice #' . $invoiceId . '.';
            header('Location: inpatient_charges.php?date=' . urlencode($chargeDate)); exit;
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('Inpatient daily charge error: ' . $e->getMessage());
            $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
        }
    }
}
$stays = [];
$stmt = $conn->prepare("SELECT a.id AS admission_id,a.patient_id,a.visit_id,a.admit_date,a.ward_name,a.bed_number,p.full_name,p.patient_number,w.daily_rate,dc.id AS charge_id,dc.invoice_id,dc.daily_rate AS posted_rate FROM admissions a JOIN patients p ON p.id=a.patient_id LEFT JOIN inpatient_wards w ON w.name=a.ward_name LEFT JOIN inpatient_daily_charges dc ON dc.admission_id=a.id AND dc.charge_date=? WHERE a.status='Admitted' ORDER BY w.name,a.bed_number,p.full_name");
if ($stmt) {
    $stmt->bind_param('s', $chargeDate);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $stays[] = $row;
    $stmt->close();
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4 pb-5" style="max-width:1500px">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4"><div><div class="text-uppercase text-muted small font-weight-bold">Clinical · Inpatient Care</div><h1 class="h3 font-weight-bold">Inpatient Daily Charges</h1><p class="text-muted mb-0">Post one auditable ward-stay charge per admission per date into the canonical invoice ledger.</p></div><a href="ward_management.php" class="btn btn-outline-primary">Back to Ward / IPD</a></div>
  <?php if (!empty($_SESSION['msg_success'])): ?><div class="alert alert-success"><?=htmlspecialchars($_SESSION['msg_success'])?></div><?php unset($_SESSION['msg_success']); endif; ?>
  <?=$message?>
  <div class="card shadow-sm mb-4"><div class="card-body"><form method="get" class="form-inline"><label class="mr-2 font-weight-bold" for="charge-date">Charge date</label><input id="charge-date" type="date" name="date" class="form-control mr-2" max="<?=date('Y-m-d')?>" value="<?=htmlspecialchars($chargeDate)?>" required><button class="btn btn-primary">Load stays</button><span class="text-muted ml-3 small">Posting is manual and duplicate-protected.</span></form></div></div>
  <div class="card shadow-sm"><div class="card-body"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Patient</th><th>Ward / Bed</th><th>Admitted</th><th>Daily rate</th><th>Charge status</th><th>Action</th></tr></thead><tbody>
  <?php if (!$stays): ?><tr><td colspan="6" class="text-center text-muted py-4">No active inpatient stays found.</td></tr><?php endif; ?>
  <?php foreach ($stays as $stay): ?><tr><td class="font-weight-bold"><?=htmlspecialchars($stay['full_name'])?><small class="d-block text-muted"><?=htmlspecialchars($stay['patient_number'])?></small></td><td><?=htmlspecialchars($stay['ward_name'])?> · Bed <?=(int)$stay['bed_number']?></td><td><?=htmlspecialchars($stay['admit_date'])?></td><td><?=number_format((float)$stay['daily_rate'],2)?></td><td><?php if (!empty($stay['charge_id'])): ?><span class="badge badge-success">Posted · Invoice #<?=(int)$stay['invoice_id']?></span><?php elseif ((float)$stay['daily_rate']<=0): ?><span class="badge badge-warning">Rate not configured</span><?php else: ?><span class="badge badge-secondary">Not posted</span><?php endif; ?></td><td><?php if (empty($stay['charge_id']) && (float)$stay['daily_rate']>0 && $canPost): ?><form method="post" onsubmit="return confirm('Post this ward daily charge to the patient invoice?');"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>"><input type="hidden" name="action" value="post_daily_charge"><input type="hidden" name="admission_id" value="<?=(int)$stay['admission_id']?>"><input type="hidden" name="charge_date" value="<?=htmlspecialchars($chargeDate)?>"><button class="btn btn-sm btn-primary">Post charge</button></form><?php elseif (!$canPost): ?><span class="text-muted small">Finance Create permission required</span><?php else: ?><span class="text-muted small">Already posted / rate unavailable</span><?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
