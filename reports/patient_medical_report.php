<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();
$canViewClinical = can_access_module($conn, 'clinical');
$canViewAdmin = can_access_module($conn, 'administration');
$canViewFinance = can_access_module($conn, 'finance');
if (!$canViewClinical && !$canViewAdmin) { http_response_code(403); exit('Forbidden: You do not have permission to view medical reports.'); }

$patient_id = max(0, (int)($_GET['id'] ?? 0));
if ($patient_id <= 0) { http_response_code(400); exit('Invalid patient ID.'); }

function report_table_exists(mysqli $conn, string $table): bool {
    $safe = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '{$safe}'");
    return $r && $r->num_rows > 0;
}

function report_column_exists(mysqli $conn, string $table, string $column): bool {
    if (!report_table_exists($conn, $table)) return false;
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($column);
    $r = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    return $r && $r->num_rows > 0;
}

function report_rows(mysqli $conn, string $sql, array $params = [], string $types = ''): array {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($params !== []) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function report_e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function report_date($value): string {
    if (!$value) return '—';
    $ts = strtotime((string)$value);
    return $ts ? date('d M Y H:i', $ts) : report_e($value);
}

$stmt = $conn->prepare("SELECT p.*, u.full_name AS doctor_name, u.specialization FROM patients p LEFT JOIN users u ON u.id=p.doctor_id WHERE p.id=? LIMIT 1");
if (!$stmt) { http_response_code(500); exit('Unable to load patient record.'); }
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$patient) { http_response_code(404); exit('Patient not found.'); }

$hasVisits = report_table_exists($conn, 'visits');
$hasVisitEncounters = report_column_exists($conn, 'encounters', 'visit_id');
$hasVisitServices = report_column_exists($conn, 'patient_services', 'visit_id');
$hasVisitPrescriptions = report_column_exists($conn, 'prescriptions', 'visit_id');
$hasVisitVitals = report_column_exists($conn, 'vitals', 'visit_id');
$hasSpo2 = report_column_exists($conn, 'vitals', 'spo2');

$activeVisit = null;
if ($hasVisits) {
    $s = $conn->prepare("SELECT id, visit_number, visit_type, clinic_category, visit_date, visit_time, status FROM visits WHERE patient_id=? AND status IN ('Open', 'In Progress') ORDER BY visit_date DESC, visit_time DESC LIMIT 1");
    if ($s) {
        $s->bind_param('i', $patient_id);
        $s->execute();
        $activeVisit = $s->get_result()->fetch_assoc();
        $s->close();
    }
}

$encounterParams = [$patient_id];
$encounterSql = "SELECT e.*, u.full_name AS doctor_name FROM encounters e LEFT JOIN users u ON u.id=e.doctor_id WHERE e.patient_id=?";
if ($activeVisit && $hasVisitEncounters) {
    $encounterSql .= " AND e.visit_id=?";
    $encounterParams[] = (int)$activeVisit['id'];
}
$encounterSql .= " ORDER BY e.created_at DESC, e.id DESC";
$encounters = report_rows($conn, $encounterSql, $encounterParams, str_repeat('i', count($encounterParams)));

$allVisitsSql = "SELECT visit_number, visit_date, visit_time, visit_type, clinic_category, status FROM visits WHERE patient_id=? ORDER BY visit_date DESC, visit_time DESC";
$allVisits = report_rows($conn, $allVisitsSql, [$patient_id], 'i');

$vitalSql = "SELECT * FROM vitals WHERE patient_id=?";
$vitalParams = [$patient_id];
if ($activeVisit && $hasVisitVitals) {
    $vitalSql .= " AND visit_id=?";
    $vitalParams[] = (int)$activeVisit['id'];
}
$vitalSql .= " ORDER BY id DESC";
$vitals = report_rows($conn, $vitalSql, $vitalParams, str_repeat('i', count($vitalParams)));

$serviceSql = "SELECT ps.*, sm.service_name, sm.category AS service_category FROM patient_services ps LEFT JOIN services_master sm ON sm.id=ps.service_id WHERE ps.patient_id=?";
$serviceParams = [$patient_id];
if ($activeVisit && $hasVisitServices) {
    $serviceSql .= " AND ps.visit_id=?";
    $serviceParams[] = (int)$activeVisit['id'];
}
$serviceSql .= " ORDER BY ps.created_at DESC, ps.id DESC";
$services = report_rows($conn, $serviceSql, $serviceParams, str_repeat('i', count($serviceParams)));

$prescriptionSql = "SELECT pr.*, ps.drug_name FROM prescriptions pr LEFT JOIN pharmacy_stock ps ON ps.id=pr.medicine_id WHERE pr.patient_id=?";
$prescriptionParams = [$patient_id];
if ($activeVisit && $hasVisitPrescriptions) {
    $prescriptionSql .= " AND pr.visit_id=?";
    $prescriptionParams[] = (int)$activeVisit['id'];
}
$prescriptionSql .= " ORDER BY pr.created_at DESC, pr.id DESC";
$prescriptions = report_rows($conn, $prescriptionSql, $prescriptionParams, str_repeat('i', count($prescriptionParams)));

$labRadiology = [];
foreach ($services as $svc) {
    $cat = strtolower((string)($svc['category'] ?? $svc['service_category'] ?? ''));
    if (in_array($cat, ['lab', 'radiology'], true)) {
        $labRadiology[] = $svc;
    }
}

$admissions = [];
if (report_table_exists($conn, 'admissions')) {
    $admissions = report_rows(
        $conn,
        "SELECT a.*, u.full_name AS doctor_name FROM admissions a LEFT JOIN users u ON u.id=a.attending_doctor WHERE a.patient_id=? ORDER BY a.admission_date DESC, a.id DESC",
        [$patient_id],
        'i'
    );
}

$appointments = [];
if (report_table_exists($conn, 'appointments')) {
    $appointments = report_rows(
        $conn,
        "SELECT a.*, u.full_name AS doctor_name FROM appointments a LEFT JOIN users u ON u.id=a.doctor_id WHERE a.patient_id=? ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC LIMIT 30",
        [$patient_id],
        'i'
    );
}

$maternity = null;
$maternityVisits = [];
$deliveries = [];
$maternityAdmission = null;
if (report_table_exists($conn, 'maternity')) {
    $maternityStmt = $conn->prepare("SELECT * FROM maternity WHERE patient_id=? ORDER BY id DESC LIMIT 1");
    if ($maternityStmt) {
        $maternityStmt->bind_param('i', $patient_id);
        $maternityStmt->execute();
        $maternity = $maternityStmt->get_result()->fetch_assoc();
        $maternityStmt->close();
    }
    if ($maternity) {
        $mid = (int)$maternity['id'];
        if (report_table_exists($conn, 'maternity_visits')) {
            $maternityVisits = report_rows($conn, "SELECT * FROM maternity_visits WHERE maternity_id=? ORDER BY created_at DESC", [$mid], 'i');
        }
        if (report_table_exists($conn, 'maternity_delivery')) {
            $deliveries = report_rows(
                $conn,
                "SELECT d.*, b.gender AS baby_gender, b.weight AS baby_weight, b.apgar, b.alive FROM maternity_delivery d LEFT JOIN maternity_baby b ON b.maternity_id=d.maternity_id AND b.created_at>=d.created_at WHERE d.maternity_id=? ORDER BY d.created_at DESC",
                [$mid],
                'i'
            );
        }
        if (report_table_exists($conn, 'maternity_admissions')) {
            $maRows = report_rows(
                $conn,
                "SELECT ma.*, a.admission_date, a.ward_name, a.bed_number, a.status AS clinical_status FROM maternity_admissions ma LEFT JOIN admissions a ON a.id=ma.admission_id WHERE ma.patient_id=? ORDER BY ma.id DESC LIMIT 1",
                [$patient_id],
                'i'
            );
            $maternityAdmission = $maRows[0] ?? null;
        }
    }
}

$billingItems = [];
$invoices = [];
$totalCharges = 0.0;
$totalPaid = 0.0;
$approvedRefunds = 0.0;
if (report_table_exists($conn, 'invoices') && report_table_exists($conn, 'invoice_items')) {
    $invoices = report_rows(
        $conn,
        "SELECT i.*, v.visit_number FROM invoices i LEFT JOIN visits v ON v.id=i.visit_id WHERE i.patient_id=? AND LOWER(COALESCE(i.status,'')) NOT IN ('cancelled','canceled','void') ORDER BY i.created_at DESC, i.id DESC",
        [$patient_id],
        'i'
    );
    $billingItems = report_rows(
        $conn,
        "SELECT ii.*, i.created_at AS invoice_date, i.status AS invoice_status, i.visit_id, v.visit_number FROM invoice_items ii INNER JOIN invoices i ON i.id=ii.invoice_id LEFT JOIN visits v ON v.id=i.visit_id WHERE i.patient_id=? AND LOWER(COALESCE(i.status,'')) NOT IN ('cancelled','canceled','void') ORDER BY i.created_at DESC, ii.id DESC",
        [$patient_id],
        'i'
    );
    foreach ($invoices as $inv) {
        $totalCharges += (float)($inv['total'] ?? 0);
    }
}
if (report_table_exists($conn, 'payments')) {
    $paid = report_rows(
        $conn,
        "SELECT COALESCE(SUM(p.amount),0) total_paid FROM payments p INNER JOIN invoices i ON i.id=p.invoice_id WHERE i.patient_id=?",
        [$patient_id],
        'i'
    );
    $totalPaid = (float)($paid[0]['total_paid'] ?? 0);
}
if (report_table_exists($conn, 'refunds')) {
    $ref = report_rows(
        $conn,
        "SELECT COALESCE(SUM(r.amount),0) total_refunded FROM refunds r INNER JOIN payments p ON p.id=r.payment_id INNER JOIN invoices i ON i.id=p.invoice_id WHERE i.patient_id=?",
        [$patient_id],
        'i'
    );
    $approvedRefunds = (float)($ref[0]['total_refunded'] ?? 0);
}
$netPaid = max($totalPaid - $approvedRefunds, 0);
$balance = max($totalCharges - $netPaid, 0);

$historySections = [];
foreach ($encounters as $e) {
    $historySections[] = [
        'date' => $e['created_at'] ?? null,
        'title' => 'Clinical Encounter',
        'visit' => $e['visit_id'] ?? '',
        'doctor' => $e['doctor_name'] ?? '',
        'items' => [
            'Presenting Complaint' => $e['presenting_complaint'] ?? '',
            'History of Present Illness' => $e['hpc'] ?? '',
            'Medical History' => $e['medical_history'] ?? '',
            'Surgical History' => $e['surgical_history'] ?? '',
            'Family History' => $e['family_history'] ?? '',
            'Drug History' => $e['drug_history'] ?? '',
            'Allergies' => $e['allergies'] ?? '',
            'Social History' => $e['social_history'] ?? '',
            'Review of Systems' => $e['review_systems'] ?? '',
            'Physical Examination' => $e['physical_exam'] ?? '',
            'Diagnosis' => $e['diagnosis'] ?? '',
            'Differential Diagnosis' => $e['differential_diagnosis'] ?? '',
            'Investigations' => $e['investigations'] ?? '',
            'Management Plan' => $e['management_plan'] ?? '',
            'Prescription Instructions' => $e['prescription_instructions'] ?? '',
            'Doctor Notes' => $e['doctor_notes'] ?? '',
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Medical Report - <?= report_e($patient['patient_number']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f6; color: #25324a; font-family: "Segoe UI", Arial, sans-serif; font-size: 11px; }
        .report { width: 210mm; margin: 18px auto; background: #fff; padding: 14mm; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08); }
        .brand { display: flex; gap: 16px; align-items: center; margin-bottom: 18px; }
        .brand img { width: 56px; height: 56px; object-fit: contain; }
        .brand h1 { margin: 0; font-size: 18px; }
        .brand p { margin: 2px 0 0; color: #52607a; }
        .report-title { font-size: 24px; font-weight: 700; color: #0b3d91; margin: 10px 0 20px; }
        .identity, .metrics, .grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .metric, .field { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 9px 10px; }
        .label, .section-label { font-size: 10px; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; }
        .value { margin-top: 5px; font-weight: 600; color: #0f172a; }
        .value.primary { font-size: 14px; }
        .section { margin-top: 18px; }
        .section-head { background: #0b3d91; color: #fff; padding: 10px 12px; font-weight: 700; border-radius: 8px 8px 0 0; }
        .section-body { background: #fff; border: 1px solid #e2e8f0; border-top: none; padding: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .table th, .table td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; vertical-align: top; }
        .table th { background: #f8fafc; }
        .muted { color: #64748b; }
        .balance .value { color: #b42318; }
        .signature { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 22px; }
        .signbox { border: 1px solid #dbe3ee; border-radius: 8px; min-height: 80px; padding: 10px; background: #fafcff; }
        .footer { margin-top: 16px; font-size: 10px; color: #64748b; }
        .no-print { text-align: center; margin-top: 14px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="report">
    <div class="brand">
        <img src="../assets/img/logo.png" alt="Logo">
        <div>
            <h1>EMAQURE MEDICAL CENTRE</h1>
            <p>Biashara Street, Mlolongo</p>
            <p>Tel: +254793069565</p>
        </div>
    </div>

    <div class="report-title">Comprehensive Patient Medical Report</div>

    <div class="identity">
        <div>
            <div class="label">Patient Name</div>
            <div class="value primary"><?= report_e($patient['full_name']) ?></div>
            <div class="label" style="margin-top: 6px">Patient Number</div>
            <div class="value"><?= report_e($patient['patient_number'] ?? '—') ?></div>
        </div>
        <div>
            <div class="label">Age / Gender</div>
            <div class="value"><?= report_e($patient['age'] ?? '—') ?> years / <?= report_e($patient['gender'] ?? '—') ?></div>
            <div class="label" style="margin-top: 6px">Phone / Address</div>
            <div class="value"><?= report_e($patient['phone'] ?? '—') ?></div>
            <div class="value muted"><?= report_e($patient['address'] ?? '—') ?></div>
        </div>
    </div>

    <div class="metrics" style="margin-top: 18px;">
        <div class="metric">
            <div class="label">Current Visit</div>
            <div class="value"><?= report_e($activeVisit['visit_number'] ?? 'None') ?></div>
        </div>
        <div class="metric">
            <div class="label">Visits Recorded</div>
            <div class="value"><?= count($allVisits) ?></div>
        </div>
        <div class="metric">
            <div class="label">Current Admission</div>
            <div class="value"><?= ($admissions && (($admissions[0]['status'] ?? '') === 'Admitted')) ? 'Admitted' : 'Outpatient' ?></div>
        </div>
        <div class="metric balance">
            <div class="label">Balance Due</div>
            <div class="value">KES <?= number_format($balance, 2) ?></div>
        </div>
    </div>

    <div class="section">
        <div class="section-head">Patient & Care Information</div>
        <div class="section-body grid2">
            <div class="field">
                <span class="section-label">Assigned Doctor</span>
                <div class="text">Dr. <?= report_e($patient['doctor_name'] ?? 'Not Assigned') ?><?= !empty($patient['specialization']) ? ' — ' . report_e($patient['specialization']) : '' ?></div>
            </div>
            <div class="field">
                <span class="section-label">Next of Kin</span>
                <div class="text"><?= report_e($patient['next_of_kin_name'] ?? '—') ?><?= !empty($patient['next_of_kin_phone']) ? ' — ' . report_e($patient['next_of_kin_phone']) : '' ?></div>
            </div>
        </div>
    </div>

    <?php if ($activeVisit): ?>
        <div class="section">
            <div class="section-head">Current Encounter</div>
            <div class="section-body grid2">
                <div class="field"><span class="section-label">Visit</span><div class="text"><?= report_e($activeVisit['visit_number'] ?? '—') ?></div></div>
                <div class="field"><span class="section-label">Type</span><div class="text"><?= report_e($activeVisit['visit_type'] ?? '—') ?></div></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="section">
        <div class="section-head">Latest / Recorded Vitals</div>
        <div class="section-body">
            <?php if ($vitals): ?>
                <table class="table">
                    <thead><tr><th>Date</th><th>Temperature</th><th>BP</th><th>Pulse</th><th>Weight</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php foreach ($vitals as $v): ?>
                            <tr>
                                <td><?= report_date($v['created_at'] ?? '') ?></td>
                                <td><?= report_e($v['temperature'] ?? '—') ?></td>
                                <td><?= report_e($v['blood_pressure'] ?? '—') ?></td>
                                <td><?= report_e($v['heart_rate'] ?? '—') ?></td>
                                <td><?= report_e($v['weight'] ?? '—') ?></td>
                                <td><?= report_e($v['notes'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="muted">No vitals recorded.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <div class="section-head">Clinical History, Assessment & Management</div>
        <div class="section-body">
            <?php if ($historySections): foreach ($historySections as $h): ?>
                <div style="border:1px solid #edf2f7;border-radius:8px;padding:10px;margin-bottom:10px;">
                    <strong><?= report_date($h['date']) ?></strong>
                    <?php foreach ($h['items'] as $label => $value): ?>
                        <?php if (trim((string)$value) !== ''): ?>
                            <div style="margin-top:6px;"><strong><?= report_e($label) ?>:</strong> <?= nl2br(report_e($value)) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; else: ?>
                <div class="muted">No clinical encounters recorded.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <div class="section-head">Services, Investigations & Results</div>
        <div class="section-body">
            <?php if ($services): ?>
                <table class="table">
                    <thead><tr><th>Date</th><th>Service</th><th>Category</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td><?= report_date($s['created_at'] ?? '') ?></td>
                                <td><?= report_e($s['service_name'] ?? $s['service_id'] ?? '—') ?></td>
                                <td><?= report_e($s['category'] ?? $s['service_category'] ?? '—') ?></td>
                                <td><?= report_e($s['status'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="muted">No services recorded.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <div class="section-head">Prescriptions & Medicines</div>
        <div class="section-body">
            <?php if ($prescriptions): ?>
                <table class="table">
                    <thead><tr><th>Date</th><th>Medicine</th><th>Frequency</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php foreach ($prescriptions as $p): ?>
                            <tr>
                                <td><?= report_date($p['created_at'] ?? '') ?></td>
                                <td><?= report_e($p['drug_name'] ?? '—') ?></td>
                                <td><?= report_e($p['frequency'] ?? '—') ?></td>
                                <td><?= report_e($p['notes'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="muted">No prescriptions recorded.</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($maternity): ?>
        <div class="section">
            <div class="section-head">Maternity / ANC / PNC</div>
            <div class="section-body grid2">
                <?php foreach ($maternity as $k => $v): if (!in_array($k, ['id', 'patient_id'], true) && $v !== '' && $v !== null): ?>
                    <div class="field"><span class="section-label"><?= report_e(str_replace('_', ' ', ucwords($k))) ?></span><div class="text"><?= report_e($v) ?></div></div>
                <?php endif; endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="section">
        <div class="section-head">Appointments & Visit History</div>
        <div class="section-body">
            <?php if ($appointments): ?>
                <table class="table">
                    <thead><tr><th>Date</th><th>Time</th><th>Type</th><th>Doctor</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($appointments as $a): ?>
                            <tr>
                                <td><?= report_date($a['appointment_date'] ?? '') ?></td>
                                <td><?= report_e($a['appointment_time'] ?? '—') ?></td>
                                <td><?= report_e($a['appointment_type'] ?? '—') ?></td>
                                <td><?= report_e($a['doctor_name'] ?? '—') ?></td>
                                <td><?= report_e($a['status'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="muted">No appointments recorded.</div>
            <?php endif; ?>

            <?php if ($allVisits): ?>
                <h4 style="margin:12px 0 6px;color:#075b9d">Visit History</h4>
                <table class="table">
                    <thead><tr><th>Visit No.</th><th>Date</th><th>Type</th><th>Department</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($allVisits as $v): ?>
                            <tr>
                                <td><?= report_e($v['visit_number'] ?? '—') ?></td>
                                <td><?= report_date($v['visit_date'] ?? '') ?></td>
                                <td><?= report_e($v['visit_type'] ?? '—') ?></td>
                                <td><?= report_e($v['clinic_category'] ?? '—') ?></td>
                                <td><?= report_e($v['status'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canViewFinance || $canViewAdmin): ?>
    <div class="section">
        <div class="section-head">Billing, Services & Payments</div>
        <div class="section-body">
            <div class="metrics" style="margin-bottom:10px;">
                <div class="metric"><div class="label">Charges</div><div class="value">KES <?= number_format($totalCharges, 2) ?></div></div>
                <div class="metric"><div class="label">Payments</div><div class="value">KES <?= number_format($netPaid, 2) ?></div></div>
                <div class="metric"><div class="label">Refunds</div><div class="value">KES <?= number_format($approvedRefunds, 2) ?></div></div>
                <div class="metric"><div class="label">Balance</div><div class="value">KES <?= number_format($balance, 2) ?></div></div>
            </div>

            <?php if ($billingItems): ?>
                <table class="table">
                    <thead><tr><th>Date</th><th>Visit</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($billingItems as $bi): ?>
                            <tr>
                                <td><?= report_date($bi['invoice_date'] ?? '') ?></td>
                                <td><?= report_e($bi['visit_number'] ?? '—') ?></td>
                                <td><?= report_e($bi['description'] ?? '—') ?></td>
                                <td><?= report_e($bi['quantity'] ?? '—') ?></td>
                                <td><?= number_format((float)($bi['unit_price'] ?? 0), 2) ?></td>
                                <td><?= number_format((float)($bi['total'] ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>

    <div class="signature">
        <div class="signbox">Authorized Medical Officer<br><small>Signature / Date</small></div>
        <div class="signbox">Official Hospital Stamp<br><small>EMAQURE MEDICAL CENTRE</small></div>
    </div>

    <div class="footer">Comprehensive medical report generated <?= date('d M Y H:i') ?>. This report consolidates information available on the Patient Dashboard.</div>
</div>
<div class="no-print"><button onclick="window.print()">Print Medical Report (A4)</button></div>
</body>
</html>
