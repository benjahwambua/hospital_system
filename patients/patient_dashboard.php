<?php
// 1. INITIALIZATION & SESSIONS (Must be first)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_once __DIR__ . '/../config/mpesa.php';
require_login();
$canPatientEdit = can_module_action($conn, 'front_desk', 'edit');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$patient_id = intval($_GET['id'] ?? 0);
$appointment_id = intval($_GET['appointment_id'] ?? 0);
$activeAppointment = null;
$activeVisitId = 0;
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

if ($patient_id > 0 && $appointment_id > 0) {
    $apptStmt = $conn->prepare("SELECT a.*, u.full_name AS doctor_name FROM appointments a LEFT JOIN users u ON u.id = a.doctor_id WHERE a.id = ? AND a.patient_id = ? LIMIT 1");
    if ($apptStmt) {
        $apptStmt->bind_param('ii', $appointment_id, $patient_id);
        $apptStmt->execute();
        $activeAppointment = $apptStmt->get_result()->fetch_assoc();
        $apptStmt->close();
    }
    if ($activeAppointment && hms_visits_available($conn)) {
        $hasAppointmentVisit = false;
        $cols = $conn->query("SHOW COLUMNS FROM appointments");
        if ($cols) while ($col = $cols->fetch_assoc()) {
            if (($col['Field'] ?? '') === 'visit_id') { $hasAppointmentVisit = true; break; }
        }
        if ($hasAppointmentVisit && !empty($activeAppointment['visit_id'])) {
            $activeVisitId = (int)$activeAppointment['visit_id'];
        } else {
            $activeVisitId = get_or_create_current_visit($conn, $patient_id, 'Outpatient', $activeAppointment['clinic_category'] ?? 'General', (int)($activeAppointment['doctor_id'] ?? 0));
            if ($hasAppointmentVisit && $activeVisitId > 0) {
                $linkStmt = $conn->prepare("UPDATE appointments SET visit_id = ? WHERE id = ? AND patient_id = ?");
                if ($linkStmt) { $linkStmt->bind_param('iii', $activeVisitId, $appointment_id, $patient_id); $linkStmt->execute(); $linkStmt->close(); }
            }
        }
    }
}

// If the dashboard was opened directly (without an appointment link), resolve the
// patient's current open encounter before loading clinical, service, prescription,
// and billing data. This keeps the dashboard anchored to one encounter.
if ($patient_id > 0 && $activeVisitId <= 0 && hms_visits_available($conn)) {
    $currentVisitStmt = $conn->prepare("SELECT id FROM visits WHERE patient_id=? AND status IN ('Open','In Progress') ORDER BY CASE WHEN visit_date=CURDATE() THEN 0 ELSE 1 END, visit_date DESC, id DESC LIMIT 1");
    if ($currentVisitStmt) {
        $currentVisitStmt->bind_param('i', $patient_id);
        $currentVisitStmt->execute();
        $currentVisitRow = $currentVisitStmt->get_result()->fetch_assoc();
        $currentVisitStmt->close();
        if ($currentVisitRow) {
            $activeVisitId = (int)$currentVisitRow['id'];
        }
    }
}

// Visit-linked clinical records are preferred whenever the modern visit columns exist.
$hasVisitVitals = false;
$hasVisitServices = false;
$hasVisitInvoices = false;
$hasVisitPrescriptions = false;
if ($patient_id > 0) {
    $q = $conn->query("SHOW COLUMNS FROM vitals LIKE 'visit_id'");
    $hasVisitVitals = $q && $q->num_rows > 0;
    $q = $conn->query("SHOW COLUMNS FROM patient_services LIKE 'visit_id'");
    $hasVisitServices = $q && $q->num_rows > 0;
    $hasVisitInvoices = invoice_column_exists($conn, 'visit_id');
    $hasVisitPrescriptions = ensure_prescription_visit_column($conn);
}

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
        if (!can_module_action($conn, 'clinical', 'create')) { throw new Exception('You do not have permission to book appointments.'); }
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


    // Handle vitals retake / edit
    if (isset($_POST['save_vitals'])) {
        if (!can_module_action($conn, 'clinical', 'create')) { throw new Exception('You do not have permission to record vitals.'); }
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
            $visitId = $activeVisitId > 0 ? $activeVisitId : get_or_create_current_visit($conn, $patient_id, 'Outpatient', 'General', (int)($patient['doctor_id'] ?? 0));
            if ($vitalsHasSpo2 && $hasVisitVitals && $visitId > 0) {
                $stmt = $conn->prepare("INSERT INTO vitals (temperature, bp, weight, pulse, respiration, spo2, patient_id, visit_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('ssssssii', $temperature, $bp, $weight, $pulse, $respiration, $spo2, $patient_id, $visitId);
            } elseif ($hasVisitVitals && $visitId > 0) {
                $stmt = $conn->prepare("INSERT INTO vitals (temperature, bp, weight, pulse, respiration, patient_id, visit_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('ssssssii', $temperature, $bp, $weight, $pulse, $respiration, $patient_id, $visitId);
            } elseif ($vitalsHasSpo2) {
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


    // Handle Prescription with Price Override
    if(isset($_POST['add_prescription_stock'])) {
        if (!can_module_action($conn, 'clinical', 'create')) { throw new Exception('You do not have permission to prescribe medicines.'); }
        $medicine_id = intval($_POST['medicine_id']);
        $qty = intval($_POST['quantity']);
        $price_override = floatval($_POST['selling_price']); 
        $instructions = $_POST['dosage_instructions'] ?? '';

        $stock = $conn->query("SELECT drug_name, selling_price FROM pharmacy_stock WHERE id = $medicine_id")->fetch_assoc();
        $unit_price = max($price_override, 0);
        if ($unit_price <= 0 && $stock) $unit_price = (float)$stock['selling_price'];
        $invoice_total = $qty * $unit_price;

        $visitId = $activeVisitId > 0 ? $activeVisitId : get_or_create_current_visit($conn, $patient_id, 'Outpatient', 'General', (int)($patient['doctor_id'] ?? 0));
        $invoiceLink = 0;
        if ($hasVisitPrescriptions && $visitId > 0) {
            $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, medicine_id, quantity, unit_price, invoice_id, visit_id, frequency, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiidiis", $patient_id, $medicine_id, $qty, $unit_price, $invoiceLink, $visitId, $instructions);
        } else {
            $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, medicine_id, quantity, unit_price, invoice_id, frequency, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiidis", $patient_id, $medicine_id, $qty, $unit_price, $invoiceLink, $instructions);
        }
        $stmt->execute();
        $prescription_id = $stmt->insert_id;
        $stmt->close();

        // Send every new prescription directly to the Pharmacy Dispensing Queue.
        // Pharmacy will deduct stock only when the item is actually dispensed.
        $queueTable = $conn->query("SHOW TABLES LIKE 'pharmacy_queue'");
        if ($queueTable && $queueTable->num_rows > 0) {
            $queueVisitColumn = $conn->query("SHOW COLUMNS FROM pharmacy_queue LIKE 'visit_id'");
            $queueExists = $conn->prepare("SELECT id FROM pharmacy_queue WHERE prescription_id = ? AND status = 'pending' LIMIT 1");
            if ($queueExists) {
                $queueExists->bind_param('i', $prescription_id);
                $queueExists->execute();
                $alreadyQueued = $queueExists->get_result()->fetch_assoc();
                $queueExists->close();

                if (!$alreadyQueued) {
                    if ($queueVisitColumn && $queueVisitColumn->num_rows > 0 && $visitId > 0) {
                        $queueStmt = $conn->prepare("INSERT INTO pharmacy_queue (prescription_id, patient_id, medicine_id, quantity, status, visit_id, created_at) VALUES (?, ?, ?, ?, 'pending', ?, NOW())");
                        if ($queueStmt) {
                            $queueStmt->bind_param('iiiii', $prescription_id, $patient_id, $medicine_id, $qty, $visitId);
                            if (!$queueStmt->execute()) {
                                $queueError = $queueStmt->error;
                                $queueStmt->close();
                                throw new Exception('Prescription saved, but it could not be sent to Pharmacy: ' . $queueError);
                            }
                            $queueStmt->close();
                        } else {
                            throw new Exception('Prescription saved, but the Pharmacy queue could not be prepared.');
                        }
                    } else {
                        $queueStmt = $conn->prepare("INSERT INTO pharmacy_queue (prescription_id, patient_id, medicine_id, quantity, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
                        if ($queueStmt) {
                            $queueStmt->bind_param('iiii', $prescription_id, $patient_id, $medicine_id, $qty);
                            if (!$queueStmt->execute()) {
                                $queueError = $queueStmt->error;
                                $queueStmt->close();
                                throw new Exception('Prescription saved, but it could not be sent to Pharmacy: ' . $queueError);
                            }
                            $queueStmt->close();
                        } else {
                            throw new Exception('Prescription saved, but the Pharmacy queue could not be prepared.');
                        }
                    }
                }
            }
        }

        if ($invoice_total > 0) {
            $invoice_id = get_or_create_invoice($conn, $patient_id, null, $visitId);
            add_invoice_item(
                $conn,
                $invoice_id,
                'Medication: ' . ($stock['drug_name'] ?? 'Prescription'),
                $qty,
                $unit_price,
                'pharmacy',
                $medicine_id
            );

            $updatePrescription = $conn->prepare("UPDATE prescriptions SET invoice_id = ? WHERE id = ?");
            if ($updatePrescription) {
                $updatePrescription->bind_param('ii', $invoice_id, $prescription_id);
                $updatePrescription->execute();
                $updatePrescription->close();
            }
        }

        header("Location: patient_dashboard.php?id=$patient_id&tab=prescriptions&success=1");
        exit;
    }

    // Handle Service/Billing Item Add
    if(isset($_POST['add_service'])){
        if (!can_module_action($conn, 'clinical', 'create')) { throw new Exception('You do not have permission to add services.'); }
        $service_id=intval($_POST['service_id']);
        $price=floatval($_POST['price']);
        if($service_id>0 && $price>=0){
            $serviceStmt=$conn->prepare("SELECT service_name, category FROM services_master WHERE id=? AND active=1 LIMIT 1");
            $serviceStmt->bind_param('i',$service_id);
            $serviceStmt->execute();
            $service=$serviceStmt->get_result()->fetch_assoc();
            $serviceStmt->close();

            if(!$service) throw new Exception('Selected service is not active or does not exist.');

            $visitId = $activeVisitId > 0 ? $activeVisitId : get_or_create_current_visit($conn, $patient_id, 'Outpatient', $service['category'] ?: 'General', (int)($patient['doctor_id'] ?? 0));
            if ($hasVisitServices && $visitId > 0) {
                $stmt=$conn->prepare("INSERT INTO patient_services (patient_id, service_id, category, price, visit_id, created_at, status) VALUES (?, ?, ?, ?, ?, NOW(), 'Completed')");
                $stmt->bind_param("iisdi",$patient_id,$service_id,$service['category'],$price,$visitId);
            } else {
                $stmt=$conn->prepare("INSERT INTO patient_services (patient_id, service_id, category, price, created_at, status) VALUES (?, ?, ?, ?, NOW(), 'Completed')");
                $stmt->bind_param("iisd",$patient_id,$service_id,$service['category'],$price);
            }
            $stmt->execute();
            $stmt->close();

            $invoice_id=get_or_create_invoice($conn,$patient_id,null,$visitId);
            add_invoice_item($conn,$invoice_id,'Service: '.$service['service_name'],1,$price,'service',$service_id);

            header("Location: patient_dashboard.php?id=$patient_id&tab=services&added=1");
            exit;
        }
    }

    // Deletions are POST-only and protected by the dashboard CSRF token.
    if (isset($_POST['delete_item'])) {
        $item_id = (int)($_POST['item_id'] ?? 0);
        $type = $_POST['type'] ?? '';
        if ($item_id > 0 && in_array($type, ['service', 'prescription'], true)) {
            $stmt = $conn->prepare($type === 'service'
                ? "DELETE FROM patient_services WHERE id = ? AND patient_id = ?"
                : "DELETE FROM prescriptions WHERE id = ? AND patient_id = ?");
            if ($stmt) { $stmt->bind_param('ii', $item_id, $patient_id); $stmt->execute(); $stmt->close(); }
        }
        header("Location: patient_dashboard.php?id=$patient_id&tab=billing&deleted=1");
        exit;
    }

    // Existing Lab Request Handler
    if(isset($_POST['add_lab_request'])){
        if (!can_module_action($conn, 'clinical', 'create')) { throw new Exception('You do not have permission to order laboratory services.'); }
        $service_id=intval($_POST['service_id']);
        $price=floatval($_POST['price']);
        $instructions=$_POST['lab_instructions'] ?? '';
        if($service_id>0 && $price>=0){
            $stmt=$conn->prepare("SELECT service_name FROM services_master WHERE id=? AND active=1 AND category='lab' LIMIT 1");
            $stmt->bind_param('i',$service_id);
            $stmt->execute();
            $labService=$stmt->get_result()->fetch_assoc();
            $stmt->close();
            if(!$labService) throw new Exception('Selected laboratory service is invalid.');

            $visitId = $activeVisitId > 0 ? $activeVisitId : get_or_create_current_visit($conn, $patient_id, 'Outpatient', 'Laboratory', (int)($patient['doctor_id'] ?? 0));
            if ($hasVisitServices && $visitId > 0) {
                $stmt=$conn->prepare("INSERT INTO patient_services (patient_id,service_id,category,price,doctor_notes,visit_id,created_at,status) VALUES (?, ?, 'lab', ?, ?, ?, NOW(), 'Pending')");
                $stmt->bind_param("iidsi",$patient_id,$service_id,$price,$instructions,$visitId);
            } else {
                $stmt=$conn->prepare("INSERT INTO patient_services (patient_id,service_id,category,price,doctor_notes,created_at,status) VALUES (?, ?, 'lab', ?, ?, NOW(), 'Pending')");
                $stmt->bind_param("iids",$patient_id,$service_id,$price,$instructions);
            }
            $stmt->execute();
            $stmt->close();

            $invoice_id=get_or_create_invoice($conn,$patient_id,null,$visitId);
            add_invoice_item($conn,$invoice_id,'Lab: '.$labService['service_name'],1,$price,'lab',$service_id);

            header("Location: patient_dashboard.php?id=$patient_id&tab=services&lab_success=1");
            exit;
        }
    }

    // Handle External Referral
    if(isset($_POST['add_referral'])){
        if (!can_module_action($conn, 'clinical', 'create')) { throw new Exception('You do not have permission to create referrals.'); }
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

    // Existing Save Clinical Handler
    if(isset($_POST['save_clinical'])){
        $params = [
            $patient_id, $_POST['presenting_complaint'] ?? '', $_POST['hpc'] ?? '',
            $_POST['medical_history'] ?? '', $_POST['surgical_history'] ?? '',
            $_POST['family_history'] ?? '', $_POST['drug_history'] ?? '',
            $_POST['allergies'] ?? '', $_POST['social_history'] ?? '',
            $_POST['review_systems'] ?? '', $_POST['physical_exam'] ?? '',
            $_POST['diagnosis'] ?? '', $_POST['differential_diagnosis'] ?? '',
            $_POST['investigations'] ?? '', $_POST['management_plan'] ?? '',
            $_POST['prescription_instructions'] ?? '', $_POST['doctor_notes'] ?? ''
        ];
        $visitId = $activeVisitId > 0 ? $activeVisitId : get_or_create_current_visit($conn, $patient_id, 'Outpatient', 'General', (int)($patient['doctor_id'] ?? 0));
        $hasEncounterVisit = ensure_encounter_visit_column($conn);
        if ($hasEncounterVisit && $visitId > 0) {
            $stmt=$conn->prepare("INSERT INTO encounters (patient_id,visit_id,presenting_complaint,hpc,medical_history,surgical_history,family_history,drug_history,allergies,social_history,review_systems,physical_exam,diagnosis,differential_diagnosis,investigations,management_plan,prescription_instructions,doctor_notes,created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if(!$stmt) throw new Exception('Unable to prepare clinical record: '.$conn->error);
            $paramsWithVisit = [$patient_id, $visitId, ...array_slice($params, 1)];
            $stmt->bind_param("iissssssssssssssss", ...$paramsWithVisit);
        } else {
            $stmt=$conn->prepare("INSERT INTO encounters (patient_id,presenting_complaint,hpc,medical_history,surgical_history,family_history,drug_history,allergies,social_history,review_systems,physical_exam,diagnosis,differential_diagnosis,investigations,management_plan,prescription_instructions,doctor_notes,created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if(!$stmt) throw new Exception('Unable to prepare clinical record: '.$conn->error);
            $stmt->bind_param("isssssssssssssssss", ...$params);
        }
        if(!$stmt) throw new Exception('Unable to prepare clinical record: '.$conn->error);
        if(!$stmt->execute()){ $error=$stmt->error; $stmt->close(); throw new Exception('Unable to save clinical record: '.$error); }
        $stmt->close();
        header("Location: patient_dashboard.php?id=$patient_id&tab=clinical&success=1");
        exit;
    }



    // Patient Dashboard is intentionally read-only for payments.
    // All collections are handled through the Central Cashier.
    // NEW: Clinical History & Invoice Queries
    $clinical_history = ($activeVisitId > 0 && ensure_encounter_visit_column($conn))
        ? $conn->query("SELECT * FROM encounters WHERE patient_id=" . (int)$patient_id . " AND visit_id=" . (int)$activeVisitId . " ORDER BY created_at DESC")
        : $conn->query("SELECT * FROM encounters WHERE patient_id=" . (int)$patient_id . " ORDER BY created_at DESC");
    $invoices = ($activeVisitId > 0 && $hasVisitInvoices)
        ? $conn->query("SELECT * FROM invoices WHERE patient_id=" . (int)$patient_id . " AND visit_id=" . (int)$activeVisitId . " ORDER BY created_at DESC")
        : $conn->query("SELECT * FROM invoices WHERE patient_id=" . (int)$patient_id . " ORDER BY created_at DESC");
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
    $vitals = ($activeVisitId > 0 && $hasVisitVitals)
        ? $conn->query("SELECT * FROM vitals WHERE patient_id = " . (int)$patient_id . " AND visit_id = " . (int)$activeVisitId . " ORDER BY created_at DESC, id DESC")
        : $conn->query("SELECT * FROM vitals WHERE patient_id = " . (int)$patient_id . " ORDER BY created_at DESC, id DESC");
    if (!$vitals) {
        $vitals = $conn->query("SELECT * FROM vitals WHERE patient_id = " . (int)$patient_id . " ORDER BY id DESC");
    }
    if ($vitals) {
        $vitals->data_seek(0);
        $latestVital = $vitals->fetch_assoc();
        $vitals->data_seek(0);
    }

    $encounter = null;
    $encounterRes = ($activeVisitId > 0 && ensure_encounter_visit_column($conn))
        ? $conn->query("SELECT * FROM encounters WHERE patient_id = " . (int)$patient_id . " AND visit_id = " . (int)$activeVisitId . " ORDER BY created_at DESC, id DESC LIMIT 1")
        : $conn->query("SELECT * FROM encounters WHERE patient_id = " . (int)$patient_id . " ORDER BY created_at DESC, id DESC LIMIT 1");
    if ($encounterRes) {
        $encounter = $encounterRes->fetch_assoc();
    }

    $all_services = $conn->query("SELECT id, category, service_name, price, active FROM services_master WHERE active = 1 ORDER BY category, service_name");
    if (!$all_services) {
        $all_services = $conn->query("SELECT id, category, service_name, price, active FROM services_master ORDER BY category, service_name");
    }

    $patient_services = ($activeVisitId > 0 && $hasVisitServices)
        ? $conn->query("SELECT ps.*, sm.service_name, sm.category AS svc_category FROM patient_services ps LEFT JOIN services_master sm ON sm.id = ps.service_id WHERE ps.patient_id = " . (int)$patient_id . " AND ps.visit_id = " . (int)$activeVisitId . " ORDER BY ps.created_at DESC, ps.id DESC")
        : $conn->query("SELECT ps.*, sm.service_name, sm.category AS svc_category FROM patient_services ps LEFT JOIN services_master sm ON sm.id = ps.service_id WHERE ps.patient_id = " . (int)$patient_id . " ORDER BY ps.created_at DESC, ps.id DESC");
    $prescriptions = ($activeVisitId > 0 && $hasVisitPrescriptions)
        ? $conn->query("SELECT pr.*, ps.drug_name, ps.selling_price AS stock_selling_price FROM prescriptions pr LEFT JOIN pharmacy_stock ps ON ps.id = pr.medicine_id WHERE pr.patient_id = " . (int)$patient_id . " AND pr.visit_id = " . (int)$activeVisitId . " ORDER BY pr.created_at DESC, pr.id DESC")
        : $conn->query("SELECT pr.*, ps.drug_name, ps.selling_price AS stock_selling_price FROM prescriptions pr LEFT JOIN pharmacy_stock ps ON ps.id = pr.medicine_id WHERE pr.patient_id = " . (int)$patient_id . " ORDER BY pr.created_at DESC, pr.id DESC");
    if (!$prescriptions) {
        $prescriptions = $conn->query("SELECT pr.*, ps.drug_name, ps.selling_price AS stock_selling_price FROM prescriptions pr LEFT JOIN pharmacy_stock ps ON ps.id = pr.medicine_id ORDER BY pr.created_at DESC, pr.id DESC");
    }
    $stock = $conn->query("SELECT id, drug_name, quantity, selling_price FROM pharmacy_stock WHERE quantity > 0 ORDER BY drug_name");


    // Maternity data is scoped strictly to this patient dashboard.
    $maternityRecord = null;
    $maternityVisits = [];
    $maternityDeliveries = [];
    $maternityAdmission = null;
    $matStmt = $conn->prepare("SELECT m.*, p.full_name, p.patient_number FROM maternity m JOIN patients p ON p.id=m.patient_id WHERE m.patient_id=? ORDER BY m.id DESC LIMIT 1");
    if ($matStmt) {
        $matStmt->bind_param('i', $patient_id); $matStmt->execute(); $maternityRecord=$matStmt->get_result()->fetch_assoc(); $matStmt->close();
    }
    if ($maternityRecord) {
        $mid=(int)$maternityRecord['id'];
        $mv=$conn->prepare("SELECT * FROM maternity_visits WHERE maternity_id=? ORDER BY created_at DESC LIMIT 10");
        if ($mv) { $mv->bind_param('i',$mid); $mv->execute(); $maternityVisits=$mv->get_result()->fetch_all(MYSQLI_ASSOC); $mv->close(); }
        $md=$conn->prepare("SELECT d.*, b.gender AS baby_gender, b.weight AS baby_weight, b.apgar, b.alive FROM maternity_delivery d LEFT JOIN maternity_baby b ON b.maternity_id=d.maternity_id AND b.created_at>=d.created_at WHERE d.maternity_id=? ORDER BY d.created_at DESC LIMIT 10");
        if ($md) { $md->bind_param('i',$mid); $md->execute(); $maternityDeliveries=$md->get_result()->fetch_all(MYSQLI_ASSOC); $md->close(); }
        $ma=$conn->prepare("SELECT ma.*, a.admission_id, a.visit_id, a.admission_date, a.ward_name, a.bed_number, a.status AS clinical_status FROM maternity_admissions ma LEFT JOIN admissions a ON a.id=ma.admission_id WHERE ma.patient_id=? ORDER BY ma.id DESC LIMIT 1");
        if ($ma) { $ma->bind_param('i',$patient_id); $ma->execute(); $maternityAdmission=$ma->get_result()->fetch_assoc(); $ma->close(); }
    }

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

// Billing line items for the read-only patient billing history.
    // Charges are read from invoice_items because this is the authoritative
    // record of services, investigations and medicines billed to the patient.
    $billingItems = null;
    $billingItemQtyColumn = invoice_item_column_exists($conn, 'qty') ? 'qty' : 'quantity';
    $billingItemPriceColumn = invoice_item_column_exists($conn, 'unit_price') ? 'unit_price' : 'price';
    $billingItemTypeSelect = invoice_item_column_exists($conn, 'item_type')
        ? "ii.item_type"
        : "NULL AS item_type";

    $billingVisitCondition = ($activeVisitId > 0 && $hasVisitInvoices)
        ? " AND i.visit_id = " . (int)$activeVisitId
        : "";

    $billingItems = $conn->query("
        SELECT
            ii.id,
            ii.invoice_id,
            ii.description,
            ii.{$billingItemQtyColumn} AS quantity,
            ii.{$billingItemPriceColumn} AS unit_price,
            ii.total,
            {$billingItemTypeSelect},
            i.created_at AS invoice_date,
            i.status AS invoice_status,
            i.visit_id,
            v.visit_number
        FROM invoice_items ii
        INNER JOIN invoices i ON i.id = ii.invoice_id
        LEFT JOIN visits v ON v.id = i.visit_id
        WHERE i.patient_id = " . (int)$patient_id . $billingVisitCondition . "
          AND LOWER(COALESCE(i.status, '')) NOT IN ('cancelled', 'canceled', 'void')
        ORDER BY i.created_at DESC, ii.id DESC
    ");

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
        WHERE i.patient_id = " . (int)$patient_id . ($activeVisitId > 0 && $hasVisitInvoices ? " AND i.visit_id = " . (int)$activeVisitId : "") . "
        ORDER BY i.id ASC
    ");

    if ($invoiceTotalsRes) {
        while ($invoiceRow = $invoiceTotalsRes->fetch_assoc()) {
            $total_charges += (float)($invoiceRow['invoice_total'] ?? 0);
        }
    }

    // Payments are invoice-specific, matching billing/view_invoice.php.
    $total_paid = 0.0;
    $paymentVisitCondition = ($activeVisitId > 0 && $hasVisitInvoices) ? " AND i.visit_id = ?" : "";
    $paidStmt = $conn->prepare("
        SELECT COALESCE(SUM(p.amount), 0) AS total_paid
        FROM payments p
        INNER JOIN invoices i ON i.id = p.invoice_id
        WHERE i.patient_id = ?" . $paymentVisitCondition . "
    ");

    if ($paidStmt) {
        if ($activeVisitId > 0 && $hasVisitInvoices) {
            $paidStmt->bind_param('ii', $patient_id, $activeVisitId);
        } else {
            $paidStmt->bind_param('i', $patient_id);
        }
        $paidStmt->execute();
        $paidRes = $paidStmt->get_result();

        if ($paidRes) {
            $paidRow = $paidRes->fetch_assoc();
            $total_paid = (float)($paidRow['total_paid'] ?? 0);
        }

        $paidStmt->close();
    }

    // Prevent negative balances.
    $balance_due = max($total_charges - $total_paid, 0.0);

    $insuranceCovered = 0.0;
    $amountToPayNow = $balance_due;
    $currentPayerLabel = 'Cash / Self Pay';
    $currentCopayEstimate = 0.0;

    // Read the optional financial-account data defensively.
    $financialAccountTable = $conn->query("SHOW TABLES LIKE 'patient_financial_accounts'");
    if ($financialAccountTable && $financialAccountTable->num_rows > 0) {
        $financialAccountStmt = $conn->prepare("
            SELECT pfa.*, p.payer_name
            FROM patient_financial_accounts pfa
            LEFT JOIN payers p ON pfa.current_payer_id = p.id
            WHERE pfa.patient_id = ?
            LIMIT 1
        ");

        if ($financialAccountStmt) {
            $financialAccountStmt->bind_param('i', $patient_id);
            $financialAccountStmt->execute();
            $financialAccount = $financialAccountStmt->get_result()->fetch_assoc();
            $financialAccountStmt->close();

            if ($financialAccount) {
                $insuranceCovered = min(
                    $balance_due,
                    (float)($financialAccount['total_claims_outstanding'] ?? 0)
                );
                $currentCopayEstimate = (float)($financialAccount['total_copay_due'] ?? 0);
                $amountToPayNow = max($balance_due - $insuranceCovered, 0.0);

                if (!empty($financialAccount['payer_name'])) {
                    $currentPayerLabel = $financialAccount['payer_name'];
                } elseif (!empty($financialAccount['account_class'])) {
                    $currentPayerLabel = $financialAccount['account_class'];
                }
            }
        }
    }

    }

// 4. BEGIN OUTPUT
// ==============================================================================
$activeVisit = null;
$activeVisitRes = $conn->prepare("SELECT id, visit_number, visit_type, clinic_category, visit_date, visit_time, status FROM visits WHERE id=? AND patient_id=? LIMIT 1");
if ($activeVisitRes) { $activeVisitRes->bind_param('ii', $activeVisitId, $patient_id); $activeVisitRes->execute(); $activeVisit = $activeVisitRes->get_result()->fetch_assoc(); $activeVisitRes->close(); }

// Patient Dashboard command-centre context.
// Keep this summary read-only; specialist modules remain responsible for transactions.
$currentAdmission = null;
$admissionStmt = $conn->prepare("SELECT id, admission_date, ward_name, bed_number, status, attending_doctor FROM admissions WHERE patient_id=? AND status='Admitted' ORDER BY id DESC LIMIT 1");
if ($admissionStmt) {
    $admissionStmt->bind_param('i', $patient_id);
    $admissionStmt->execute();
    $currentAdmission = $admissionStmt->get_result()->fetch_assoc();
    $admissionStmt->close();
}

$recentAppointment = $activeAppointment;
if (!$recentAppointment) {
    $appointmentStmt = $conn->prepare("SELECT a.*, u.full_name AS doctor_name FROM appointments a LEFT JOIN users u ON u.id=a.doctor_id WHERE a.patient_id=? AND COALESCE(a.status,'') NOT IN ('Cancelled','Closed') ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC LIMIT 1");
    if ($appointmentStmt) {
        $appointmentStmt->bind_param('i', $patient_id);
        $appointmentStmt->execute();
        $recentAppointment = $appointmentStmt->get_result()->fetch_assoc();
        $appointmentStmt->close();
    }
}

$isMaternityPatient = !empty($patient) && empty($patient['is_walkin']) && in_array((string)($patient['clinic_category'] ?? ''), ['ANC','PNC','Maternity'], true);

// Historical encounters are kept separate from the current encounter. Prefer the
// canonical visits table so older visits cannot be mistaken for today's encounter.
$encounterHistory = [];
if ($patient_id > 0 && hms_visits_available($conn)) {
    $historyStmt = $conn->prepare("SELECT visit_number, visit_date, visit_time, visit_type, clinic_category, status FROM visits WHERE patient_id=? ORDER BY visit_date DESC, visit_time DESC, id DESC LIMIT 15");
    if ($historyStmt) {
        $historyStmt->bind_param('i', $patient_id);
        $historyStmt->execute();
        $historyResult = $historyStmt->get_result();
        while ($historyRow = $historyResult->fetch_assoc()) {
            $encounterHistory[] = $historyRow;
        }
        $historyStmt->close();
    }
}

$quickActions = [
    ['label'=>'Record Vitals','icon'=>'fa-heartbeat','tab'=>'clinical','allowed'=>can_module_action($conn,'clinical','create')],
    ['label'=>'Clinical Encounter','icon'=>'fa-user-md','tab'=>'clinical','allowed'=>can_module_action($conn,'clinical','create')],
    ['label'=>'Order Service / Lab','icon'=>'fa-flask','tab'=>'services','allowed'=>can_module_action($conn,'clinical','create')],
    ['label'=>'Prescribe Medicine','icon'=>'fa-pills','tab'=>'prescriptions','allowed'=>can_module_action($conn,'clinical','create')],
    ['label'=>'View Billing','icon'=>'fa-file-invoice-dollar','tab'=>'billing','allowed'=>can_module_action($conn,'finance','view')],
    ['label'=>'Maternity','icon'=>'fa-female','url'=>'/hospital_system/maternity/index.php?patient_id='.(int)$patient_id,'allowed'=>$isMaternityPatient && can_module_action($conn,'maternity','view')],
];
if ($currentAdmission && can_module_action($conn,'clinical','approve')) {
    $quickActions[] = ['label'=>'Discharge Patient','icon'=>'fa-sign-out-alt','url'=>'/hospital_system/clinical/discharge_patient.php?id='.(int)$currentAdmission['id'],'allowed'=>true];
} elseif (!$currentAdmission && can_module_action($conn,'clinical','create')) {
    $quickActions[] = ['label'=>'Admit Patient','icon'=>'fa-bed','url'=>'/hospital_system/clinical/admit_patient.php?patient_id='.(int)$patient_id,'allowed'=>true];
}

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
    /* Patient Dashboard command-centre layer. Existing patient header/tabs remain unchanged. */
    .patient-command-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:0 0 18px;}
    .patient-command-card{background:#fff;border:1px solid var(--border-color);border-radius:10px;padding:16px 18px;box-shadow:0 2px 10px rgba(0,0,0,.04);min-height:94px;box-sizing:border-box;}
    .patient-command-card .command-label{font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#6c7a89;margin-bottom:7px;}
    .patient-command-card .command-value{font-size:15px;font-weight:750;color:var(--secondary-blue);line-height:1.35;}
    .patient-command-card .command-meta{font-size:12px;color:#667085;margin-top:5px;line-height:1.4;}
    .patient-command-card.alert-card{border-left:4px solid #dc3545;}
    .patient-command-card.alert-card .command-value{color:#a61b29;}
    .patient-command-card.ok-card{border-left:4px solid #28a745;}
    .patient-command-card.info-card{border-left:4px solid var(--primary-blue);}
    .dashboard-command-row{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(280px,.7fr);gap:18px;margin-bottom:18px;}
    .dashboard-summary-panel{background:#fff;border:1px solid var(--border-color);border-radius:10px;padding:18px;box-shadow:0 2px 10px rgba(0,0,0,.04);}
    .dashboard-summary-title{margin:0;color:var(--secondary-blue);font-size:15px;}
    .dashboard-summary-title i{color:var(--primary-blue);margin-right:7px;}
    .dashboard-summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:14px;}
    .dashboard-summary-item{background:#f8fbff;border:1px solid #e5eef8;border-radius:8px;padding:11px 12px;}
    .dashboard-summary-item .summary-label{font-size:10px;text-transform:uppercase;font-weight:800;color:#718096;display:block;margin-bottom:4px;}
    .dashboard-summary-item .summary-value{font-size:13px;font-weight:700;color:#26364a;line-height:1.35;}
    .dashboard-quick-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;}
    .dashboard-quick-actions a,.dashboard-quick-actions button{display:inline-flex;align-items:center;gap:7px;padding:8px 11px;border-radius:7px;border:1px solid #d6e3f2;background:#f8fbff;color:var(--secondary-blue);font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;}
    .dashboard-quick-actions a:hover,.dashboard-quick-actions button:hover{background:var(--accent-blue);border-color:#a9c9e8;}
    .dashboard-quick-actions .primary-action{background:var(--primary-blue);border-color:var(--primary-blue);color:#fff;}
    .dashboard-quick-actions .primary-action:hover{background:var(--secondary-blue);color:#fff;}
    .dashboard-timeline{margin:0;padding:0;list-style:none;}
    .dashboard-timeline li{position:relative;padding:0 0 13px 22px;border-left:2px solid #d9e7f5;margin-left:5px;font-size:12px;color:#596579;}
    .dashboard-timeline li:last-child{border-left-color:transparent;padding-bottom:0;}
    .dashboard-timeline li:before{content:"";position:absolute;left:-6px;top:1px;width:10px;height:10px;border-radius:50%;background:var(--primary-blue);border:2px solid #fff;box-shadow:0 0 0 1px #a9c9e8;}
    .dashboard-timeline strong{color:#26364a;}
    @media (max-width:1100px){.patient-command-grid{grid-template-columns:repeat(2,minmax(0,1fr));}.dashboard-command-row{grid-template-columns:1fr;}}
    @media (max-width:768px){.patient-command-grid,.dashboard-summary-grid{grid-template-columns:1fr;}}
    
    .service-print-area { display:none; }
    .service-print-card { max-width:980px; margin:0 auto; background:#fff; padding:30px; box-sizing:border-box; }
    .service-print-table { width:100%; border-collapse:collapse; }
    .service-print-table th { background:#007bff; color:#fff; padding:11px; text-align:left; }
    .service-print-table td { padding:10px; border-bottom:1px solid #eee; }
    @media print {
        @page { size:A4 portrait; margin:12mm; }
        html, body { background:#fff !important; }
        body * { visibility:hidden !important; }
        .service-print-area,
        .service-print-area * { visibility:visible !important; }
        .service-print-area {
            display:block !important;
            position:absolute !important;
            left:0 !important;
            top:0 !important;
            width:100% !important;
            margin:0 !important;
            padding:0 !important;
            background:#fff !important;
        }
        .service-print-card {
            max-width:none !important;
            width:100% !important;
            padding:0 !important;
            margin:0 !important;
            box-shadow:none !important;
        }
        .service-print-table { width:100% !important; border-collapse:collapse !important; }
        .service-print-table th {
            background:#007bff !important;
            color:#fff !important;
            -webkit-print-color-adjust:exact;
            print-color-adjust:exact;
        }
        .service-print-table tr { break-inside:avoid; page-break-inside:avoid; }
    }

    @media (max-width: 992px) {
        .header-content { grid-template-columns: 1fr; }
        .clinical-grid { grid-template-columns: 1fr; }
        .coverage-form { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .container { padding: 16px; }
        .dashboard-tabs li { width: 100%; margin-right: 0; margin-bottom: 6px; border-radius: 8px; }
    }
.stamp-sign-area{display:flex;justify-content:space-between;gap:40px;margin-top:28px;padding-top:10px}.stamp-box,.signature-box{width:45%;min-height:90px;border:1px solid #777;padding:12px;text-align:center;box-sizing:border-box}.stamp-box span{display:block;height:55px}.signature-box span{display:block;height:38px;border-bottom:1px solid #555;margin:12px 8px 8px}.signature-box small{display:block;text-align:left;font-size:10px}.stamp-note{text-align:center;margin-top:12px;font-size:10px;color:#666}</style>

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
                <div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                    <?php if ($canPatientEdit): ?><a href="/hospital_system/patients/edit_patient.php?id=<?= (int)$patient_id ?>" style="background:#e8f1ff; color:#1f5fbf; border:1px solid #cfe0ff; padding:5px 12px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:700;"><i class="fas fa-user-edit"></i> Edit Patient</a><?php endif; ?>
                    <?php if ($isMaternityPatient && can_module_action($conn, 'maternity', 'view')): ?><a href="/hospital_system/maternity/index.php?patient_id=<?= (int)$patient_id ?>" style="background:#ffecf3; color:#c2185b; border:none; padding:5px 12px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:700;">Maternity Visit</a><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="patient-command-grid">
        <div class="patient-command-card <?= $activeVisit ? 'info-card' : '' ?>">
            <div class="command-label">Current Encounter</div>
            <div class="command-value"><?= $activeVisit ? htmlspecialchars($activeVisit['visit_number']) : 'No open encounter' ?></div>
            <div class="command-meta"><?= $activeVisit ? htmlspecialchars($activeVisit['visit_type'].' · '.($activeVisit['clinic_category'] ?: 'General').' · '.$activeVisit['status']) : 'Start an encounter when the patient is seen.' ?></div>
        </div>
        <div class="patient-command-card <?= $currentAdmission ? 'alert-card' : 'ok-card' ?>">
            <div class="command-label">Admission Status</div>
            <div class="command-value"><?= $currentAdmission ? 'Currently Admitted' : 'Not Admitted' ?></div>
            <div class="command-meta"><?= $currentAdmission ? htmlspecialchars(($currentAdmission['ward_name'] ?: 'Ward').' · Bed '.($currentAdmission['bed_number'] ?: '—')) : 'No active inpatient admission.' ?></div>
        </div>
        <div class="patient-command-card <?= $balance_due > 0 ? 'alert-card' : 'ok-card' ?>">
            <div class="command-label">Outstanding Balance</div>
            <div class="command-value">KSH <?= number_format($balance_due,2) ?></div>
            <div class="command-meta"><?= htmlspecialchars($currentPayerLabel) ?> · Co-pay estimate KSH <?= number_format($currentCopayEstimate,2) ?></div>
        </div>
        <div class="patient-command-card <?= $recentAppointment ? 'info-card' : 'ok-card' ?>">
            <div class="command-label">Appointment</div>
            <div class="command-value"><?= $recentAppointment ? htmlspecialchars(date('d M Y',strtotime($recentAppointment['appointment_date']))) : 'No active appointment' ?></div>
            <div class="command-meta"><?= $recentAppointment ? htmlspecialchars(($recentAppointment['appointment_time'] ?? '').' · '.($recentAppointment['status'] ?? 'Scheduled')) : 'No pending appointment found.' ?></div>
        </div>
    </div>

    <div class="dashboard-command-row">
        <div class="dashboard-summary-panel">
            <h3 class="dashboard-summary-title"><i class="fas fa-notes-medical"></i> Clinical Snapshot</h3>
            <div class="dashboard-summary-grid">
                <div class="dashboard-summary-item">
                    <span class="summary-label">Latest Vitals</span>
                    <span class="summary-value"><?= $latestVital ? 'BP '.htmlspecialchars($latestVital['bp'] ?? '—').' · Pulse '.htmlspecialchars($latestVital['pulse'] ?? '—').' · Temp '.htmlspecialchars($latestVital['temperature'] ?? '—') : 'No vitals recorded' ?></span>
                </div>
                <div class="dashboard-summary-item">
                    <span class="summary-label">Latest Diagnosis</span>
                    <span class="summary-value"><?= htmlspecialchars($encounter['diagnosis'] ?? 'No diagnosis recorded') ?></span>
                </div>
                <div class="dashboard-summary-item <?= !empty($encounter['allergies']) ? 'alert-card' : '' ?>">
                    <span class="summary-label">Allergies</span>
                    <span class="summary-value"><?= !empty($encounter['allergies']) ? htmlspecialchars($encounter['allergies']) : 'No allergy documented' ?></span>
                </div>
            </div>
            <div class="dashboard-quick-actions">
                <?php foreach ($quickActions as $action): ?>
                    <?php if (!empty($action['allowed'])): ?>
                        <?php if (!empty($action['url'])): ?>
                            <a href="<?= htmlspecialchars($action['url']) ?>" class="primary-action"><i class="fas <?= htmlspecialchars($action['icon']) ?>"></i><?= htmlspecialchars($action['label']) ?></a>
                        <?php else: ?>
                            <button type="button" onclick="showTab('<?= htmlspecialchars($action['tab']) ?>')"><i class="fas <?= htmlspecialchars($action['icon']) ?>"></i><?= htmlspecialchars($action['label']) ?></button>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dashboard-summary-panel">
            <h3 class="dashboard-summary-title"><i class="fas fa-route"></i> Patient Journey</h3>
            <ul class="dashboard-timeline">
                <li><strong>Registration</strong><br><?= htmlspecialchars($patient['patient_number'] ?? 'Patient record') ?></li>
                <?php if ($recentAppointment): ?><li><strong>Appointment</strong><br><?= htmlspecialchars(($recentAppointment['appointment_date'] ?? '').' '.($recentAppointment['appointment_time'] ?? '')) ?></li><?php endif; ?>
                <?php if ($activeVisit): ?><li><strong>Current Visit</strong><br><?= htmlspecialchars($activeVisit['visit_number'].' · '.$activeVisit['status']) ?></li><?php endif; ?>
                <?php if ($latestVital): ?><li><strong>Vitals</strong><br><?= htmlspecialchars(date('d M Y H:i',strtotime($latestVital['created_at']))) ?></li><?php endif; ?>
                <?php if ($encounter): ?><li><strong>Clinical Encounter</strong><br><?= htmlspecialchars($encounter['diagnosis'] ?? 'Clinical record available') ?></li><?php endif; ?>
                <?php if ($currentAdmission): ?><li><strong>Inpatient</strong><br><?= htmlspecialchars(($currentAdmission['ward_name'] ?? 'Ward').' · Bed '.($currentAdmission['bed_number'] ?? '—')) ?></li><?php endif; ?>
                <?php if ($balance_due > 0): ?><li><strong>Finance</strong><br>KSH <?= number_format($balance_due,2) ?> outstanding</li><?php else: ?><li><strong>Finance</strong><br>Account currently settled</li><?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="dashboard-summary-panel" style="margin-bottom:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <h3 class="dashboard-summary-title"><i class="fas fa-history"></i> Patient History</h3>
            <span style="font-size:11px;color:#718096;">Historical encounters — current encounter is shown above</span>
        </div>
        <?php if ($encounterHistory): ?>
            <div style="overflow-x:auto;margin-top:12px;">
                <table class="table-custom" style="margin-top:0;">
                    <thead><tr><th>Date</th><th>Visit No.</th><th>Type</th><th>Department</th><th>Status</th><th>Context</th></tr></thead>
                    <tbody>
                    <?php foreach ($encounterHistory as $history): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d M Y', strtotime((string)$history['visit_date']))) ?><br><small><?= htmlspecialchars((string)($history['visit_time'] ?? '')) ?></small></td>
                            <td><strong><?= htmlspecialchars((string)$history['visit_number']) ?></strong></td>
                            <td><?= htmlspecialchars((string)$history['visit_type']) ?></td>
                            <td><?= htmlspecialchars((string)($history['clinic_category'] ?: 'General')) ?></td>
                            <td><?= htmlspecialchars((string)$history['status']) ?></td>
                            <td><?php if ($activeVisit && (string)$history['visit_number'] === (string)$activeVisit['visit_number']): ?><span class="status-chip completed">Current</span><?php else: ?>Historical encounter<?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="margin:12px 0 0;color:#718096;font-size:13px;">No encounter history is available yet.</p>
        <?php endif; ?>
    </div>

    <ul class="dashboard-tabs">
        <li onclick="showTab('clinical')" id="tab-clinical" class="active">Clinical Encounter</li>
        <li onclick="showTab('services')" id="tab-services">Services</li>
        <li onclick="showTab('prescriptions')" id="tab-prescriptions">Pharmacy & Prescriptions</li>
        <li onclick="showTab('billing')" id="tab-billing">Billing</li>
        <li onclick="showTab('maternity')" id="tab-maternity">Maternity</li>
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
        <?php if (!empty($patient['is_walkin']) && $walkinRequestedService !== ''): ?>
            <div style="margin-bottom:18px; padding:14px 16px; background:#fff8e1; border:1px solid #f0c36d; border-left:5px solid #f39c12; border-radius:7px;">
                <div style="font-size:11px; font-weight:700; color:#8a6d1d; text-transform:uppercase; letter-spacing:.5px;">Walk-in Requested Service</div>
                <div style="font-size:18px; font-weight:700; color:#5d4b12; margin-top:4px;"><?= htmlspecialchars($walkinRequestedService) ?></div>
                <div style="font-size:12px; color:#7a6a2a; margin-top:4px;">Selected during walk-in registration and loaded automatically.</div>
            </div>
        <?php endif; ?>
        <form method="post" style="margin-bottom:30px; background:#f4f7f6; padding:20px; border-radius:8px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:15px;">
                <div>
                    <label class="info-label">Service Description</label>
                    <select name="service_id" onchange="updatePrice(this, 'svc_p')" required style="width:100%; padding:10px;">
                        <option value="">Search Service...</option>
                        <?php $all_services->data_seek(0); while($s=$all_services->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>" data-price="<?= $s['price'] ?>" <?= ($walkinRequestedServiceId > 0 && (int)$s['id'] === $walkinRequestedServiceId) ? 'selected' : '' ?>><?= htmlspecialchars($s['service_name']) ?> (<?= strtoupper(htmlspecialchars($s['category'])) ?>)</option>
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
                    <option value="<?= $s['id'] ?>" <?= ($walkinRequestedServiceId > 0 && (int)$s['id'] === $walkinRequestedServiceId) ? 'selected' : '' ?>><?= htmlspecialchars($s['service_name']) ?></option>
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
                <td><form method="post" style="display:inline;" onsubmit="return confirm('Remove this service?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="item_id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="type" value="service"><button type="submit" name="delete_item" style="border:0;background:none;color:red;cursor:pointer;">&times; Remove</button></form></td>
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
                <td>KSH <?= number_format((float)($p['unit_price'] ?? 0), 2) ?></td>
                <td>KSH <?= number_format($p['quantity'] * (float)($p['unit_price'] ?? 0), 2) ?></td>
                <td><?= date('d/m/y', strtotime($p['created_at'])) ?></td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Remove this medication?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="item_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="type" value="prescription"><button type="submit" name="delete_item" style="border:0;background:none;color:red;cursor:pointer;">&times; Remove</button></form>
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

        <div style="background:#fdfefe; border:1px solid #ddd; padding:22px; border-radius:10px; margin-bottom:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap;">
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                    <h4 style="margin:0; color:var(--secondary-blue);"><i class="fas fa-list"></i> Services & Charges</h4>
                    <button type="button" onclick="printServiceList()" class="btn-save" style="float:none; margin:0; padding:10px 18px;">
                        <i class="fas fa-print"></i> Print Service List
                    </button>
                </div>
                    <p style="margin:6px 0 0; color:#666; font-size:13px;">
                        Read-only history of services, investigations and medicines billed during this visit.
                        Payments are collected centrally by the Cashier.
                    </p>
                </div>
                <div class="badge-info">Central Cashier collects payments</div>
            </div>

            <div style="overflow-x:auto; margin-top:18px;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Service / Item</th>
                            <th>Department</th>
                            <th>Visit</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Amount</th>
                            <th>Invoice</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($billingItems && $billingItems->num_rows > 0): ?>
                            <?php while ($billItem = $billingItems->fetch_assoc()): ?>
                                <?php
                                    $itemType = strtolower(trim((string)($billItem['item_type'] ?? '')));
                                    if ($itemType === '') {
                                        $descriptionLower = strtolower((string)($billItem['description'] ?? ''));
                                        if (strpos($descriptionLower, 'lab:') === 0) {
                                            $itemType = 'lab';
                                        } elseif (strpos($descriptionLower, 'medication:') === 0 || strpos($descriptionLower, 'medicine:') === 0) {
                                            $itemType = 'pharmacy';
                                        } elseif (strpos($descriptionLower, 'radiology:') === 0 || strpos($descriptionLower, 'x-ray:') === 0) {
                                            $itemType = 'radiology';
                                        } else {
                                            $itemType = 'service';
                                        }
                                    }
                                    $departmentLabels = [
                                        'lab' => 'Laboratory',
                                        'laboratory' => 'Laboratory',
                                        'pharmacy' => 'Pharmacy',
                                        'medicine' => 'Pharmacy',
                                        'radiology' => 'Radiology',
                                        'service' => 'Service',
                                    ];
                                    $department = $departmentLabels[$itemType] ?? ucwords(str_replace(['_', '-'], ' ', $itemType));
                                ?>
                                <tr>
                                    <td><?= !empty($billItem['invoice_date']) ? date('d M Y H:i', strtotime($billItem['invoice_date'])) : 'N/A' ?></td>
                                    <td><strong><?= htmlspecialchars($billItem['description'] ?? 'Billed Item') ?></strong></td>
                                    <td><?= htmlspecialchars($department) ?></td>
                                    <td><?= htmlspecialchars($billItem['visit_number'] ?? ($activeVisit['visit_number'] ?? '—')) ?></td>
                                    <td><?= number_format((float)($billItem['quantity'] ?? 1), 2) ?></td>
                                    <td>KSH <?= number_format((float)($billItem['unit_price'] ?? 0), 2) ?></td>
                                    <td><strong>KSH <?= number_format((float)($billItem['total'] ?? 0), 2) ?></strong></td>
                                    <td>#INV-<?= (int)$billItem['invoice_id'] ?></td>
                                    <td><span class="status-chip <?= strtolower($billItem['invoice_status'] ?? '') === 'paid' ? 'completed' : 'pending' ?>"><?= htmlspecialchars($billItem['invoice_status'] ?? 'Pending') ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center; color:#666; padding:25px;">
                                    No billed services or charges are recorded for this visit.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="background:#f8fbff; border:1px solid var(--border-color); padding:20px; border-radius:10px;">
            <h4 style="margin-top:0; color:var(--secondary-blue);"><i class="fas fa-info-circle"></i> Payment Status</h4>
            <p style="margin:0; color:#555; font-size:13px;">
                Outstanding amounts shown above are for information only. The Patient Dashboard does not accept payments,
                and invoices are not printed from this screen. Use <strong>Central Cashier</strong> for payment collection
                and official receipts.
            </p>
        </div>
    </div>


    <div class="service-print-area">
        <div class="service-print-card">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:20px; border-bottom:2px solid #007bff; padding-bottom:18px; margin-bottom:20px;">
                <div><img src="/hospital_system/assets/img/logo.png" alt="Hospital Logo" style="max-height:65px;" onerror="this.style.display='none'"></div>
                <div style="text-align:right;">
                    <h2 style="margin:0; font-size:22px; text-transform:uppercase;">Emaqure Medical Centre</h2>
                    <p style="margin:3px 0; font-size:12px; color:#555;">Biashara Street, Opposite Old Naiwe School, Mlolongo</p>
                    <p style="margin:3px 0; font-size:12px; color:#555;">+254793069565 | emaquremedicalcentre@gmail.com</p>
                </div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
                <div><h1 style="margin:0; color:#007bff; font-size:25px;">Service Statement</h1><div style="font-size:12px; color:#666; margin-top:5px;">Services and charges presented for payment</div></div>
                <div style="text-align:right; font-size:13px;"><strong>Printed:</strong> <?= date('d-m-Y H:i') ?><br><strong>Patient No.:</strong> <?= htmlspecialchars($patient['patient_number'] ?? '—') ?></div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; padding:16px; background:#f8f9fa; border-radius:8px; margin-bottom:22px;">
                <div><div style="font-size:11px; font-weight:700; color:#666; text-transform:uppercase;">Patient Name</div><div style="font-size:15px; font-weight:600;"><?= htmlspecialchars($patient['full_name']) ?></div></div>
                <div><div style="font-size:11px; font-weight:700; color:#666; text-transform:uppercase;">Patient Number</div><div style="font-size:15px; font-weight:600;"><?= htmlspecialchars($patient['patient_number']) ?></div></div>
            </div>
            <table class="service-print-table">
                <thead><tr><th>#</th><th>Description</th><th>Source</th><th>Qty</th><th style="text-align:right;">Price (KSH)</th><th style="text-align:right;">Amount (KSH)</th></tr></thead>
                <tbody>
                    <?php $printNo=0; $printTotal=0.0; if($billingItems){ $billingItems->data_seek(0); while($printRow=$billingItems->fetch_assoc()){ $printNo++; $printTotal+=(float)($printRow['total']??0); $printType=ucfirst(strtolower(trim((string)($printRow['item_type']??'service')))); ?>
                    <tr><td><?= $printNo ?></td><td><?= htmlspecialchars($printRow['description']??'Billed Item') ?></td><td><?= htmlspecialchars($printType) ?></td><td><?= number_format((float)($printRow['quantity']??1),0) ?></td><td style="text-align:right;"><?= number_format((float)($printRow['unit_price']??0),2) ?></td><td style="text-align:right;"><strong><?= number_format((float)($printRow['total']??0),2) ?></strong></td></tr>
                    <?php } } ?>
                    <?php if($printNo===0): ?><tr><td colspan="6" style="text-align:center;">No billed services recorded.</td></tr><?php endif; ?>
                </tbody>
            </table>
            <div style="display:flex; justify-content:flex-end; margin-top:22px;">
                <div style="width:340px; padding:18px; background:#f8f9fa; border:1px solid #ddd; border-radius:8px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><strong>Total Bill</strong><strong>KSH <?= number_format($printTotal,2) ?></strong></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;color:#28a745;"><span>Amount Paid</span><span>KSH <?= number_format($total_paid,2) ?></span></div>
                    <div style="border-top:2px solid #007bff;padding-top:10px;display:flex;justify-content:space-between;font-size:19px;color:#007bff;"><strong>Amount to Pay</strong><strong>KSH <?= number_format(max($printTotal-$total_paid,0),2) ?></strong></div>
                </div>
            </div>
            <div style="margin-top:28px;padding-top:14px;border-top:1px dashed #ccc;font-size:11px;color:#777;display:flex;justify-content:space-between;"><span>Generated By: <strong><?= htmlspecialchars($_SESSION['full_name']??'System Administrator') ?></strong></span><span>For payment processing — not a receipt.</span></div>
            <div class="stamp-sign-area">
                <div class="stamp-box"><strong>OFFICIAL STAMP</strong><span></span></div>
                <div class="signature-box"><strong>AUTHORIZED SIGNATURE</strong><span></span><small>Name / Designation: ______________________________</small></div>
            </div>
            <div class="stamp-note">Stamp and sign after verification before presenting this statement for payment.</div>
        </div>
    </div>

    <div id="maternity" class="card" style="display:none;">
        <h3>Maternity Care</h3>
        <p style="color:#666; margin-top:-8px; margin-bottom:20px;">Maternity information for this patient only. Open the full maternity workspace to record or review maternal and newborn care.</p>
        <?php if ($maternityRecord): ?>
        <div class="sub-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:15px;flex-wrap:wrap;">
                <div><h3 style="margin:0;color:var(--primary-blue);"><?= htmlspecialchars($maternityRecord['anc_number'] ?? 'Maternity Record') ?></h3>
                    <p style="margin:6px 0 0;color:#666;">Gravida <?= htmlspecialchars((string)$maternityRecord['gravida']) ?> · Parity <?= htmlspecialchars((string)$maternityRecord['parity']) ?> · EDD <?= !empty($maternityRecord['expected_delivery']) ? htmlspecialchars(date('d M Y',strtotime($maternityRecord['expected_delivery']))) : 'Not set' ?></p>
                </div>
                <a class="btn btn-primary" href="/hospital_system/maternity/index.php?patient_id=<?= (int)$patient_id ?>">Open Maternity Record</a>
            </div>
        </div>
        <div class="coverage-grid">
            <div class="coverage-card"><h4>ANC / PNC Visits</h4><div class="coverage-value"><?= count($maternityVisits) ?></div><div class="coverage-subtext">Recent maternity clinical visits</div></div>
            <div class="coverage-card"><h4>Deliveries</h4><div class="coverage-value"><?= count($maternityDeliveries) ?></div><div class="coverage-subtext">Delivery records on file</div></div>
            <div class="coverage-card"><h4>Admission</h4><div class="coverage-value"><?= $maternityAdmission ? htmlspecialchars($maternityAdmission['status'] ?? $maternityAdmission['clinical_status'] ?? 'Recorded') : 'None' ?></div><div class="coverage-subtext"><?= $maternityAdmission && !empty($maternityAdmission['bed_number']) ? 'Bed '.(int)$maternityAdmission['bed_number'] : 'No maternity admission recorded' ?></div></div>
            <div class="coverage-card"><h4>Newborns</h4><div class="coverage-value"><?php $babyCount=0; $bc=$conn->prepare("SELECT COUNT(*) c FROM maternity_baby WHERE maternity_id=?"); if($bc){$bc->bind_param('i',$mid);$bc->execute();$babyCount=(int)($bc->get_result()->fetch_assoc()['c']??0);$bc->close();} echo $babyCount; ?></div><div class="coverage-subtext">Newborn records linked to this maternity record</div></div>
        </div>
        <?php if ($maternityVisits): ?><div class="sub-card"><h4 style="margin-top:0;color:var(--secondary-blue);">Recent Maternity Visits</h4><div style="overflow-x:auto;"><table class="table-custom"><thead><tr><th>Date</th><th>Type</th><th>BP</th><th>Weight</th><th>Notes</th></tr></thead><tbody><?php foreach($maternityVisits as $mv): ?><tr><td><?= htmlspecialchars(date('d M Y H:i',strtotime($mv['created_at']))) ?></td><td><?= htmlspecialchars($mv['visit_type']) ?></td><td><?= htmlspecialchars($mv['bp']) ?></td><td><?= htmlspecialchars($mv['weight']) ?></td><td><?= htmlspecialchars($mv['notes']) ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?>
        <?php else: ?>
        <div class="sub-card"><h4 style="margin-top:0;color:var(--secondary-blue);">No Maternity Record</h4><p style="color:#666;">This patient does not have a maternity record yet.</p><a class="btn btn-primary" href="/hospital_system/maternity/add.php?patient_id=<?= (int)$patient_id ?>">Start Maternity Record</a></div>
        <?php endif; ?>
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
function printServiceList() { window.print(); }

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
