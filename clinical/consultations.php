<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_module_access($conn, 'clinical', 'edit');
require_role(['admin','doctor','nurse']);

// Detect whether vitals has a status column so the queue query remains compatible.
$vitalsHasStatus = false;
$vitalsColumns = $conn->query("SHOW COLUMNS FROM vitals");
if ($vitalsColumns) {
    while ($col = $vitalsColumns->fetch_assoc()) {
        if (($col['Field'] ?? '') === 'status') {
            $vitalsHasStatus = true;
            break;
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// Build the clinical queue from today's visits. Triage is required for standard
// outpatient visits, but Walk-in visits may proceed directly without vitals.
$hasVisits = false;
$visitCheck = $conn->query("SHOW TABLES LIKE 'visits'");
if ($visitCheck && $visitCheck->num_rows > 0) {
    $hasVisits = true;
}

if ($hasVisits) {
    $vitalTempColumn = 'NULL';
    $tempCheck = $conn->query("SHOW COLUMNS FROM vitals");
    if ($tempCheck) {
        while ($col = $tempCheck->fetch_assoc()) {
            if (($col['Field'] ?? '') === 'temp') { $vitalTempColumn = 'vt.temp'; break; }
            if (($col['Field'] ?? '') === 'temperature') { $vitalTempColumn = 'vt.temperature'; }
        }
    }
    $sql = "SELECT v.id AS visit_id, v.visit_number, v.visit_type, v.clinic_category,
                   v.status AS visit_status, v.visit_time,
                   p.id AS patient_id, p.full_name, p.gender, p.age, p.patient_number,
                   vt.bp, {$vitalTempColumn} AS temp, vt.weight, vt.pulse, vt.complaints
            FROM visits v
            INNER JOIN patients p ON p.id = v.patient_id
            LEFT JOIN vitals vt ON vt.id = (
                SELECT v2.id FROM vitals v2
                WHERE v2.patient_id = v.patient_id
                  AND DATE(v2.created_at) = CURDATE()
                  AND (v2.visit_id = v.id OR v2.visit_id IS NULL)
                ORDER BY CASE WHEN v2.visit_id = v.id THEN 0 ELSE 1 END, v2.id DESC LIMIT 1
            )
            WHERE v.visit_date = CURDATE()
              AND v.status = 'Open'
              AND (v.visit_type = 'Walk-in' OR EXISTS (
                  SELECT 1 FROM vitals tv
                  WHERE tv.visit_id = v.id
              ))
            ORDER BY v.id ASC";
} else {
    $sql = "SELECT v.*, p.full_name, p.gender, p.age, p.patient_number,
                   p.id AS patient_id
            FROM vitals v
            JOIN patients p ON v.patient_id = p.id
            WHERE DATE(v.created_at) = CURDATE()";
    if ($vitalsHasStatus) {
        $sql .= " AND v.status = 'pending'";
    }
    $sql .= " ORDER BY v.created_at ASC";
}
$res = $conn->query($sql);
?>

<div class="main-content">
    <div class="container-fluid pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="font-weight-bold text-gray-800 mb-1">Doctor's Consultation Queue</h4><div class="text-muted small">Patients ready for clinical assessment — triage is shown when it was performed; direct walk-ins may proceed without triage</div></div><div>
<a href="triage.php" class="btn btn-outline-warning mr-2"><i class="fas fa-heartbeat"></i> Triage</a>
<a href="index.php" class="btn btn-outline-primary"><i class="fas fa-th-large"></i> Clinical Dashboard</a>
</div></div>
        <?php if (isset($_GET['triage']) && $_GET['triage'] === 'success'): ?><div class="alert alert-success">Visit routed to the doctor queue. Triage was recorded for this visit.</div><?php endif; ?>
        <div class="row">
            <?php if($res && $res->num_rows > 0): while($row = $res->fetch_assoc()): ?>
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card shadow-sm border-left-primary">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= $row['full_name'] ?> (<?= $row['gender'] ?>, <?= $row['age'] ?> yrs)</div>
                                <div class="small text-muted mb-2">Visit: <?= htmlspecialchars($row['visit_number']) ?> · <?= htmlspecialchars($row['clinic_category'] ?? 'General') ?> · BP: <?= htmlspecialchars($row['bp'] ?? '') ?> | Temp: <?= htmlspecialchars($row['temp'] ?? '') ?>°C</div>
                                <div class="mb-0 text-gray-800 small"><strong>Complaints:</strong> <?= substr($row['complaints'], 0, 60) ?>...</div>
                            </div>
                            <div class="col-auto">
                                <a href="../clinical/care.php?patient_id=<?= (int)$row['patient_id'] ?>&visit_id=<?= (int)$row['visit_id'] ?>" class="btn btn-sm btn-primary px-3">Examine</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-user-md fa-3x text-muted"></i>
                    <p class="mt-3 text-muted">No patients currently in queue.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>