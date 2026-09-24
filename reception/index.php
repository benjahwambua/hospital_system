<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$role = strtolower((string)($_SESSION['role'] ?? ''));
$isSuperUser = !empty($_SESSION['is_super']) && $_SESSION['is_super'] === 1;
if (!$isSuperUser && !in_array($role, ['admin', 'receptionist', 'cashier'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

$today = date('Y-m-d');
$stats = [
    'visits' => 0,
    'waiting' => 0,
    'registered' => 0,
    'walkins' => 0
];

$hasVisits = false;
$check = $conn->query("SHOW TABLES LIKE 'visits'");
if ($check && $check->num_rows > 0) {
    $hasVisits = true;
    $stmt = $conn->prepare("SELECT COUNT(*) total,
        SUM(status IN ('Open','In Progress')) waiting,
        SUM(visit_type = 'Walk-in') walkins,
        SUM(visit_type <> 'Walk-in') registered
        FROM visits WHERE visit_date = ?");
    if ($stmt) {
        $stmt->bind_param('s', $today);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stats['visits'] = (int)($row['total'] ?? 0);
        $stats['waiting'] = (int)($row['waiting'] ?? 0);
        $stats['walkins'] = (int)($row['walkins'] ?? 0);
        $stats['registered'] = (int)($row['registered'] ?? 0);
        $stmt->close();
    }
}

$recent = [];
if ($hasVisits) {
    $stmt = $conn->prepare("SELECT v.id, v.visit_number, v.visit_type, v.clinic_category,
        v.status, v.visit_time, p.patient_number, p.full_name
        FROM visits v
        INNER JOIN patients p ON p.id = v.patient_id
        WHERE v.visit_date = ?
        ORDER BY v.id DESC LIMIT 15");
    if ($stmt) {
        $stmt->bind_param('s', $today);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $recent[] = $row;
        $stmt->close();
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.reception-wrap{padding:28px;max-width:1250px;margin:auto}
.reception-head{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:25px;flex-wrap:wrap}
.reception-head h1{margin:0;color:#123;font-size:28px}.reception-head p{margin:6px 0 0;color:#687}
.actions{display:flex;gap:10px;flex-wrap:wrap}.btn{display:inline-block;padding:11px 16px;border-radius:8px;text-decoration:none;font-weight:600}
.btn-primary{background:#007bff;color:#fff}.btn-light{background:#eef4fa;color:#145}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:25px}
.card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 5px 20px rgba(0,0,0,.08)}
.stat-label{color:#687;font-size:13px}.stat-value{font-size:30px;font-weight:800;margin-top:7px;color:#123}
.section-title{font-size:19px;font-weight:700;margin:0 0 15px}.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse}.table th,.table td{padding:12px;border-bottom:1px solid #eee;text-align:left;font-size:14px}.table th{background:#f7f9fc;color:#456}
.badge{display:inline-block;padding:5px 9px;border-radius:12px;font-size:12px;font-weight:700;background:#eef4fa}.badge-open{background:#fff3cd;color:#856404}.badge-progress{background:#dbeafe;color:#1d4ed8}.badge-done{background:#d4edda;color:#155724}
@media(max-width:850px){.stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:520px){.reception-wrap{padding:15px}.stats{grid-template-columns:1fr}}
</style>
<div class="reception-wrap">
    <div class="reception-head">
        <div><h1><i class="fas fa-concierge-bell"></i> Reception</h1><p>Patient registration, visits, queue and front-desk control.</p></div>
        <div class="actions">
            <a class="btn btn-primary" href="/hospital_system/patients/reception_register.php"><i class="fas fa-user-plus"></i> New Patient</a>
            <a class="btn btn-light" href="/hospital_system/patients/patient_list.php"><i class="fas fa-search"></i> Patient Search</a>
            <a class="btn btn-light" href="/hospital_system/patients/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
        </div>
    </div>

    <?php if (!$hasVisits): ?>
        <div class="card" style="margin-bottom:20px;border-left:4px solid #f59e0b;">
            <strong>Visit module not active yet.</strong>
            <div style="margin-top:6px;color:#667;">Run <code>database/visits_migration.sql</code> once in <code>hms_db</code> to enable today's visit and queue statistics.</div>
        </div>
    <?php endif; ?>

    <div class="stats">
        <div class="card"><div class="stat-label">Today's Visits</div><div class="stat-value"><?= $stats['visits'] ?></div></div>
        <div class="card"><div class="stat-label">Waiting / In Progress</div><div class="stat-value"><?= $stats['waiting'] ?></div></div>
        <div class="card"><div class="stat-label">Registered Patients</div><div class="stat-value"><?= $stats['registered'] ?></div></div>
        <div class="card"><div class="stat-label">Walk-ins</div><div class="stat-value"><?= $stats['walkins'] ?></div></div>
    </div>

    <div class="card">
        <div class="section-title">Today's Patient Queue</div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Time</th><th>Patient</th><th>Number</th><th>Type</th><th>Department</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!$recent): ?>
                    <tr><td colspan="6" style="text-align:center;color:#788;">No visits recorded today.</td></tr>
                <?php else: foreach ($recent as $visit): ?>
                    <?php
                    $statusClass = $visit['status'] === 'Open' ? 'badge-open' : ($visit['status'] === 'In Progress' ? 'badge-progress' : ($visit['status'] === 'Completed' ? 'badge-done' : ''));
                    ?>
                    <tr>
                        <td><?= htmlspecialchars(substr((string)$visit['visit_time'],0,5)) ?></td>
                        <td><strong><?= htmlspecialchars($visit['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($visit['patient_number']) ?></td>
                        <td><?= htmlspecialchars($visit['visit_type']) ?></td>
                        <td><?= htmlspecialchars($visit['clinic_category']) ?></td>
                        <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($visit['status']) ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
