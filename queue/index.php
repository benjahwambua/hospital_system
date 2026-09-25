<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin','receptionist','nurse','doctor']);

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'serve') {
    $id = (int)$_GET['id'];
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE appointments SET status='In Progress' WHERE id=? AND status IN ('Pending','pending')");
        if ($stmt) { $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close(); }
    }
    header('Location: /hospital_system/queue/index.php');
    exit;
}

// The legacy queue table is not the hospital workflow source of truth.
// Reception creates appointments/visits; Clinical Care consumes the same visit.
$res = $conn->query("SELECT a.id, a.patient_id, a.appointment_date, a.appointment_time, a.reason,
                            a.status, p.full_name, p.patient_number
                     FROM appointments a
                     INNER JOIN patients p ON p.id = a.patient_id
                     WHERE a.appointment_date = CURDATE()
                       AND a.status IN ('Pending','pending')
                     ORDER BY a.appointment_time ASC, a.id ASC");
$queryError = $res === false ? $conn->error : '';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="container-fluid pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div><h4 class="font-weight-bold text-gray-800 mb-1">Patient Queue</h4>
      <div class="text-muted small">Reception handoff queue — patients proceed to Triage & Vitals before consultation.</div></div>
      <a href="/hospital_system/patients/reception_register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register Patient</a>
    </div>
    <?php if ($queryError): ?>
      <div class="alert alert-danger">Unable to load the patient queue. Please verify the appointments/visits database migration.</div>
    <?php elseif ($res && $res->num_rows): ?>
      <div class="card shadow-sm"><div class="card-body table-responsive">
        <table class="table table-bordered">
          <thead><tr><th>Patient</th><th>Patient No.</th><th>Time</th><th>Service</th><th>Status</th><th>Next Step</th></tr></thead>
          <tbody>
          <?php while ($row = $res->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['full_name']) ?></td>
              <td><?= htmlspecialchars($row['patient_number']) ?></td>
              <td><?= htmlspecialchars($row['appointment_time']) ?></td>
              <td><?= htmlspecialchars($row['reason']) ?></td>
              <td><span class="badge badge-warning">Waiting for Triage</span></td>
              <td><a class="btn btn-sm btn-outline-primary" href="/hospital_system/clinical/triage.php?patient_id=<?= (int)$row['patient_id'] ?>">Open Triage</a></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div></div>
    <?php else: ?>
      <div class="card shadow-sm"><div class="card-body text-center py-5 text-muted">No patients are currently waiting for triage.</div></div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>