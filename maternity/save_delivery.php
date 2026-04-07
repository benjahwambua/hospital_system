<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$maternity_id = intval($_POST['maternity_id']);
$delivery_time = $_POST['delivery_time'] ?: NULL;
$delivery_mode = $conn->real_escape_string($_POST['delivery_mode'] ?? '');
$primary_doctor = intval($_POST['primary_doctor'] ?? 0);
$mother_condition = $conn->real_escape_string($_POST['mother_condition'] ?? '');
$complications = $conn->real_escape_string($_POST['notes'] ?? '');
$blood_loss = floatval($_POST['blood_loss'] ?? 0);

$stmt = $conn->prepare("INSERT INTO maternity_delivery (maternity_id, delivery_time, delivery_mode, primary_doctor, mother_condition, complications, blood_loss, notes) VALUES (?,?,?,?,?,?,?,?)");
$stmt->bind_param("ississds", $maternity_id, $delivery_time, $delivery_mode, $primary_doctor, $mother_condition, $complications, $blood_loss, $complications);
$stmt->execute();
$stmt->close();

audit('maternity_delivery', "maternity_id={$maternity_id},mode={$delivery_mode}");
header("Location: view.php?id={$maternity_id}");
exit;
