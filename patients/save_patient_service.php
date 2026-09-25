<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../helpers/billing.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); exit('Invalid security token.'); }
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $service_id = intval($_POST['service_id'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);

    if ($patient_id && $service_id && $category && $price >= 0) {
        $visitId = get_or_create_current_visit($conn, $patient_id, 'Outpatient', $category);
        if ($visitId > 0) {
            $stmt = $conn->prepare("INSERT INTO patient_services (patient_id, service_id, category, price, visit_id, created_at) VALUES (?,?,?,?,?,NOW())");
            $stmt->bind_param("iisdi", $patient_id, $service_id, $category, $price, $visitId);
        } else {
            $stmt = $conn->prepare("INSERT INTO patient_services (patient_id, service_id, category, price, created_at) VALUES (?,?,?,?,NOW())");
            $stmt->bind_param("iisd", $patient_id, $service_id, $category, $price);
        }

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: patient_dashboard.php?id=$patient_id");
            exit;
        } else {
            $error = "Failed to add service.";
        }
    } else {
        $error = "All fields are required.";
    }
}

if (!empty($error)) {
    echo "<div class='alert'>$error</div>";
}