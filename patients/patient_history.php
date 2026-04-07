<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patient_id = intval($_GET['id'] ?? 0);
if (!$patient_id) {
    header("Location: view_patients.php");
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

/* =======================
   PATIENT
======================= */
$stmt = $conn->prepare("SELECT * FROM patients WHERE id=?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    echo "<div class='card'>Patient not found</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

/* =======================
   ENCOUNTERS
======================= */
$stmt = $conn->prepare("
    SELECT e.*, u.full_name AS doctor_name
    FROM encounters e
    LEFT JOIN users u ON u.id = e.doctor_id
    WHERE e.patient_id=?
    ORDER BY e.created_at DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$encounters = $stmt->get_result();
$stmt->close();

/* =======================
   CONSULTATIONS
======================= */
$stmt = $conn->prepare("
    SELECT 
        c.*,
        e.created_at AS visit_date,
        u.full_name AS doctor_name
    FROM consultations c
    INNER JOIN encounters e ON e.id = c.encounter_id
    LEFT JOIN users u ON u.id = e.doctor_id
    WHERE e.patient_id=?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$consults = $stmt->get_result();
$stmt->close();

/* =======================
   VITALS
======================= */
$stmt = $conn->prepare("
    SELECT v.*, e.created_at AS visit_date
    FROM vitals v
    INNER JOIN encounters e ON e.id = v.encounter_id
    WHERE e.patient_id=?
    ORDER BY v.created_at DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$vitals = $stmt->get_result();
$stmt->close();

/* =======================
   LABS
======================= */
$stmt = $conn->prepare("
    SELECT 
        lt.test_name,
        lt.status,
        lr.result_text,
        lr.reported_at,
        u.full_name AS reported_by
    FROM lab_tests lt
    INNER JOIN encounters e ON e.id = lt.encounter_id
    LEFT JOIN lab_results lr ON lr.lab_test_id = lt.id
    LEFT JOIN users u ON u.id = lr.reported_by
    WHERE e.patient_id=?
    ORDER BY lt.requested_at DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$labs = $stmt->get_result();
$stmt->close();

/* =======================
   INVOICES
======================= */
$stmt = $conn->prepare("
    SELECT i.*
    FROM invoices i
    INNER JOIN encounters e ON e.id = i.encounter_id
    WHERE e.patient_id=?
    ORDER BY i.created_at DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$invoices = $stmt->get_result();
$stmt->close();

/* =======================
   PHARMACY
======================= */
$stmt = $conn->prepare("
    SELECT 
        ps.id,
        m.name AS medication,
        ps.quantity,
        ps.total,
        ps.created_at
    FROM pharmacy_sales ps
    INNER JOIN encounters e ON e.id = ps.encounter_id
    LEFT JOIN medications m ON m.id = ps.med_id
    WHERE e.patient_id=?
    ORDER BY ps.created_at DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$pharmacy = $stmt->get_result();
$stmt->close();
?>

<!-- ======================= UI ======================= -->
<div class="card">
    <h2><?= htmlspecialchars($patient['full_name']) ?></h2>
    <div>Hospital No: <?= htmlspecialchars($patient['hospital_number'] ?? '—') ?></div>
    <div>Phone: <?= htmlspecialchars($patient['phone']) ?></div>
</div>

<div style="display:flex;gap:8px;margin:12px 0;">
    <a href="#consults" class="btn">Consultations</a>
    <a href="#vitals" class="btn">Vitals</a>
    <a href="#labs" class="btn">Labs</a>
    <a href="#invoices" class="btn">Invoices</a>
    <a href="#pharmacy" class="btn">Pharmacy</a>
</div>

<div id="consults" class="card">
<h3>Consultations</h3>
<?php if ($consults->num_rows === 0): ?>
    <div class="muted">No consultations</div>
<?php else: while ($c = $consults->fetch_assoc()): ?>
    <div class="item">
        <strong><?= htmlspecialchars($c['diagnosis'] ?: $c['complaints']) ?></strong>
        <div><?= nl2br(htmlspecialchars($c['notes'])) ?></div>
        <small>Dr. <?= htmlspecialchars($c['doctor_name']) ?> | <?= $c['created_at'] ?></small>
    </div>
<?php endwhile; endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
