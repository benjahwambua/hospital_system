<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_module_access($conn, 'icu', 'view');

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
$canRecord = can_module_action($conn, 'icu', 'create');
$message = '';
$today = date('Y-m-d');

// ICU/HDU beds remain part of the canonical inpatient ward and bed register.
// The admission's ward_name and bed_number remain the source of truth for occupancy.
$census = [];
$sql = "SELECT a.id AS admission_id, a.patient_id, a.ward_name, a.bed_number, a.admit_date,
               p.patient_number, p.full_name
        FROM admissions a
        JOIN patients p ON p.id = a.patient_id
        WHERE a.status = 'Admitted'
          AND (UPPER(a.ward_name) LIKE '%ICU%' OR UPPER(a.ward_name) LIKE '%HDU%')
        ORDER BY a.ward_name, a.bed_number";
$result = $conn->query($sql);
if ($result) { while ($row = $result->fetch_assoc()) $census[] = $row; $result->free(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'record_fluid') {
    if (!$canRecord) {
        $message = '<div class="alert alert-danger">You do not have permission to record ICU/HDU fluid entries.</div>';
    } elseif (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $message = '<div class="alert alert-danger">Your session token has expired. Refresh and try again.</div>';
    } else {
        $admissionId = (int)($_POST['admission_id'] ?? 0);
        $entryType = (string)($_POST['entry_type'] ?? '');
        $category = trim((string)($_POST['category'] ?? ''));
        $amount = filter_var($_POST['amount_ml'] ?? null, FILTER_VALIDATE_FLOAT);
        $recordedAtInput = trim((string)($_POST['recorded_at'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $validDate = DateTime::createFromFormat('Y-m-d\\TH:i', $recordedAtInput);
        if ($admissionId <= 0 || !in_array($entryType, ['Intake', 'Output'], true) || $category === '' || $amount === false || $amount <= 0 || !$validDate || $userId <= 0) {
            $message = '<div class="alert alert-danger">Complete all required fields with a valid positive amount.</div>';
        } else {
            $conn->begin_transaction();
            try {
                $lock = $conn->prepare("SELECT id, patient_id, ward_name FROM admissions WHERE id = ? AND status = 'Admitted' FOR UPDATE");
                if (!$lock) throw new Exception('Unable to verify the inpatient admission.');
                $lock->bind_param('i', $admissionId);
                if (!$lock->execute()) throw new Exception('Unable to verify the inpatient admission.');
                $admission = $lock->get_result()->fetch_assoc();
                $lock->close();
                if (!$admission || (stripos($admission['ward_name'], 'ICU') === false && stripos($admission['ward_name'], 'HDU') === false)) {
                    throw new Exception('The selected patient is no longer admitted to an ICU/HDU ward.');
                }
                $recordedAt = $validDate->format('Y-m-d H:i:s');
                $stmt = $conn->prepare('INSERT INTO icu_fluid_entries (admission_id, patient_id, entry_type, category, amount_ml, recorded_at, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                if (!$stmt) throw new Exception('Unable to prepare the fluid entry.');
                $stmt->bind_param('iissdssi', $admissionId, $admission['patient_id'], $entryType, $category, $amount, $recordedAt, $notes, $userId);
                if (!$stmt->execute()) throw new Exception('Unable to save the fluid entry.');
                $entryId = (int)$stmt->insert_id;
                $stmt->close();
                if (function_exists('audit')) audit('icu_fluid_entry_recorded', "entry_id={$entryId},admission_id={$admissionId},type={$entryType},amount_ml={$amount}");
                $conn->commit();
                $_SESSION['msg_success'] = 'Fluid entry recorded successfully.';
                header('Location: index.php?admission_id=' . $admissionId);
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('ICU fluid entry error: ' . $e->getMessage());
                $message = '<div class="alert alert-danger">The fluid entry could not be saved. No changes were made.</div>';
            }
        }
    }
}

$selectedAdmissionId = (int)($_GET['admission_id'] ?? $_POST['admission_id'] ?? 0);
$selectedAdmission = null;
foreach ($census as $row) { if ((int)$row['admission_id'] === $selectedAdmissionId) { $selectedAdmission = $row; break; } }
if (!$selectedAdmission && $selectedAdmissionId > 0) $selectedAdmissionId = 0;
$fluidRows = [];
$totals = ['Intake' => 0.0, 'Output' => 0.0];
if ($selectedAdmission) {
    $stmt = $conn->prepare("SELECT id, entry_type, category, amount_ml, recorded_at, notes FROM icu_fluid_entries WHERE admission_id = ? AND DATE(recorded_at) = ? ORDER BY recorded_at DESC, id DESC");
    if ($stmt) {
        $stmt->bind_param('is', $selectedAdmissionId, $today);
        if ($stmt->execute()) {
            $rows = $stmt->get_result();
            while ($row = $rows->fetch_assoc()) { $fluidRows[] = $row; $totals[$row['entry_type']] += (float)$row['amount_ml']; }
        }
        $stmt->close();
    }
}
$netBalance = $totals['Intake'] - $totals['Output'];
$pageTitle = 'ICU / HDU';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="container-fluid py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">ICU / High Dependency Unit</h1><p class="text-muted mb-0">Critical-care census and fluid balance</p></div>
    <span class="badge badge-info">Inpatient-linked</span>
  </div>
  <?= $message ?>
  <?php if (!empty($_SESSION['msg_success'])): ?><div class="alert alert-success"><?= htmlspecialchars((string)$_SESSION['msg_success'], ENT_QUOTES, 'UTF-8') ?></div><?php unset($_SESSION['msg_success']); endif; ?>
  <div class="row mb-4">
    <div class="col-md-4 mb-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Current ICU/HDU patients</div><div class="h2 mb-0"><?= count($census) ?></div></div></div></div>
    <div class="col-md-4 mb-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Today's intake — selected patient</div><div class="h2 mb-0"><?= number_format($totals['Intake'], 0) ?> <small class="text-muted">ml</small></div></div></div></div>
    <div class="col-md-4 mb-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Today's net balance — selected patient</div><div class="h2 mb-0"><?= number_format($netBalance, 0) ?> <small class="text-muted">ml</small></div></div></div></div>
  </div>
  <div class="card shadow-sm mb-4"><div class="card-header bg-white"><h2 class="h6 mb-0">ICU / HDU Census</h2></div><div class="card-body p-0">
    <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Patient</th><th>Patient No.</th><th>Ward</th><th>Bed</th><th>Admission date</th><th></th></tr></thead><tbody>
    <?php if (!$census): ?><tr><td colspan="6" class="text-center text-muted py-4">No active admissions are currently assigned to an ICU/HDU ward. Assign patients through the existing Ward / IPD workflow.</td></tr><?php endif; ?>
    <?php foreach ($census as $patient): ?><tr><td><?= htmlspecialchars($patient['full_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($patient['patient_number'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($patient['ward_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int)$patient['bed_number'] ?></td><td><?= htmlspecialchars((string)$patient['admit_date'], ENT_QUOTES, 'UTF-8') ?></td><td><a class="btn btn-sm btn-outline-primary" href="?admission_id=<?= (int)$patient['admission_id'] ?>">Fluid chart</a></td></tr><?php endforeach; ?>
    </tbody></table></div></div></div>
  <?php if ($selectedAdmission): ?>
  <div class="row">
    <div class="col-lg-5 mb-4"><div class="card shadow-sm h-100"><div class="card-header bg-white"><h2 class="h6 mb-0">Record fluid balance</h2></div><div class="card-body">
      <p class="small text-muted mb-3"><?= htmlspecialchars($selectedAdmission['full_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($selectedAdmission['patient_number'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($selectedAdmission['ward_name'], ENT_QUOTES, 'UTF-8') ?> / Bed <?= (int)$selectedAdmission['bed_number'] ?></p>
      <?php if ($canRecord): ?><form method="post"><input type="hidden" name="action" value="record_fluid"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="admission_id" value="<?= (int)$selectedAdmissionId ?>">
        <div class="form-group"><label>Entry type</label><select class="form-control" name="entry_type" required><option value="Intake">Intake</option><option value="Output">Output</option></select></div>
        <div class="form-group"><label>Category / source</label><input class="form-control" name="category" maxlength="100" required placeholder="e.g. Oral fluids, IV fluids, urine"></div>
        <div class="form-group"><label>Amount (ml)</label><input class="form-control" type="number" name="amount_ml" min="0.01" step="0.01" required></div>
        <div class="form-group"><label>Date and time</label><input class="form-control" type="datetime-local" name="recorded_at" value="<?= date('Y-m-d\\TH:i') ?>" required></div>
        <div class="form-group"><label>Notes (optional)</label><textarea class="form-control" name="notes" rows="2" maxlength="500"></textarea></div>
        <button class="btn btn-primary" type="submit">Save fluid entry</button></form>
      <?php else: ?><div class="alert alert-warning mb-0">You have view access only. Ask a Super User to grant ICU/HDU create permission if you are authorised to record clinical entries.</div><?php endif; ?>
    </div></div></div>
    <div class="col-lg-7 mb-4"><div class="card shadow-sm h-100"><div class="card-header bg-white"><h2 class="h6 mb-0">Today's fluid entries</h2></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><th>Time</th><th>Type</th><th>Category</th><th class="text-right">Amount (ml)</th></tr></thead><tbody>
      <?php if (!$fluidRows): ?><tr><td colspan="4" class="text-center text-muted py-4">No fluid entries recorded for this patient today.</td></tr><?php endif; ?>
      <?php foreach ($fluidRows as $entry): ?><tr><td><?= htmlspecialchars($entry['recorded_at'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($entry['entry_type'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($entry['category'], ENT_QUOTES, 'UTF-8') ?></td><td class="text-right"><?= number_format((float)$entry['amount_ml'], 2) ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div><div class="card-footer bg-white small d-flex justify-content-between"><span>Intake: <?= number_format($totals['Intake'], 2) ?> ml</span><span>Output: <?= number_format($totals['Output'], 2) ?> ml</span><strong>Net: <?= number_format($netBalance, 2) ?> ml</strong></div></div></div>
  </div><?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>