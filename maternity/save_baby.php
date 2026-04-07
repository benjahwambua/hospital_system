<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$maternity_id = intval($_POST['maternity_id']);
$baby_number = intval($_POST['baby_number'] ?? 1);
$gender = $conn->real_escape_string($_POST['gender'] ?? '');
$weight = floatval($_POST['weight'] ?? 0);
$apgar = $conn->real_escape_string($_POST['apgar'] ?? '');
$notes = $conn->real_escape_string($_POST['notes'] ?? '');

$stmt = $conn->prepare("INSERT INTO maternity_baby (maternity_id, baby_number, gender, weight, apgar, notes) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("iisdds", $maternity_id, $baby_number, $gender, $weight, $apgar, $notes);
$stmt->execute();
$stmt->close();

audit('maternity_baby', "maternity_id={$maternity_id},baby={$baby_number}");
header("Location: view.php?id={$maternity_id}");
exit;
