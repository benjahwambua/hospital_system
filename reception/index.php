<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'front_desk', 'view');

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
        v.status, v.visit_time, v.doctor_id, p.patient_number, p.full_name,
        u.full_name AS doctor_name
        FROM visits v
        INNER JOIN patients p ON p.id = v.patient_id
        LEFT JOIN users u ON u.id = v.doctor_id
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
.reception-wrap{padding:24px;background:#f4f7fb;min-height:calc(100vh - 60px)}.reception-shell{max-width:1500px;margin:auto}.reception-head{background:linear-gradient(135deg,#075985,#0284c7);color:#fff;border-radius:20px;padding:30px;display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:20px;box-shadow:0 14px 34px rgba(2,132,199,.2)}.reception-head h1{margin:5px 0;font-size:29px}.reception-head p{margin:0;color:rgba(255,255,255,.82);font-size:14px}.reception-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.8px;font-weight:800;color:#c9efff}.actions{display:grid;grid-template-columns:repeat(3,minmax(190px,1fr));gap:12px;flex:1;max-width:760px}.quick-action{display:flex;align-items:center;gap:13px;padding:14px 15px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.10);border-radius:14px;color:#fff;text-decoration:none;transition:.2s}.quick-action:hover{background:rgba(255,255,255,.17);transform:translateY(-2px);color:#fff}.quick-icon{width:42px;height:42px;flex:0 0 42px;border-radius:12px;background:#fff;color:#075b9d;display:flex;align-items:center;justify-content:center;font-size:17px}.quick-copy{min-width:0}.quick-copy strong{display:block;font-size:13px;line-height:1.2}.quick-copy span{display:block;margin-top:4px;font-size:10px;color:rgba(255,255,255,.72);line-height:1.3}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}.card{background:#fff;border:1px solid #e7ebf2;border-radius:15px;padding:20px;box-shadow:0 4px 15px rgba(31,45,61,.05)}.stat-label{color:#7b8798;text-transform:uppercase;font-size:10px;font-weight:800}.stat-value{font-size:29px;font-weight:800;margin-top:7px;color:#25324a}.section-title{font-size:15px;font-weight:800;color:#25324a;margin:0}.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse}.table th,.table td{padding:12px;border-bottom:1px solid #edf0f5;text-align:left;font-size:13px}.table th{background:#fafbfc;color:#667085;font-size:11px;text-transform:uppercase}.badge{display:inline-block;padding:5px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#eef4fa}.badge-open{background:#fff3cd;color:#856404}.badge-progress{background:#dbeafe;color:#1d4ed8}.badge-done{background:#d4edda;color:#155724}.doctor-cell{min-width:170px}.doctor-name{font-weight:800;color:#25324a}.doctor-role{display:block;margin-top:3px;font-size:10px;color:#98a2b3}@media(max-width:1050px){.reception-head{align-items:flex-start;flex-direction:column}.actions{max-width:none;width:100%}}@media(max-width:850px){.stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:600px){.reception-wrap{padding:18px 12px}.reception-head{flex-direction:column;align-items:flex-start}.stats{grid-template-columns:1fr}}
</style>
<div class="reception-wrap"><div class="reception-shell">
    <div class="reception-head">
        <div><div class="reception-kicker">Front Desk</div><h1>Reception Dashboard</h1><p>Patient registration, visits and front-desk operations.</p></div>
        <div class="actions">
            <a class="quick-action" href="/hospital_system/patients/reception_register.php">
                <span class="quick-icon"><i class="fas fa-user-plus"></i></span>
                <span class="quick-copy"><strong>New Patient</strong><span>Register and assign the visit</span></span>
            </a>
            <a class="quick-action" href="/hospital_system/patients/patient_list.php">
                <span class="quick-icon"><i class="fas fa-search"></i></span>
                <span class="quick-copy"><strong>Patient Search</strong><span>Find records by name or number</span></span>
            </a>
            <a class="quick-action" href="/hospital_system/patients/appointments.php">
                <span class="quick-icon"><i class="fas fa-calendar-check"></i></span>
                <span class="quick-copy"><strong>Appointments</strong><span>View today's scheduled patients</span></span>
            </a>
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
                <thead><tr><th>Time</th><th>Patient</th><th>Number</th><th>Type</th><th>Department</th><th>Attending Doctor</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!$recent): ?>
                    <tr><td colspan="7" style="text-align:center;color:#788;">No visits recorded today.</td></tr>
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
                        <td class="doctor-cell"><?php if (!empty($visit['doctor_name'])): ?><span class="doctor-name">Dr. <?= htmlspecialchars($visit['doctor_name']) ?></span><span class="doctor-role">Assigned to visit</span><?php else: ?><span style="color:#98a2b3;">Unassigned</span><span class="doctor-role">First available</span><?php endif; ?></td>
                        <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($visit['status']) ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div></div>\n<?php include __DIR__ . '/../includes/footer.php'; ?>
