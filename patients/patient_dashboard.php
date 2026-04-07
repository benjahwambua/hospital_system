<?php
// 1. INITIALIZATION & SESSIONS (Must be first)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
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
    // --- ADDITION: AUTO-CONSULTATION BILLING ---
    $check_stmt = $conn->prepare("SELECT id FROM patient_services WHERE patient_id = ? AND service_id = (SELECT id FROM services_master WHERE service_name = 'Consultation' LIMIT 1) AND DATE(created_at) = CURDATE()");
    $check_stmt->bind_param("i", $patient_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->num_rows;
    $check_stmt->close();

    if ($exists == 0) {
        $consult = $conn->query("SELECT id, price FROM services_master WHERE service_name = 'Consultation' LIMIT 1")->fetch_assoc();
        if ($consult) {
            $ins_stmt = $conn->prepare("INSERT INTO patient_services (patient_id, service_id, price, created_at, status) VALUES (?, ?, ?, NOW(), 'Completed')");
            $ins_stmt->bind_param("iid", $patient_id, $consult['id'], $consult['price']);
            $ins_stmt->execute();
        }
    }
    // ------------------------------------------

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postedToken = $_POST['csrf_token'] ?? '';
        if (!hash_equals($csrfToken, $postedToken)) {
            header("Location: patient_dashboard.php?id=$patient_id&tab=clinical&error=csrf");
            exit;
        }
    }

    // Handle vitals retake / edit
    if (isset($_POST['save_vitals'])) {
        $vitalId = max(0, (int)($_POST['vital_id'] ?? 0));
        $temperature = trim((string)($_POST['temperature'] ?? ''));
        $bp = trim((string)($_POST['bp'] ?? ''));
        $weight = trim((string)($_POST['weight'] ?? ''));
        $pulse = trim((string)($_POST['pulse'] ?? ''));
        $respiration = trim((string)($_POST['respiration'] ?? ''));
        $spo2 = trim((string)($_POST['spo2'] ?? ''));

        if ($vitalId > 0) {
            if ($vitalsHasSpo2) {
                $stmt = $conn->prepare("UPDATE vitals SET temperature = ?, bp = ?, weight = ?, pulse = ?, respiration = ?, spo2 = ? WHERE id = ? AND patient_id = ?");
                $stmt->bind_param('ssssssii', $temperature, $bp, $weight, $pulse, $respiration, $spo2, $vitalId, $patient_id);
            } else {
                $stmt = $conn->prepare("UPDATE vitals SET temperature = ?, bp = ?, weight = ?, pulse = ?, respiration = ? WHERE id = ? AND patient_id = ?");
                $stmt->bind_param('sssssii', $temperature, $bp, $weight, $pulse, $respiration, $vitalId, $patient_id);
            }
        } else {
            if ($vitalsHasSpo2) {
                $stmt = $conn->prepare("INSERT INTO vitals (temperature, bp, weight, pulse, respiration, spo2, patient_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('ssssssi', $temperature, $bp, $weight, $pulse, $respiration, $spo2, $patient_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO vitals (temperature, bp, weight, pulse, respiration, patient_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('sssssi', $temperature, $bp, $weight, $pulse, $respiration, $patient_id);
            }
        }

        $stmt->execute();
        $stmt->close();

        header("Location: patient_dashboard.php?id=$patient_id&tab=clinical&vitals_saved=1");
        exit;
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

    // Handle Prescription with Price Override
    if(isset($_POST['add_prescription_stock'])) {
        $medicine_id = intval($_POST['medicine_id']);
        $qty = intval($_POST['quantity']);
        $price_override = floatval($_POST['selling_price']); 
        $instructions = $_POST['dosage_instructions'] ?? '';
        $lineTotal = max($qty, 0) * max($price_override, 0);

        $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, medicine_id, quantity, invoice_id, frequency, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiids", $patient_id, $medicine_id, $qty, $price_override, $instructions);
        $stmt->execute();
        $stmt->close();

        if ($lineTotal > 0) {
            $note = "Pharmacy sale - patient_id={$patient_id}, medicine_id={$medicine_id}";
            $accStmt = $conn->prepare("INSERT INTO accounting_entries (account, debit, credit, note, created_at) VALUES ('Pharmacy Sales', ?, 0, ?, NOW())");
            if ($accStmt) {
                $accStmt->bind_param('ds', $lineTotal, $note);
                $accStmt->execute();
                $accStmt->close();
            }
        }

        header("Location: patient_dashboard.php?id=$patient_id&tab=prescriptions&success=1");
        exit;
    }

    // Handle Service/Billing Item Add
    if(isset($_POST['add_service'])){
        $service_id = intval($_POST['service_id']);
        $price = floatval($_POST['price']);
        if($service_id > 0){
            $stmt = $conn->prepare("INSERT INTO patient_services (patient_id, service_id, price, created_at, status) VALUES (?, ?, ?, NOW(), 'Completed')");
            $stmt->bind_param("iid", $patient_id, $service_id, $price);
            $stmt->execute();
            header("Location: patient_dashboard.php?id=$patient_id&tab=services&added=1");
            exit;
        }
    }

    // Handle Deletion Logic
    if(isset($_GET['delete_item'])) {
        $item_id = intval($_GET['item_id']);
        $type = $_GET['type'];
        if($type == 'service') {
            $conn->query("DELETE FROM patient_services WHERE id = $item_id AND patient_id = $patient_id");
        } elseif($type == 'prescription') {
            $conn->query("DELETE FROM prescriptions WHERE id = $item_id AND patient_id = $patient_id");
        }
        header("Location: patient_dashboard.php?id=$patient_id&tab=billing&deleted=1");
        exit;
    }

    // Existing Lab Request Handler
    if(isset($_POST['add_lab_request'])){
        $service_id = intval($_POST['service_id']);
        $price = floatval($_POST['price']);
        $instructions = $_POST['lab_instructions'] ?? '';
        if($service_id > 0){
            $stmt = $conn->prepare("INSERT INTO patient_services (patient_id, service_id, category, price, doctor_notes, created_at, status) VALUES (?, ?, 'lab', ?, ?, NOW(), 'Pending')");
            $stmt->bind_param("iids", $patient_id, $service_id, $price, $instructions);
            $stmt->execute();
            $stmt->close();
            header("Location: patient_dashboard.php?id=$patient_id&tab=services&lab_success=1");
            exit;
        }
    }

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

    // Existing Save Clinical Handler (UPDATED TO REPLACE/UPDATE)
    if(isset($_POST['save_clinical'])){
        $clinic_patient_id = intval($_POST['patient_id']);
        $params = [
            $clinic_patient_id,
            $_POST['presenting_complaint'] ?? '',
            $_POST['hpc'] ?? '',
            $_POST['medical_history'] ?? '',
            $_POST['surgical_history'] ?? '',
            $_POST['family_history'] ?? '',
            $_POST['drug_history'] ?? '',
            $_POST['allergies'] ?? '',
            $_POST['social_history'] ?? '',
            $_POST['review_systems'] ?? '',
            $_POST['physical_exam'] ?? '',
            $_POST['diagnosis'] ?? '',
            $_POST['differential_diagnosis'] ?? '',
            $_POST['investigations'] ?? '',
            $_POST['management_plan'] ?? '',
            $_POST['prescription_instructions'] ?? '',
            $_POST['doctor_notes'] ?? ''
        ];

        $stmt = $conn->prepare("REPLACE INTO encounters (patient_id, presenting_complaint, hpc, medical_history, surgical_history, family_history, drug_history, allergies, social_history, review_systems, physical_exam, diagnosis, differential_diagnosis, investigations, management_plan, prescription_instructions, doctor_notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issssssssssssssss", ...$params);

        if($stmt->execute()){
            $stmt->close();
            header("Location: patient_dashboard.php?id=$patient_id&tab=clinical&success=1");
            exit;
        }
    }

   // --- UPDATED PAYMENT HANDLER ---
if(isset($_POST['register_payment'])){
    $amount = floatval($_POST['amount']);
    $method = $_POST['method'] ?? '';
    if($amount > 0){
        $service_total = 0;
        $service_total_result = $conn->query("SELECT COALESCE(SUM(price), 0) AS total FROM patient_services WHERE patient_id=$patient_id");
        if($service_total_result) {
            $service_total = (float)($service_total_result->fetch_assoc()['total'] ?? 0);
        }

        $prescription_total = 0;
        $prescription_total_result = $conn->query("SELECT COALESCE(SUM(quantity * invoice_id), 0) AS total FROM prescriptions WHERE patient_id=$patient_id");
        if($prescription_total_result) {
            $prescription_total = (float)($prescription_total_result->fetch_assoc()['total'] ?? 0);
        }

        $paid_total = 0;
        $paid_total_result = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM billing WHERE patient_id=$patient_id AND paid=1");
        if($paid_total_result) {
            $paid_total = (float)($paid_total_result->fetch_assoc()['total'] ?? 0);
        }

        $consultation_fee = 200;
        $current_balance = max((($service_total + $prescription_total + $consultation_fee) - $paid_total), 0);
        $invoice_status = $amount >= $current_balance ? 'paid' : 'partial';

        $stmt = $conn->prepare("INSERT INTO billing (patient_id, amount, method, paid, created_at) VALUES (?,?,?,1,NOW())");
        $stmt->bind_param("ids", $patient_id, $amount, $method);
        
        if($stmt->execute()){
            $invoice_stmt = $conn->prepare("INSERT INTO invoices (patient_id, status, payment_mode, created_at) VALUES (?, ?, ?, NOW())");
            $invoice_stmt->bind_param("iss", $patient_id, $invoice_status, $method);
            $invoice_stmt->execute();
            $invoice_id = $invoice_stmt->insert_id;
            
            header("Location: /hospital_system/pharmacy/view_invoice.php?id=" . $invoice_id);
            exit;
        }
    }
}
}

// ==============================================================================
// 3. DATA AGGREGATION (Queries for Display)
// ==============================================================================
if ($patient_id > 0) {
    $vitals = $conn->query("SELECT * FROM vitals WHERE patient_id=$patient_id ORDER BY created_at DESC");
    $latestVital = null;
    if ($vitals && $vitals->num_rows > 0) {
        $latestVital = $vitals->fetch_assoc();
        $vitals->data_seek(0);
    }
    $encounter = $conn->query("SELECT * FROM encounters WHERE patient_id=$patient_id ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
    $prescriptions = $conn->query("SELECT p.*, s.drug_name FROM prescriptions p LEFT JOIN pharmacy_stock s ON s.id=p.medicine_id WHERE p.patient_id=$patient_id ORDER BY p.created_at DESC");
    $patient_services = $conn->query("SELECT ps.*, sm.service_name, sm.category AS svc_category FROM patient_services ps LEFT JOIN services_master sm ON sm.id=ps.service_id WHERE ps.patient_id=$patient_id ORDER BY ps.created_at DESC");
    $billing = $conn->query("SELECT * FROM billing WHERE patient_id=$patient_id ORDER BY created_at DESC");
    $all_services = $conn->query("SELECT * FROM services_master ORDER BY category, service_name");
    $stock = $conn->query("SELECT id, drug_name, selling_price, quantity FROM pharmacy_stock WHERE quantity > 0 ORDER BY drug_name");

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


    // Billing Calculations
    // ==============================================================================
// 4. BILLING CALCULATIONS (UPDATED TO INCLUDE CONSULTATION)
// ==============================================================================
$total_charges = 0; 

// 1. Calculate Services
if($patient_services) { 
    while($s = $patient_services->fetch_assoc()) {
        $total_charges += $s['price']; 
    }
    $patient_services->data_seek(0); 
}

// 2. Calculate Prescriptions
if($prescriptions) { 
    while($p = $prescriptions->fetch_assoc()) {
        // Note: You are using 'invoice_id' as the price field in your prescription handler
        $total_charges += ($p['quantity'] * ($p['invoice_id'] ?? 0)); 
    }
    $prescriptions->data_seek(0); 
}

// 3. ADD CONSULTATION FEE (Matches view_invoice.php logic)
// Only add if this is a valid patient session
if ($patient_id > 0) {
    $total_charges += 200; 
}

// 4. Calculate Payments
$total_paid = 0;
// Calculate Payments (ensure this is only summing what is actually paid)
$total_paid = 0;
if($billing) { 
    while($b = $billing->fetch_assoc()) {
        if($b['paid']) $total_paid += $b['amount']; 
    }
    $billing->data_seek(0); 
}

// Logic to prevent negative balance
$balance_due = ($total_charges > $total_paid) ? ($total_charges - $total_paid) : 0;

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
}

// ==============================================================================
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
                <div style="display:flex; gap:8px; justify-content:flex-end;"><a href="/hospital_system/maternity/maternity_visit.php?patient_id=<?= (int)$patient_id ?>" style="background:#ffecf3; color:#c2185b; border:none; padding:5px 12px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:700;">Maternity Visit</a><button onclick="showTab('billing')" style="background:#fff; color:var(--primary-blue); border:none; padding:5px 15px; border-radius:5px; cursor:pointer;">Quick Pay</button></div>
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
    <?php if (isset($_GET['vitals_saved'])): ?><div class="alert alert-success">Vitals saved successfully.</div><?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?><div class="alert alert-danger">Security token mismatch. Please retry the action.</div><?php endif; ?>

    <div class="sub-card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div>
                <h3 style="margin:0; color:var(--primary-blue);">Vitals Retake / Edit</h3>
                <p style="margin:6px 0 0; color:#666;">Doctors can record a new set of vitals or load any historical reading for correction.</p>
            </div>
            <?php if ($latestVital): ?>
                <div class="badge-info">Latest vitals: <?= date('d M Y H:i', strtotime($latestVital['created_at'])) ?></div>
            <?php endif; ?>
        </div>
        <form method="post" style="margin-top:18px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="vital_id" id="vital_id" value="">
            <div class="vitals-form-grid">
                <div><label class="info-label">Temperature</label><input type="number" step="0.1" name="temperature" id="vital_temperature" value="<?= htmlspecialchars($latestVital['temperature'] ?? '') ?>"></div>
                <div><label class="info-label">Blood Pressure</label><input type="text" name="bp" id="vital_bp" value="<?= htmlspecialchars($latestVital['bp'] ?? '') ?>"></div>
                <div><label class="info-label">Weight</label><input type="number" step="0.1" name="weight" id="vital_weight" value="<?= htmlspecialchars($latestVital['weight'] ?? '') ?>"></div>
                <div><label class="info-label">Pulse</label><input type="number" name="pulse" id="vital_pulse" value="<?= htmlspecialchars($latestVital['pulse'] ?? '') ?>"></div>
                <div><label class="info-label">Respiration</label><input type="number" name="respiration" id="vital_respiration" value="<?= htmlspecialchars($latestVital['respiration'] ?? '') ?>"></div>
                <?php if ($vitalsHasSpo2): ?><div><label class="info-label">SPO2</label><input type="number" name="spo2" id="vital_spo2" value="<?= htmlspecialchars($latestVital['spo2'] ?? '') ?>"></div><?php endif; ?>
            </div>
            <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit" name="save_vitals" class="btn-save" style="float:none; margin-top:0;">Save Vitals</button>
                <button type="button" class="btn-save" style="float:none; margin-top:0; background:#6c757d;" onclick="resetVitalsForm()">Record Fresh Set</button>
            </div>
        </form>
    </div>

    <h3>Vital Signs History</h3>
    <table class="table-custom" style="margin-bottom: 30px;">
        <thead>
            <tr><th>BP</th><th>Temp</th><th>Pulse</th><th>SPO2</th><th>Weight</th><th>Timestamp</th></tr>
        </thead>
        <tbody>
            <?php while($v = $vitals->fetch_assoc()): ?>
            <tr onclick='loadVital(<?= htmlspecialchars(json_encode($v), ENT_QUOTES) ?>)' style="cursor:pointer;">
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

    <form method="post" id="clinicalForm">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: var(--primary-blue);">Comprehensive Clinical Examination</h3>
            <button type="button" onclick="clearForm()" class="btn-save" style="background:#6c757d; margin-top:0;">+ New Encounter</button>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
        
        <div class="clinical-grid">
            <div class="module-card"><h4>1. Presenting Complaints</h4><textarea name="presenting_complaint"><?= htmlspecialchars($encounter['presenting_complaint'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>2. HPC</h4><textarea name="hpc"><?= htmlspecialchars($encounter['hpc'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>3. Past Medical History</h4><textarea name="medical_history"><?= htmlspecialchars($encounter['medical_history'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>4. Past Surgical History</h4><textarea name="surgical_history"><?= htmlspecialchars($encounter['surgical_history'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>5. Family History</h4><textarea name="family_history"><?= htmlspecialchars($encounter['family_history'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>6. Drug History</h4><textarea name="drug_history"><?= htmlspecialchars($encounter['drug_history'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>7. Allergies</h4><textarea name="allergies"><?= htmlspecialchars($encounter['allergies'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>8. Social History</h4><textarea name="social_history"><?= htmlspecialchars($encounter['social_history'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>9. Review of Systems</h4><textarea name="review_systems"><?= htmlspecialchars($encounter['review_systems'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>10. Physical Examination</h4><textarea name="physical_exam"><?= htmlspecialchars($encounter['physical_exam'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>11. Diagnosis</h4><textarea name="diagnosis"><?= htmlspecialchars($encounter['diagnosis'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>12. Differential Diagnosis</h4><textarea name="differential_diagnosis"><?= htmlspecialchars($encounter['differential_diagnosis'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>13. Investigations</h4><textarea name="investigations"><?= htmlspecialchars($encounter['investigations'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>14. Management Plan</h4><textarea name="management_plan"><?= htmlspecialchars($encounter['management_plan'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>15. Prescription Instructions</h4><textarea name="prescription_instructions"><?= htmlspecialchars($encounter['prescription_instructions'] ?? '') ?></textarea></div>
            <div class="module-card"><h4>16. Doctor's Notes</h4><textarea name="doctor_notes"><?= htmlspecialchars($encounter['doctor_notes'] ?? '') ?></textarea></div>
        </div>
        <button type="submit" name="save_clinical" class="btn-save">Save Clinical Notes</button>
    </form>

    <h3 style="margin-top:60px;">Clinical History Archive (Click to Review)</h3>
    <table class="table-custom">
        <thead><tr><th>Date</th><th>Diagnosis</th><th>Notes Snippet</th></tr></thead>
        <tbody>
            <?php 
            $clinical_history->data_seek(0); 
            while($h = $clinical_history->fetch_assoc()): 
            ?>
            <tr onclick="loadEncounter(<?= htmlspecialchars(json_encode($h)) ?>)" style="cursor:pointer;" onmouseover="this.style.background='#f0f4f8'" onmouseout="this.style.background='transparent'">
                <td><strong><?= date('d M Y', strtotime($h['created_at'])) ?></strong></td>
                <td><?= htmlspecialchars($h['diagnosis'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars(substr($h['doctor_notes'], 0, 80)) ?>...</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
        <div style="clear:both; margin-top:50px; border-top: 2px solid #eee; padding-top:20px;">
            <h3 style="color: #e67e22;">Book Next Appointment</h3>
            <form method="post" style="display:flex; gap:10px; align-items: flex-end; background:#fff9f0; padding:20px; border-radius:8px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div style="flex:1;">
                    <label class="info-label">Follow-up Date</label>
                    <input type="date" name="appointment_date" required style="width:100%; padding:10px;">
                </div>
                <div style="flex:1;">
                    <label class="info-label">Time</label>
                    <input type="time" name="appointment_time" required style="width:100%; padding:10px;">
                </div>
                <div style="flex:2;">
                    <label class="info-label">Reason</label>
                    <input type="text" name="reason" placeholder="e.g. Lab Review" style="width:100%; padding:10px;">
                </div>
                <button type="submit" name="book_appointment" style="background:#e67e22; color:white; height:42px; border:none; padding: 0 20px; border-radius:5px; cursor:pointer;">Book Appointment</button>
            </form>
        </div>
    </div>

<script>
function loadEncounter(data) {
    document.getElementById('clinical').scrollIntoView({behavior: 'smooth'});
    for (const key in data) {
        const field = document.querySelector(`[name="${key}"]`);
        if (field) field.value = data[key];
    }
}
function loadVital(data) {
    document.getElementById('vital_id').value = data.id || '';
    document.getElementById('vital_temperature').value = data.temperature || '';
    document.getElementById('vital_bp').value = data.bp || '';
    document.getElementById('vital_weight').value = data.weight || '';
    document.getElementById('vital_pulse').value = data.pulse || '';
    document.getElementById('vital_respiration').value = data.respiration || '';
    const spo2Field = document.getElementById('vital_spo2');
    if (spo2Field) spo2Field.value = data.spo2 || '';
    document.getElementById('clinical').scrollIntoView({behavior: 'smooth'});
}
function resetVitalsForm() {
    document.getElementById('vital_id').value = '';
    document.getElementById('vital_temperature').value = '';
    document.getElementById('vital_bp').value = '';
    document.getElementById('vital_weight').value = '';
    document.getElementById('vital_pulse').value = '';
    document.getElementById('vital_respiration').value = '';
    const spo2Field = document.getElementById('vital_spo2');
    if (spo2Field) spo2Field.value = '';
}
function clearForm() {
    document.getElementById("clinicalForm").reset();
    // Also clear textareas specifically if reset doesn't catch them
    document.querySelectorAll("textarea").forEach(t => t.value = "");
}
</script>

    <div id="services" class="card" style="display:none;">
        <h3>Add Service / Procedure</h3>
        <form method="post" style="margin-bottom:30px; background:#f4f7f6; padding:20px; border-radius:8px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:15px;">
                <div>
                    <label class="info-label">Service Description</label>
                    <select name="service_id" onchange="updatePrice(this, 'svc_p')" required style="width:100%; padding:10px;">
                        <option value="">Search Service...</option>
                        <?php $all_services->data_seek(0); while($s=$all_services->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>" data-price="<?= $s['price'] ?>"><?= htmlspecialchars($s['service_name']) ?> (<?= strtoupper(htmlspecialchars($s['category'])) ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="info-label">Fee (KSH)</label>
                    <input type="number" id="svc_p" name="price" step="0.01" style="width:100%; padding:10px;">
                </div>
                <button type="submit" name="add_service" style="background:var(--primary-blue); color:white; border:none; border-radius:5px; margin-top:22px;">Bill Item</button>
            </div>
        </form>

        <div class="lab-order-box">
            <h4>Request Laboratory Test</h4>
            <form method="post" style="display:grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap:10px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <select name="service_id" required style="padding:10px;">
                    <option value="">Select Lab Test...</option>
                    <?php $all_services->data_seek(0); while($s=$all_services->fetch_assoc()): if($s['category'] == 'lab'): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['service_name']) ?></option>
                    <?php endif; endwhile; ?>
                </select>
                <input type="number" name="price" placeholder="Price" step="0.01" style="padding:10px;">
                <input type="text" name="lab_instructions" placeholder="Notes..." style="padding:10px;">
                <button type="submit" name="add_lab_request" style="background:#2980b9; color:white; border:none; padding:10px; border-radius:5px;">Request Lab</button>
            </form>
        </div>

        <table class="table-custom">
            <tr><th>Service Name</th><th>Category</th><th>Cost</th><th>Action</th></tr>
            <?php $patient_services->data_seek(0); while($s=$patient_services->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($s['service_name']) ?></td>
                <td><span class="badge-info"><?= htmlspecialchars($s['svc_category']) ?></span></td>
                <td>KSH <?= number_format($s['price'], 2) ?></td>
                <td><a href="?id=<?= $patient_id ?>&delete_item=1&item_id=<?= $s['id'] ?>&type=service" style="color:red;">&times; Remove</a></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div id="prescriptions" class="card" style="display:none;">
        <h3>Prescribe from Pharmacy Stock</h3>
        <form method="post" style="margin-bottom:30px; background: #f0f4ff; padding:20px; border-radius:8px; border-left: 5px solid #0056b3;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div style="display:grid; grid-template-columns: 2fr 1fr 1fr 2fr 1fr; gap:10px;">
                <div>
                    <label class="info-label">Available Stock</label>
                    <select name="medicine_id" onchange="updatePrice(this, 'stock_p')" required style="width:100%; padding:10px;">
                        <option value="">-- Select Drug --</option>
                        <?php $stock->data_seek(0); while($item = $stock->fetch_assoc()): ?>
                            <option value="<?= $item['id'] ?>" data-price="<?= $item['selling_price'] ?>"><?= htmlspecialchars($item['drug_name']) ?> (Avail: <?= $item['quantity'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="info-label">Qty</label>
                    <input type="number" name="quantity" value="1" style="width:100%; padding:10px;">
                </div>
                <div>
                    <label class="info-label">Price Override</label>
                    <input type="number" id="stock_p" name="selling_price" step="0.01" style="width:100%; padding:10px;">
                </div>
                <div>
                    <label class="info-label">Dosage Instructions</label>
                    <input type="text" name="dosage_instructions" placeholder="1x3 for 5 days" style="width:100%; padding:10px;">
                </div>
                <button type="submit" name="add_prescription_stock" style="background:#2ecc71; color:white; border:none; border-radius:5px; margin-top:22px; cursor:pointer;">Prescribe</button>
            </div>
        </form>

        <h3>Medication History</h3>
        <table class="table-custom">
            <tr><th>Drug Name</th><th>Quantity</th><th>Unit Price</th><th>Total</th><th>Date</th><th>Action</th></tr>
            <?php $prescriptions->data_seek(0); while($p=$prescriptions->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['drug_name']) ?></strong></td>
                <td><?= htmlspecialchars($p['quantity']) ?></td>
                <td>KSH <?= number_format($p['invoice_id'], 2) ?></td>
                <td>KSH <?= number_format($p['quantity'] * $p['invoice_id'], 2) ?></td>
                <td><?= date('d/m/y', strtotime($p['created_at'])) ?></td>
                <td>
                    <a href="?id=<?= $patient_id ?>&delete_item=1&item_id=<?= $p['id'] ?>&type=prescription" 
                       style="color:red;" onclick="return confirm('Remove this medication?')">&times; Remove</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

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
                <h4>Process New Payment</h4>
                <p style="color:#666; font-size:13px; margin-top:-8px;">Supports partial settlement. The remaining balance stays outstanding until fully cleared.</p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <label class="info-label">Amount to Pay</label>
                    <input type="number" name="amount" value="<?= $amountToPayNow ?>" step="0.01" style="width:100%; padding:10px; margin-bottom:15px;">
                    <label class="info-label">Payment Mode</label>
                    <select name="method" style="width:100%; padding:10px; margin-bottom:15px;">
                        <option value="Cash">Cash Payment</option>
                        <option value="Mpesa">M-Pesa Mobile Money</option>
                        <option value="Bank">Bank Payment</option>
                        <option value="Wire Transfer">Wire Transfer</option>
                    </select>
                    <button type="submit" name="register_payment" style="width:100%; background:#2e7d32; color:white; border:none; height:45px; border-radius:5px; cursor:pointer; font-weight:bold;">Finalize Payment</button>
                </form>
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
                                <a href="/hospital_system/pharmacy/view_invoice.php?id=<?= $inv['id'] ?>" target="_blank">View</a> | 
                                <a href="/hospital_system/pharmacy/view_invoice.php?id=<?= $inv['id'] ?>&print=1" target="_blank">Print</a>
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
    document.getElementById(targetInputId).value = price;
}


// Keep the active tab after reload if specified in URL
const urlParams = new URLSearchParams(window.location.search);
const activeTab = urlParams.get('tab');
if(activeTab) showTab(activeTab);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
