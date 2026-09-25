<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/billing.php';

require_login();
require_role(['admin','receptionist','nurse','doctor']);

/*
 * The Visit is the single source of truth.
 * Triage is NOT mandatory for every visit:
 * - Walk-in treatment visits may proceed directly to clinical/service care.
 * - Standard outpatient visits wait for Triage & Vitals before the doctor queue.
 */
$sql = "SELECT v.id AS visit_id, v.visit_number, v.visit_time, v.visit_type,
               v.clinic_category, v.status AS visit_status,
               p.id AS patient_id, p.full_name, p.patient_number,
               p.gender, p.age,
               CASE
                   WHEN v.visit_type = 'Walk-in' THEN 'direct'
                   WHEN EXISTS (SELECT 1 FROM vitals vt WHERE vt.visit_id = v.id) THEN 'doctor'
                   ELSE 'triage'
               END AS workflow_stage
        FROM visits v
        INNER JOIN patients p ON p.id = v.patient_id
        WHERE v.visit_date = CURDATE()
          AND v.status IN ('Open','In Progress')
        ORDER BY v.visit_time ASC, v.id ASC";

$res = $conn->query($sql);
$queryError = $res === false ? $conn->error : '';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="container-fluid pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="font-weight-bold text-gray-800 mb-1">Patient Queue</h4>
        <div class="text-muted small">Reception handoff — patients are routed according to the visit pathway.</div>
      </div>
      <a href="/hospital_system/patients/reception_register.php" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Register Patient
      </a>
    </div>

    <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success">
        Patient registered. The visit has been created once and routed to the appropriate next step.
      </div>
    <?php endif; ?>

    <?php if ($queryError): ?>
      <div class="alert alert-danger">
        Unable to load the patient queue. Please verify that the Visits and Vitals migrations are installed.
      </div>
    <?php elseif ($res && $res->num_rows): ?>
      <div class="card shadow-sm">
        <div class="card-body table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Patient</th><th>Patient No.</th><th>Visit</th><th>Time</th>
                <th>Department</th><th>Status</th><th>Next Step</th>
              </tr>
            </thead>
            <tbody>
            <?php while ($row = $res->fetch_assoc()): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                  <small class="text-muted"><?= htmlspecialchars($row['gender'] ?? '') ?> · <?= htmlspecialchars($row['age'] ?? '') ?> yrs</small>
                </td>
                <td><?= htmlspecialchars($row['patient_number']) ?></td>
                <td><?= htmlspecialchars($row['visit_number']) ?></td>
                <td><?= htmlspecialchars($row['visit_time']) ?></td>
                <td><?= htmlspecialchars($row['clinic_category'] ?? 'General') ?></td>
                <td>
                  <?php if ($row['workflow_stage'] === 'triage'): ?>
                    <span class="badge badge-warning">Waiting for Triage</span>
                  <?php elseif ($row['workflow_stage'] === 'doctor'): ?>
                    <span class="badge badge-primary">Waiting for Doctor</span>
                  <?php else: ?>
                    <span class="badge badge-info">Direct Walk-in</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($row['workflow_stage'] === 'triage'): ?>
                    <a class="btn btn-sm btn-outline-primary"
                       href="/hospital_system/clinical/triage.php?patient_id=<?= (int)$row['patient_id'] ?>&visit_id=<?= (int)$row['visit_id'] ?>">
                      <i class="fas fa-heartbeat"></i> Open Triage
                    </a>
                  <?php else: ?>
                    <a class="btn btn-sm btn-primary"
                       href="/hospital_system/clinical/care.php?patient_id=<?= (int)$row['patient_id'] ?>&visit_id=<?= (int)$row['visit_id'] ?>">
                      <i class="fas fa-user-md"></i> Open Clinical Care
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">No active patients in the queue.</div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
