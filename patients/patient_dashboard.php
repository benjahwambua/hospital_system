<?php
// 1. INITIALIZATION & SESSIONS (Must be first)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_once __DIR__ . '/../config/mpesa.php';
require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$patient_id = intval($_GET['id'] ?? 0);
$status_message = '';
$status_type = 'success';

// ==============================================================================
// 2. ACTION HANDLERS (MOVED TO TOP TO PREVENT "HEADERS ALREADY SENT")
// ==============================================================================

// Fetch patient data early so it's available for handlers (like appointments)
$stmt = $conn->prepare("SELECT p.*, u.full_name as doctor_name, u.specialization FROM patients p LEFT JOIN users u ON p.doctor_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($patient_id <= 0) {
    // We handle the error later in the HTML section to keep the UI consistent
} else {
    $vitalsHasSpo2 = false;
    $vitalsColumnsRes = $conn->query("SHOW COLUMNS FROM vitals");
    if ($vitalsColumnsRes) {
        while ($col = $vitalsColumnsRes->fetch_assoc()) {
            if (($col['Field'] ?? '') === 'spo2') {
                $vitalsHasSpo2 = true;
                break;
            }
        }
    }

    $conn->query("CREATE TABLE IF NOT EXISTS external_referrals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        referred_facility VARCHAR(200) NOT NULL,
        referred_doctor VARCHAR(150) DEFAULT NULL,
        specialty VARCHAR(150) DEFAULT NULL,
        reason VARCHAR(255) NOT NULL,
        urgency ENUM('Routine', 'Urgent', 'Emergency') NOT NULL DEFAULT 'Routine',
        notes TEXT DEFAULT NULL,
        status ENUM('Pending', 'Accepted', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
        created_by INT DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    // Consultation billing is created only for registered patients.
    // Walk-in patients are explicitly exempt; clean up any legacy KES 200
    // consultation charge that may have been created before this rule was fixed.
    if (!empty($patient['is_walkin'])) {
        remove_walkin_consultation_charge($conn, $patient_id);
    } else {
        ensure_registered_consultation_charge($conn, $patient_id, 200.00);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postedToken = $_POST['csrf_token'] ?? '';
        if (!hash_equals($csrfToken, $postedToken)) {
            header("Location: patient_dashboard.php?id=$patient_id&tab=clinical&error=csrf");
            exit;
        }
    }

    // Handle Next Appointment Booking
    if(isset($_POST['book_appointment'])) {
        $app_date = $_POST['appointment_date'];
        $app_time = $_POST['appointment_time'];
        $reason = $_POST['reason'] ?? 'Follow-up';
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, 'Scheduled')");
        $stmt->bind_param("iisss", $patient_id, $patient['doctor_id'], $app_date, $app_time, $reason);
        if($stmt->execute()) {
            echo "<script>alert('Appointment booked successfully');</script>";
        }
    }

    // Deletions are handled by the dedicated POST-only endpoint.


    // Handle External Referral
    if(isset($_POST['add_referral'])){
        $referred_facility = $_POST['referred_facility'] ?? '';
        $referred_doctor = $_POST['referred_doctor'] ?? '';
        $specialty = $_POST['specialty'] ?? '';
        $reason = $_POST['referral_reason'] ?? '';
        $urgency = $_POST['urgency'] ?? 'Routine';
        $notes = $_POST['referral_notes'] ?? '';

        $stmt = $conn->prepare("INSERT INTO external_referrals (patient_id, referred_facility, referred_doctor, specialty, reason, urgency, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
        $stmt->bind_param("issssss", $patient_id, $referred_facility, $referred_doctor, $specialty, $reason, $urgency, $notes);
        if($stmt->execute()) {
            $stmt->close();
            header("Location: patient_dashboard.php?id=$patient_id&tab=clinical&referral_success=1");
            exit;
        }
    }

    // Patient-facing pages no longer collect money. All payments are processed
    // through the Central Cashier to keep financial control in one place.
    if(isset($_POST['register_payment'])){
        header("Location: /hospital_system/cashier/index.php");
        exit;
    }

    // NEW: Clinical History & Invoice Queries
    $clinical_history = $conn->query("SELECT * FROM encounters WHERE patient_id=$patient_id ORDER BY created_at DESC");
    $invoices = $conn->query("SELECT * FROM invoices WHERE patient_id=$patient_id ORDER BY created_at DESC");
    $referrals = $conn->query("SELECT * FROM external_referrals WHERE patient_id=$patient_id ORDER BY created_at DESC");
    $labResults = [];
    $labResultsTable = $conn->query("SHOW TABLES LIKE 'lab_results'");
    if ($labResultsTable && $labResultsTable->num_rows > 0) {
        $labResultColumns = [];
        $labColsRes = $conn->query("SHOW COLUMNS FROM lab_results");
        if ($labColsRes) {
            while ($col = $labColsRes->fetch_assoc()) {
                $labResultColumns[] = $col['Field'];
            }
        }

        $labWhere = '';
        if (in_array('patient_id', $labResultColumns, true)) {
            $labWhere = 'patient_id = ' . (int)$patient_id;
        } elseif (in_array('encounter_id', $labResultColumns, true) && $encounter && !empty($encounter['id'])) {
            $labWhere = 'encounter_id = ' . (int)$encounter['id'];
        }

        if ($labWhere !== '') {
            $orderColumn = in_array('created_at', $labResultColumns, true) ? 'created_at' : $labResultColumns[0];
            $labResultsRes = $conn->query("SELECT * FROM lab_results WHERE {$labWhere} ORDER BY {$orderColumn} DESC");
            if ($labResultsRes) {
                while ($row = $labResultsRes->fetch_assoc()) {
                    $labResults[] = [
                        'test_name' => $row['test_name'] ?? ($row['service_name'] ?? ($row['test'] ?? 'Lab Result')),
                        'result_value' => $row['result_value'] ?? ($row['result'] ?? ''),
                        'notes' => $row['notes'] ?? ($row['interpretation'] ?? ''),
                        'status' => $row['status'] ?? 'completed',
                        'created_at' => $row['created_at'] ?? null,
                    ];
                }
            }
        }
    }
    if (!$labResults) {
        $labFallback = $conn->query("SELECT sm.service_name AS test_name, ps.doctor_notes AS notes, ps.status, ps.created_at FROM patient_services ps LEFT JOIN services_master sm ON sm.id = ps.service_id WHERE ps.patient_id = $patient_id AND (ps.category = 'lab' OR sm.category = 'lab') ORDER BY ps.created_at DESC");
        if ($labFallback) {
            while ($row = $labFallback->fetch_assoc()) {
                $labResults[] = [
                    'test_name' => $row['test_name'] ?? 'Lab Test',
                    'result_value' => '',
                    'notes' => $row['notes'] ?? '',
                    'status' => $row['status'] ?? 'pending',
                    'created_at' => $row['created_at'] ?? null,
                ];
            }
        }
    }


    // Dashboard data required by the Clinical Encounter and Billing tabs.
    // Keep these queries defensive so older databases remain usable.
    $latestVital = null;
    $vitals = $conn->query("SELECT * FROM vitals WHERE patient_id = " . (int)$patient_id . " ORDER BY created_at DESC, id DESC");
    if (!$vitals) {
        $vitals = $conn->query("SELECT * FROM vitals WHERE patient_id = " . (int)$patient_id . " ORDER BY id DESC");
    }
    if ($vitals) {
        $vitals->data_seek(0);
        $latestVital = $vitals->fetch_assoc();
        $vitals->data_seek(0);
    }

    $encounter = null;
    $encounterRes = $conn->query("SELECT * FROM encounters WHERE patient_id = " . (int)$patient_id . " ORDER BY created_at DESC, id DESC LIMIT 1");
    if ($encounterRes) {
        $encounter = $encounterRes->fetch_assoc();
    }

    $all_services = $conn->query("SELECT id, category, service_name, price, active FROM services_master WHERE active = 1 ORDER BY category, service_name");
    if (!$all_services) {
        $all_services = $conn->query("SELECT id, category, service_name, price, active FROM services_master ORDER BY category, service_name");
    }

    $patient_services = $conn->query("SELECT ps.*, sm.service_name, sm.category AS svc_category FROM patient_services ps LEFT JOIN services_master sm ON sm.id = ps.service_id WHERE ps.patient_id = " . (int)$patient_id . " ORDER BY ps.created_at DESC, ps.id DESC");
    $prescriptions = $conn->query("SELECT pr.*, ps.drug_name, ps.selling_price AS stock_selling_price FROM prescriptions pr LEFT JOIN pharmacy_stock ps ON ps.id = pr.medicine_id WHERE pr.patient_id = " . (int)$patient_id . " ORDER BY pr.created_at DESC, pr.id DESC");
    if (!$prescriptions) {
        $prescriptions = $conn->query("SELECT pr.*, ps.drug_name, ps.selling_price AS stock_selling_price FROM prescriptions pr LEFT JOIN pharmacy_stock ps ON ps.id = pr.medicine_id ORDER BY pr.created_at DESC, pr.id DESC");
    }
    $stock = $conn->query("SELECT id, drug_name, quantity, selling_price FROM pharmacy_stock WHERE quantity > 0 ORDER BY drug_name");


    // Walk-in requested service
// Prefer the actual service already attached to the walk-in (for example a lab test).
// Reception walk-ins fall back to the selected clinic/service category stored on the patient.
$walkinRequestedService = '';
$walkinRequestedServiceId = 0;
if (!empty($patient['is_walkin'])) {
    $walkinServiceStmt = $conn->prepare("SELECT ps.service_id, sm.service_name FROM patient_services ps INNER JOIN services_master sm ON sm.id = ps.service_id WHERE ps.patient_id = ? AND ps.status <> 'Cancelled' ORDER BY ps.created_at DESC, ps.id DESC LIMIT 1");
    if ($walkinServiceStmt) {
        $walkinServiceStmt->bind_param('i', $patient_id);
        $walkinServiceStmt->execute();
        $walkinServiceRow = $walkinServiceStmt->get_result()->fetch_assoc();
        $walkinServiceStmt->close();
        if ($walkinServiceRow) {
            $walkinRequestedServiceId = (int)$walkinServiceRow['service_id'];
            $walkinRequestedService = (string)$walkinServiceRow['service_name'];
        }
    }
    if ($walkinRequestedService === '' && !empty($patient['clinic_category'])) {
        $walkinRequestedService = (string)$patient['clinic_category'];
    }
}

// Billing Calculations
    // ==============================================================================
    // 4. BILLING CALCULATIONS
    // ==============================================================================
    // The dashboard must use the same authoritative invoice totals as
    // billing/view_invoice.php. Do not rebuild the bill from patient_services
    // and prescriptions because that can omit charges such as Consultation.
    $total_charges = 0.0;

    $invoiceTotalsRes = $conn->query("
        SELECT
            i.id,
            CASE
                WHEN COALESCE(items.items_total, 0) > 0 THEN items.items_total
                ELSE COALESCE(i.total, 0)
            END AS invoice_total
        FROM invoices i
        LEFT JOIN (
            SELECT invoice_id, SUM(total) AS items_total
            FROM invoice_items
            GROUP BY invoice_id
        ) items ON items.invoice_id = i.id
        WHERE i.patient_id = " . (int)$patient_id . "
        ORDER BY i.id ASC
    ");

    if ($invoiceTotalsRes) {
        while ($invoiceRow = $invoiceTotalsRes->fetch_assoc()) {
            $total_charges += (float)($invoiceRow['invoice_total'] ?? 0);
        }
    }

    // Payments are invoice-specific, matching billing/view_invoice.php.
    $total_paid = 0.0;
    $paidStmt = $conn->prepare("
        SELECT COALESCE(SUM(p.amount), 0) AS total_paid
        FROM payments p
        INNER JOIN invoices i ON i.id = p.invoice_id
        WHERE i.patient_id = ?
    );
    if ($paidStmt) {
        $paidStmt->bind_param('i', $patient_id);
        $paidStmt->execute();
        $paidRes = $paidStmt->get_result();
    if ($paidRes) {
        $total_paid = (float)($paidRes->fetch_assoc()['total_paid'] ?? 0);
    }
    if (isset($paidStmt) && $paidStmt) $paidStmt->close();

    // Logic to prevent negative balance.
    $balance_due = max($total_charges - $total_paid, 0.0);

$insuranceCovered = 0;
$amountToPayNow = $balance_due;
$currentPayerLabel = 'Cash / Self Pay';
$currentCopayEstimate = 0;

$financialAccountTable = $conn->query("SHOW TABLES LIKE 'patient_financial_accounts'");
if ($financialAccountTable && $financialAccountTable->num_rows > 0) {
    $financialAccount = $conn->query("SELECT pfa.*, p.payer_name FROM patient_financial_accounts pfa LEFT JOIN payers p ON pfa.current_payer_id = p.id WHERE pfa.patient_id = $patient_id LIMIT 1")->fetch_assoc();
    if ($financialAccount) {
        $insuranceCovered = min($balance_due, (float)($financialAccount['total_claims_outstanding'] ?? 0));
        $currentCopayEstimate = (float)($financialAccount['total_copay_due'] ?? 0);
        $amountToPayNow = max($balance_due - $insuranceCovered, 0);
        if (!empty($financialAccount['payer_name'])) {
            $currentPayerLabel = $financialAccount['payer_name'];
        } elseif (!empty($financialAccount['account_class'])) {
            $currentPayerLabel = $financialAccount['account_class'];
        }
    }
}

// ==============================================================================

}

// 4. BEGIN OUTPUT
// ==============================================================================
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

if ($patient_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid patient ID</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<style>
    :root {
        --primary-blue: #0056b3;
        --secondary-blue: #003366;
        --accent-blue: #e3f2fd;
        --border-color: #bbdefb;
    }
    body{font-family: 'Segoe UI', sans-serif; background:#f0f4f8;}
    .container{padding:24px 32px;max-width:none;width:100%;margin:0;box-sizing:border-box;}
    
    .header-section { background: linear-gradient(135deg, var(--secondary-blue), var(--primary-blue)); color: white; padding: 25px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .header-content { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .info-label { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #bbdefb; }
    .info-value { font-size: 16px; font-weight: 600; display: block; margin-bottom: 10px;}
    
    .dashboard-tabs{display:flex; list-style:none; padding:0; margin-bottom:0; border-bottom: 2px solid var(--primary-blue); flex-wrap:wrap; width:100%;}
    .dashboard-tabs li{cursor:pointer; padding:15px 25px; background:#d1d9e6; margin-right:5px; border-radius:10px 10px 0 0; font-weight:bold; transition: 0.2s; color: var(--secondary-blue);}
    .dashboard-tabs li.active{background:#fff; color:var(--primary-blue); border-bottom: 3px solid #fff; margin-bottom: -2px;}
    
    .card{background:#fff; border-radius:0 0 10px 10px; padding:30px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border:none; width:100%; box-sizing:border-box;}
    
    .clinical-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
    .module-card { border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; background: #fcfdfe; }
    .module-card h4 { margin-top: 0; color: var(--secondary-blue); font-size: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 10px; }
    
    textarea { width: 100%; height: 80px; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; font-size: 13px; resize: vertical; background: #fff; }
    .table-custom { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .table-custom th { background: var(--accent-blue); color: var(--secondary-blue); padding: 12px; text-align: left; border-bottom: 2px solid var(--border-color); }
    .table-custom td { padding: 12px; border-bottom: 1px solid #eee; }
    
    .btn-save { background: var(--primary-blue); color: white; padding: 15px 40px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; float: right; margin-top: 20px; }
    .btn-save:hover { background: var(--secondary-blue); }
    
    .lab-order-box { background: var(--accent-blue); padding: 20px; border-radius: 8px; border-left: 5px solid var(--primary-blue); margin-top: 20px;}
    .sub-card { background:#f8fbff; border:1px solid var(--border-color); border-radius:10px; padding:20px; margin-bottom:24px; }
    .vitals-form-grid { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:14px; }
    .vitals-form-grid input { width:100%; padding:10px; border:1px solid #ced4da; border-radius:6px; }
    .status-chip { display:inline-flex; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; text-transform:uppercase; }
    .status-chip.pending { background:#fff3cd; color:#856404; }
    .status-chip.completed { background:#d4edda; color:#155724; }
    .badge-info { background: var(--primary-blue); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
    .coverage-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin-bottom:25px; }
    .coverage-card { background:#f8fbff; border:1px solid var(--border-color); border-radius:10px; padding:18px; }
    .coverage-card h4 { margin:0 0 8px; color:var(--secondary-blue); }
    .coverage-value { font-size:18px; font-weight:700; color:var(--primary-blue); }
    .coverage-subtext { font-size:12px; color:#666; margin-top:6px; }
    .coverage-form { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:16px; background:#fcfdfe; border:1px solid var(--border-color); border-radius:10px; padding:20px; }
    .coverage-form input, .coverage-form select, .coverage-form textarea { width:100%; padding:10px; border:1px solid #ced4da; border-radius:6px; box-sizing:border-box; }
    .coverage-form textarea { min-height:100px; resize:vertical; }
    .coverage-form .full-width { grid-column:1 / -1; }
    .coverage-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }

    @media (max-width: 992px) {
        .header-content { grid-template-columns: 1fr; }
        .clinical-grid { grid-template-columns: 1fr; }
        .coverage-form { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .container { padding: 16px; }
        .dashboard-tabs li { width: 100%; margin-right: 0; margin-bottom: 6px; border-radius: 8px; }
    }
</style>

<div class="container">
    <div class="header-section">
        <div class="header-content">
            <div>
                <span class="info-label">Patient Full Name</span>
                <span class="info-value" style="font-size: 24px;"><?= htmlspecialchars($patient['full_name']) ?></span>
                <div style="display:flex; gap:30px;">
                    <div><span class="info-label">Patient No</span><span class="info-value"><?= htmlspecialchars($patient['patient_number']) ?></span></div>
                    <div><span class="info-label">Current Balance</span><span class="info-value" style="color:#ffeb3b;">KSH <?= number_format($balance_due, 2) ?></span></div>
                </div>
            </div>
            <div style="text-align:right; border-left: 1px solid rgba(255,255,255,0.2); padding-left: 20px;">
                <span class="info-label">Primary Consultant</span>
                <span class="info-value">Dr. <?= htmlspecialchars($patient['doctor_name'] ?? 'Not Assigned') ?></span>
                <div style="display:flex; gap:8px; justify-content:flex-end;"><a href="/hospital_system/maternity/add.php?patient_id=<?= (int)$patient_id ?>" style="background:#ffecf3; color:#c2185b; border:none; padding:5px 12px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:700;">Maternity Visit</a><button onclick="showTab('billing')" style="background:#fff; color:var(--primary-blue); border:none; padding:5px 15px; border-radius:5px; cursor:pointer;">Quick Pay</button></div>
            </div>
        </div>
    </div>

    <ul class="dashboard-tabs">
        <li onclick="showTab('clinical')" id="tab-clinical" class="active">Clinical Encounter</li>
        <li onclick="showTab('services')" id="tab-services">Procedures & Billing</li>
        <li onclick="showTab('prescriptions')" id="tab-prescriptions">Pharmacy & Prescriptions</li>
        <li onclick="showTab('billing')" id="tab-billing">Billing</li>
        <li onclick="showTab('coverage')" id="tab-coverage">Insurance & SHA</li>
    </ul>

   <div id="clinical" class="card">
    <?php if (isset($_GET['payment_success'])): ?><div class="alert alert-success" style="font-weight:600; margin-bottom:20px;"><i class="fas fa-check-circle"></i> Payment successful. The M-Pesa payment has been recorded.</div><?php endif; ?>
    <?php if (isset($_GET['vitals_saved'])): ?><div class="alert alert-success">Vitals saved successfully.</div><?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?><div class="alert alert-danger">Security token mismatch. Please retry the action.</div><?php endif; ?>

    <div class="sub-card" style="border-left:5px solid var(--primary-blue);"><div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;"><div><h3 style="margin:0;color:var(--primary-blue);">Current Clinical Assessment</h3><p style="margin:6px 0 0;color:#666;">Today's vitals and clinical assessment are recorded against the active Visit through Clinical Care. This dashboard is for patient history and review.</p></div><a href="/hospital_system/clinical/care.php?patient_id=<?= (int)$patient_id ?>" class="btn-save" style="float:none; margin-top:0; text-decoration:none;">Open Clinical Care</a></div></div>

    <h3>Vital Signs History</h3>

    <table class="table-custom" style="margin-bottom: 30px;">
        <thead>
            <tr><th>BP</th><th>Temp</th><th>Pulse</th><th>SPO2</th><th>Weight</th><th>Timestamp</th></tr>
        </thead>
        <tbody>
            <?php while($v = $vitals->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($v['bp']) ?></strong></td>
                <td><?= htmlspecialchars($v['temperature']) ?>°C</td>
                <td><?= htmlspecialchars($v['pulse']) ?></td>
                <td><?= htmlspecialchars($v['spo2'] ?? '--') ?>%</td>
                <td><?= htmlspecialchars($v['weight']) ?> kg</td>
                <td><?= date('d M Y, H:i', strtotime($v['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>



    <div class="sub-card">
        <h3 style="margin-top:0; color:var(--primary-blue);">Laboratory Results & Requests</h3>
        <p style="color:#666; margin-top:-4px;">Displays completed lab results when available, or pending lab requests from the patient service log.</p>
        <table class="table-custom">
            <thead><tr><th>Test</th><th>Result</th><th>Notes / Interpretation</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                <?php if ($labResults): ?>
                    <?php foreach ($labResults as $labRow): ?>
                        <tr>
                            <td><?= htmlspecialchars($labRow['test_name'] ?? 'Lab Test') ?></td>
                            <td><?= htmlspecialchars($labRow['result_value'] ?: '-- Pending --') ?></td>
                            <td><?= htmlspecialchars($labRow['notes'] ?: 'No notes yet.') ?></td>
                            <td><span class="status-chip <?= strtolower(($labRow['status'] ?? 'pending')) === 'completed' ? 'completed' : 'pending' ?>">
                                <?= htmlspecialchars($labRow['status'] ?? 'Pending') ?>
                            </span></td>
                            <td><?= !empty($labRow['created_at']) ? date('d M Y, H:i', strtotime($labRow['created_at'])) : 'N/A' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No lab results or requests are available yet for this patient.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="sub-card"><h3 style="color:var(--primary-blue);margin:0;">Clinical History</h3><p style="color:#666;">Clinical notes, diagnoses, examination and management are recorded in the current Visit from Clinical Care. This dashboard does not create another encounter.</p><a href="/hospital_system/clinical/care.php?patient_id=<?= (int)$patient_id ?>" class="btn-save" style="float:none;margin-top:10px;text-decoration:none;">Open Clinical Care</a></div>

<div id="services" class="card" style="display:none;"><h3>Services & Laboratory</h3><p style="color:#666;">New services and laboratory requests are created from the active Visit in Clinical Care.</p><a href="/hospital_system/clinical/orders.php?patient_id=<?= (int)$patient_id ?>" class="btn-save" style="float:none;margin-top:10px;text-decoration:none;">Open Orders & Referrals</a></div>

<div id="prescriptions" class="card" style="display:none;"><h3>Medication History</h3><p style="color:#666;">Prescriptions are created from the active Visit by authorized clinical staff and dispensed through Pharmacy.</p><a href="/hospital_system/clinical/orders.php?patient_id=<?= (int)$patient_id ?>" class="btn-save" style="float:none;margin-top:10px;text-decoration:none;">Open Clinical Orders</a></div>

<div id="billing" class="card" style="display:none;">
        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:20px; margin-bottom:30px;">
            <div style="padding: 20px; border-radius: 8px; text-align: center; background:var(--accent-blue);">
                <span class="info-label">Total Invoiced</span><br>
                <span style="font-size:24px; font-weight:bold; color:var(--secondary-blue);">KSH <?= number_format($total_charges, 2) ?></span>
            </div>
            <div style="padding: 20px; border-radius: 8px; text-align: center; background:#e8f5e9;">
                <span class="info-label">Total Collected</span><br>
                <span style="font-size:24px; font-weight:bold; color:#2e7d32;">KSH <?= number_format($total_paid, 2) ?></span>
            </div>
            <div style="padding: 20px; border-radius: 8px; text-align: center; background:#eef7ff;">
                <span class="info-label">Covered by Insurance / SHA</span><br>
                <span style="font-size:24px; font-weight:bold; color:#1565c0;">KSH <?= number_format($insuranceCovered, 2) ?></span>
                <div style="font-size:12px; color:#666; margin-top:6px;">Payer: <?= htmlspecialchars($currentPayerLabel); ?></div>
            </div>
            <div style="padding: 20px; border-radius: 8px; text-align: center; background:#ffebee;">
                <span class="info-label">Amount to Pay</span><br>
                <span style="font-size:24px; font-weight:bold; color:#c62828;">KSH <?= number_format($amountToPayNow, 2) ?></span>
                <div style="font-size:12px; color:#666; margin-top:6px;">Co-pay est: KSH <?= number_format($currentCopayEstimate, 2); ?></div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
            <div style="background:#fdfefe; border:1px solid #ddd; padding:25px; border-radius:10px;">
                <h4><i class="fas fa-cash-register"></i> Payment Collection</h4>
                <p style="color:#666; font-size:13px;">All patient payments are collected through the Central Cashier. Clinical and patient-facing screens only display the balance.</p>
                <div style="padding:18px; background:#eef7ff; border-radius:8px; margin-top:18px;">
                    <strong>Outstanding balance: KSH <?= number_format($amountToPayNow, 2) ?></strong>
                    <p style="margin:8px 0 15px; color:#555;">The cashier can accept Cash, M-Pesa and other configured payment methods, including partial payments.</p>
                    <a href="/hospital_system/cashier/index.php" class="btn btn-success">
                        <i class="fas fa-cash-register"></i> Open Central Cashier
                    </a>
                </div>
            </div>

            <div>
                <h4>Invoices & Printing</h4>
                <table class="table-custom">
                    <thead><tr><th>Invoice</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($inv = $invoices->fetch_assoc()): ?>
                        <tr>
                            <td>#INV-<?= $inv['id'] ?></td>
                            <td><?= date('d/m/Y', strtotime($inv['created_at'])) ?></td>
                            <td><span class="badge-info"><?= strtoupper($inv['status']) ?></span></td>
                            <td>
                                <a href="/hospital_system/billing/view_invoice.php?id=<?= $inv['id'] ?>" target="_blank">View</a> | 
                                <a href="/hospital_system/billing/view_invoice.php?id=<?= $inv['id'] ?>&print=1" target="_blank">Print</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div id="coverage" class="card" style="display:none;">
        <h3>Insurance & SHA</h3>
        <p style="color:#666; margin-top:-8px; margin-bottom:20px;">Starter coverage module for payer setup, SHA details, insurer capture, pre-authorization and co-pay workflow.</p>

        <div class="coverage-grid">
            <div class="coverage-card">
                <h4>Payment Class</h4>
                <div class="coverage-value">Self Pay / Cash</div>
                <div class="coverage-subtext">Upgrade this patient to SHA or Insurance once payer schema is introduced.</div>
            </div>
            <div class="coverage-card">
                <h4>SHA Status</h4>
                <div class="coverage-value">Not Linked</div>
                <div class="coverage-subtext">Capture SHA number, eligibility and authorization here.</div>
            </div>
            <div class="coverage-card">
                <h4>Insurance Status</h4>
                <div class="coverage-value">No Active Cover</div>
                <div class="coverage-subtext">Attach insurer, member number, plan and employer/corporate panel.</div>
            </div>
            <div class="coverage-card">
                <h4>Expected Co-pay</h4>
                <div class="coverage-value">KSH 0.00</div>
                <div class="coverage-subtext">Use this area later for co-pay, deductible and authorization balance.</div>
            </div>
        </div>

        <form class="coverage-form">
            <div>
                <label class="info-label">Funding Type</label>
                <select>
                    <option>Cash / Self Pay</option>
                    <option>SHA</option>
                    <option>Private Insurance</option>
                    <option>Corporate / Panel</option>
                </select>
            </div>
            <div>
                <label class="info-label">Scheme / Plan</label>
                <input type="text" placeholder="e.g. SHA Outpatient, Jubilee, AAR, Madison">
            </div>
            <div>
                <label class="info-label">Member / Card Number</label>
                <input type="text" placeholder="Enter SHA or insurance member number">
            </div>
            <div>
                <label class="info-label">Principal / Employer</label>
                <input type="text" placeholder="Employer, principal member, or sponsor">
            </div>
            <div>
                <label class="info-label">Authorization Number</label>
                <input type="text" placeholder="Pre-auth / approval number">
            </div>
            <div>
                <label class="info-label">Co-pay Estimate (KSH)</label>
                <input type="number" step="0.01" placeholder="0.00">
            </div>
            <div class="full-width">
                <label class="info-label">Coverage Notes</label>
                <textarea placeholder="Capture benefit limits, exclusions, authorization notes, payer instructions and claim comments."></textarea>
            </div>
        </form>

        <div class="coverage-actions">
            <button type="button" class="btn-save" style="float:none; margin-top:0;">Save Coverage Profile</button>
            <button type="button" class="btn-save" style="float:none; margin-top:0; background:#6c757d;">Verify SHA Eligibility</button>
            <button type="button" class="btn-save" style="float:none; margin-top:0; background:#17a2b8;">Create Pre-Authorization</button>
        </div>
    </div>
</div>

<script>
function showTab(tabId) {
    document.querySelectorAll('.card').forEach(c => c.style.display = 'none');
    document.querySelectorAll('.dashboard-tabs li').forEach(l => l.classList.remove('active'));
    document.getElementById(tabId).style.display = 'block';
    document.getElementById('tab-' + tabId).classList.add('active');
}

function updatePrice(selectElement, targetInputId) {
    const price = selectElement.options[selectElement.selectedIndex].getAttribute('data-price');
    document.getElementById(targetInputId).value = price || '';
}

// Automatically load the walk-in's previously selected service and its price.
// This makes the dashboard ready for the next action without selecting the service again.
document.addEventListener('DOMContentLoaded', function () {
    const requestedServiceId = <?= (int)$walkinRequestedServiceId ?>;
    if (requestedServiceId > 0) {
        const serviceSelect = document.querySelector('select[name="service_id"]');
        if (serviceSelect && serviceSelect.value === String(requestedServiceId)) {
            updatePrice(serviceSelect, 'svc_p');
        }

        const labSelect = document.querySelector('#services select[name="service_id"]');
        if (labSelect && labSelect.value === String(requestedServiceId)) {
            updatePrice(labSelect, 'lab_service_price');
        }
    }
});

// Keep the active tab after reload if specified in URL
const urlParams = new URLSearchParams(window.location.search);
const activeTab = urlParams.get('tab');
if(activeTab) showTab(activeTab);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
