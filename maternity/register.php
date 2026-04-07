<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // patient first
    $stmt = $conn->prepare("
        INSERT INTO patients (full_name, phone, gender)
        VALUES (?,?, 'Female')
    ");
    $stmt->bind_param("ss", $_POST['name'], $_POST['phone']);
    $stmt->execute();
    $pid = $stmt->insert_id;
    $stmt->close();

    // maternity record
    $stmt = $conn->prepare("
        INSERT INTO maternity_records
        (patient_id, gravida, para, last_menstrual_period, expected_delivery_date)
        VALUES (?,?,?,?,?)
    ");
    $stmt->bind_param(
        "iiiss",
        $pid,
        $_POST['gravida'],
        $_POST['para'],
        $_POST['lmp'],
        $_POST['edd']
    );
    $stmt->execute();
    $stmt->close();

    header("Location: /hospital_system/patients/patient_history.php?id=".$pid);
    exit;
}
