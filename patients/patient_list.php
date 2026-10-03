<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
$canPatientEdit = can_module_action($conn, 'front_desk', 'edit');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// Handle search with prepared statements to prevent SQL injection from the search box.
$search = '';
$where = '';
$params = [];
$types = '';

if (isset($_GET['search']) && trim((string)$_GET['search']) !== '') {
    $search = trim((string)$_GET['search']);
    $where = " WHERE (p.full_name LIKE ? OR p.patient_number LIKE ? OR p.phone LIKE ? OR p.next_of_kin_name LIKE ?)";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
    $types = 'ssss';
}

$baseSql = "
    SELECT p.id, p.patient_number, p.full_name, p.gender, p.age, p.phone, p.next_of_kin_name, p.next_of_kin_phone, u.full_name AS doctor_name, p.appointment_date, p.created_at
    FROM patients p
    LEFT JOIN users u ON p.doctor_id = u.id
    {$where}
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($baseSql);
$patients = false;

if ($stmt) {
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $patients = $stmt->get_result();
} else {
    $patients = $conn->query($baseSql);
}
?>

<style>
.patient-list-page{background:#f5f7fb;min-height:calc(100vh - 72px);padding:28px 24px 40px}.patient-list-shell{width:100%;max-width:1550px;margin:0 auto}.patient-list-hero{background:linear-gradient(135deg,#0d3b72,#1976d2);color:#fff;padding:28px 30px;border-radius:18px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 8px 25px rgba(13,59,114,0.18);margin-bottom:18px}.patient-list-kicker{font-size:11px;letter-spacing:1px;text-transform:uppercase;opacity:.8}.patient-list-hero h1{margin:8px 0 6px;font-size:2.1rem}.patient-list-hero p{margin:0;font-size:1rem;opacity:.9}.patient-list-card{background:#fff;border:1px solid #e8edf5;border-radius:16px;box-shadow:0 8px 24px rgba(17,34,54,0.06);overflow:hidden}.patient-list-toolbar{padding:18px 20px;border-bottom:1px solid #edf1f7}.patient-search{display:flex;justify-content:space-between;gap:14px;align-items:center;flex-wrap:wrap}.patient-search-field{position:relative;flex:1;min-width:260px}.patient-search-field i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#8996a3}.patient-search-field input{width:100%;padding:12px 16px 12px 40px;border:1px solid #dfe7f1;border-radius:10px;background:#f8fafc;font-size:14px}.patient-search-button{padding:12px 18px;border:0;border-radius:10px;background:#0d6efd;color:#fff;font-weight:700}.patient-table-wrap{overflow-x:auto}.patient-table{width:100%;border-collapse:collapse;min-width:1100px}.patient-table th{background:#eef4ff;color:#1e3a5f;padding:14px 16px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.6px}.patient-table td{padding:14px 16px;border-bottom:1px solid #edf1f7;vertical-align:middle}.patient-name{font-weight:700;color:#1b2a41}.patient-number{font-size:12px;color:#667085;margin-top:4px}.status-badge{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:700}.status-badge.upcoming{background:#e8f5e9;color:#1e7d32}.status-badge.past{background:#f3f4f6;color:#475569}.patient-actions a{display:inline-flex;align-items:center;justify-content:center;padding:8px 12px;border-radius:8px;background:#edf5ff;color:#0a3d87;text-decoration:none;font-weight:600}.empty-state{padding:40px 20px;text-align:center;color:#667085}.empty-icon{font-size:2rem;color:#c8d4e6;margin-bottom:12px}.patient-list-footer{padding:14px 20px;background:#f8fafc;color:#586879;font-size:13px;border-top:1px solid #edf1f7}.appt-chip{display:inline-flex;padding:5px 9px;border-radius:999px;background:#fff3cd;color:#8a5d00;font-size:11px;font-weight:700}.@media (max-width:768px){.patient-list-hero{flex-direction:column;align-items:flex-start;gap:12px}.patient-list-hero h1{font-size:1.6rem}.patient-search{flex-direction:column;align-items:stretch}.patient-search-button{width:100%}}</style>

<div class="patient-list-page"><div class="patient-list-shell">
<div class="patient-list-hero"><div><div class="patient-list-kicker">Clinical Care</div><h1>Patient List</h1><p>Find patients and open their clinical records.</p></div><a href="/hospital_system/patients/reception_register.php" class="btn btn-light btn-lg">Register New Patient</a></div>
<div class="patient-list-card"><div class="patient-list-toolbar"><form method="get" class="patient-search"><div class="patient-search-field"><i class="fas fa-search"></i><input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by patient name, number, phone or NOK"></div><button class="patient-search-button" type="submit">Search</button></form></div>
<?php if ($patients && $patients->num_rows > 0): ?><div class="patient-table-wrap"><table class="patient-table"><thead><tr><th>#</th><th>Patient</th><th>Gender</th><th>Age</th><th>Phone</th><th>Next of Kin</th><th>Doctor</th><th>Upcoming</th><th>Actions</th></tr></thead><tbody>
<?php $count=1; while($p=$patients->fetch_assoc()): $appt_display='N/A'; if(!empty($p['appointment_date'])){$appt_time=strtotime((string)$p['appointment_date']);if($appt_time>time()){$appt_display='<span class="status-badge upcoming">'.htmlspecialchars(date('d M Y', $appt_time)).'</span>';}else{$appt_display='<span class="status-badge past">'.htmlspecialchars(date('d M Y', $appt_time)).'</span>';}} ?><tr><td><?= $count++; ?></td><td><div class="patient-name"><?= htmlspecialchars($p['full_name']); ?></div><div class="patient-number"><?= htmlspecialchars($p['patient_number']); ?></div></td><td><?= htmlspecialchars((string)($p['gender'] ?? '—')) ?></td><td><?= (int)($p['age'] ?? 0) ?></td><td><?= htmlspecialchars((string)($p['phone'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($p['next_of_kin_name'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($p['doctor_name'] ?? 'Unassigned')) ?></td><td><?= $appt_display ?></td><td class="patient-actions"><a href="/hospital_system/patients/patient_dashboard.php?id=<?= (int)$p['id'] ?>">Open</a></td></tr><?php endwhile; ?></tbody></table></div><div class="patient-list-footer"><i class="fas fa-users mr-1"></i> <?= $patients->num_rows; ?> patient(s) displayed</div>
<?php else: ?><div class="empty-state"><div class="empty-icon"><i class="fas fa-user-injured"></i></div><strong><?= $search ? 'No patients found' : 'No patients registered yet'; ?></strong><p><?= $search ? 'Try a different search term or clear the filter.' : 'Register a patient to begin tracking clinical care and billing.' ?></p></div><?php endif; ?>
</div></div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
