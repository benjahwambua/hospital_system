<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_module_access($conn, 'clinical', 'view');
$canEdit = can_module_action($conn, 'clinical', 'edit');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canEdit) {
        $message = "<div class='alert alert-danger'>You do not have permission to change ward or bed configuration.</div>";
    } elseif (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $message = "<div class='alert alert-danger'>Invalid security token. Refresh and try again.</div>";
    } else {
        $action = (string)($_POST['action'] ?? '');
        try {
            if ($action === 'add_ward') {
                $name = trim((string)($_POST['ward_name'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                if ($name === '' || mb_strlen($name) > 120) throw new RuntimeException('Enter a ward name up to 120 characters.');
                $stmt = $conn->prepare("INSERT INTO inpatient_wards(name,description,is_active,created_by) VALUES(?,?,1,?)");
                if (!$stmt) throw new RuntimeException('Unable to prepare ward creation.');
                $userId = (int)($_SESSION['user_id'] ?? 0);
                $stmt->bind_param('ssi', $name, $description, $userId);
                if (!$stmt->execute()) throw new RuntimeException('Ward name may already exist.');
                $stmt->close();
                if (function_exists('audit')) audit('inpatient_ward_created', "ward={$name}");
                $_SESSION['msg_success'] = 'Ward added.';
                header('Location: ward_configuration.php'); exit;
            } elseif ($action === 'add_bed') {
                $wardId = (int)($_POST['ward_id'] ?? 0);
                $bedNumber = (int)($_POST['bed_number'] ?? 0);
                $label = trim((string)($_POST['label'] ?? ''));
                if ($wardId <= 0 || $bedNumber <= 0 || $bedNumber > 999) throw new RuntimeException('Choose a ward and a valid positive bed number.');
                $stmt = $conn->prepare("SELECT id,name FROM inpatient_wards WHERE id=? AND is_active=1 LIMIT 1");
                $stmt->bind_param('i', $wardId); $stmt->execute(); $ward = $stmt->get_result()->fetch_assoc(); $stmt->close();
                if (!$ward) throw new RuntimeException('Selected ward is not active.');
                $stmt = $conn->prepare("INSERT INTO inpatient_beds(ward_id,bed_number,label,is_active,created_by) VALUES(?,?,?,1,?)");
                if (!$stmt) throw new RuntimeException('Unable to prepare bed creation.');
                $userId = (int)($_SESSION['user_id'] ?? 0);
                $stmt->bind_param('iisi', $wardId, $bedNumber, $label, $userId);
                if (!$stmt->execute()) throw new RuntimeException('That bed number already exists in this ward.');
                $stmt->close();
                if (function_exists('audit')) audit('inpatient_bed_created', "ward={$ward['name']},bed={$bedNumber}");
                $_SESSION['msg_success'] = 'Bed added.';
                header('Location: ward_configuration.php'); exit;
            } elseif ($action === 'toggle_bed') {
                $bedId = (int)($_POST['bed_id'] ?? 0);
                $stmt = $conn->prepare("SELECT b.id,b.bed_number,b.is_active,w.name AS ward_name FROM inpatient_beds b JOIN inpatient_wards w ON w.id=b.ward_id WHERE b.id=? LIMIT 1");
                $stmt->bind_param('i', $bedId); $stmt->execute(); $bed = $stmt->get_result()->fetch_assoc(); $stmt->close();
                if (!$bed) throw new RuntimeException('Bed not found.');
                if ((int)$bed['is_active'] === 1) {
                    $stmt = $conn->prepare("SELECT id FROM admissions WHERE status='Admitted' AND ward_name=? AND bed_number=? LIMIT 1");
                    $stmt->bind_param('si', $bed['ward_name'], $bed['bed_number']); $stmt->execute(); $occupied = $stmt->get_result()->fetch_assoc(); $stmt->close();
                    if ($occupied) throw new RuntimeException('An occupied bed cannot be deactivated. Transfer or discharge the patient first.');
                }
                $next = (int)$bed['is_active'] === 1 ? 0 : 1;
                $stmt = $conn->prepare("UPDATE inpatient_beds SET is_active=? WHERE id=?");
                $stmt->bind_param('ii', $next, $bedId);
                if (!$stmt->execute()) throw new RuntimeException('Unable to update bed status.');
                $stmt->close();
                if (function_exists('audit')) audit('inpatient_bed_status_changed', "ward={$bed['ward_name']},bed={$bed['bed_number']},active={$next}");
                $_SESSION['msg_success'] = 'Bed status updated.';
                header('Location: ward_configuration.php'); exit;
            } else {
                throw new RuntimeException('Unknown configuration action.');
            }
        } catch (Throwable $e) {
            error_log('Ward configuration error: '.$e->getMessage());
            $message = "<div class='alert alert-danger'>".htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')."</div>";
        }
    }
}
$wards = [];
$q = $conn->query("SELECT w.id,w.name,w.description,w.is_active,COUNT(CASE WHEN b.is_active=1 THEN 1 END) AS active_beds,COUNT(b.id) AS total_beds FROM inpatient_wards w LEFT JOIN inpatient_beds b ON b.ward_id=w.id GROUP BY w.id,w.name,w.description,w.is_active ORDER BY w.name");
if ($q) while ($row = $q->fetch_assoc()) $wards[] = $row;
$beds = [];
$q = $conn->query("SELECT b.id,b.ward_id,b.bed_number,b.label,b.is_active,w.name AS ward_name,EXISTS(SELECT 1 FROM admissions a WHERE a.status='Admitted' AND a.ward_name=w.name AND a.bed_number=b.bed_number) AS is_occupied FROM inpatient_beds b JOIN inpatient_wards w ON w.id=b.ward_id ORDER BY w.name,b.bed_number");
if ($q) while ($row = $q->fetch_assoc()) $beds[] = $row;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid pt-4 pb-5" style="max-width:1400px">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div><div class="text-uppercase text-muted small font-weight-bold">Clinical · Inpatient Care</div><h1 class="h3 font-weight-bold">Ward &amp; Bed Configuration</h1><p class="text-muted mb-0">Maintain the live ward register and active bed capacity. Occupied beds cannot be deactivated.</p></div>
    <a class="btn btn-outline-primary" href="ward_management.php"><i class="fas fa-arrow-left mr-2"></i>Back to Ward / IPD</a>
  </div>
  <?php if (!empty($_SESSION['msg_success'])): ?><div class="alert alert-success"><?=htmlspecialchars($_SESSION['msg_success'])?></div><?php unset($_SESSION['msg_success']); endif; ?>
  <?=$message?>
  <?php if (!$canEdit): ?><div class="alert alert-info">You have view-only access to ward configuration.</div><?php endif; ?>
  <div class="row">
    <div class="col-lg-5">
      <div class="card shadow-sm mb-4"><div class="card-header font-weight-bold">Add Ward</div><div class="card-body">
        <form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>"><input type="hidden" name="action" value="add_ward">
          <div class="form-group"><label>Ward name</label><input class="form-control" name="ward_name" maxlength="120" required></div>
          <div class="form-group"><label>Description / speciality</label><input class="form-control" name="description" maxlength="255"></div>
          <button class="btn btn-primary" <?=(!$canEdit?'disabled':'')?>><i class="fas fa-plus mr-1"></i>Add ward</button>
        </form>
      </div></div>
      <div class="card shadow-sm mb-4"><div class="card-header font-weight-bold">Add Bed</div><div class="card-body">
        <form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>"><input type="hidden" name="action" value="add_bed">
          <div class="form-group"><label>Ward</label><select class="form-control" name="ward_id" required><?php foreach ($wards as $ward): if ((int)$ward['is_active'] !== 1) continue; ?><option value="<?=(int)$ward['id']?>"><?=htmlspecialchars($ward['name'])?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>Bed number</label><input type="number" class="form-control" min="1" max="999" name="bed_number" required></div>
          <div class="form-group"><label>Bed label (optional)</label><input class="form-control" maxlength="80" name="label" placeholder="e.g. Isolation 1"></div>
          <button class="btn btn-primary" <?=(!$canEdit?'disabled':'')?>><i class="fas fa-plus mr-1"></i>Add bed</button>
        </form>
      </div></div>
    </div>
    <div class="col-lg-7"><div class="card shadow-sm mb-4"><div class="card-header font-weight-bold">Ward Register</div><div class="card-body"><div class="table-responsive"><table class="table table-hover">
      <thead><tr><th>Ward</th><th>Description</th><th>Active beds</th><th>Total records</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($wards as $ward): ?><tr><td class="font-weight-bold"><?=htmlspecialchars($ward['name'])?></td><td><?=htmlspecialchars($ward['description'] ?? '')?></td><td><?=(int)$ward['active_beds']?></td><td><?=(int)$ward['total_beds']?></td><td><?=((int)$ward['is_active']===1?'Active':'Inactive')?></td></tr><?php endforeach; ?>
      </tbody></table></div></div></div></div>
  </div>
  <div class="card shadow-sm"><div class="card-header font-weight-bold">Bed Register</div><div class="card-body"><div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Ward / Bed</th><th>Label</th><th>Occupancy</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($beds as $bed): ?><tr><td><?=htmlspecialchars($bed['ward_name'])?> · Bed <?=(int)$bed['bed_number']?></td><td><?=htmlspecialchars($bed['label'] ?? '')?></td><td><?=((int)$bed['is_occupied']===1?'<span class="badge badge-danger">Occupied</span>':'<span class="badge badge-success">Free</span>')?></td><td><?=((int)$bed['is_active']===1?'Active':'Inactive')?></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>"><input type="hidden" name="action" value="toggle_bed"><input type="hidden" name="bed_id" value="<?=(int)$bed['id']?>"><button class="btn btn-sm btn-outline-secondary" <?=(!$canEdit || (int)$bed['is_occupied']===1?'disabled':'')?>><?=((int)$bed['is_active']===1?'Deactivate':'Activate')?></button></form></td></tr><?php endforeach; ?>
    </tbody></table></div></div></div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
