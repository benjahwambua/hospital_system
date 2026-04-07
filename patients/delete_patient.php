<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
$id = intval($_GET['id'] ?? 0);
if ($id) { $stmt = $conn->prepare("DELETE FROM patients WHERE id=?"); $stmt->bind_param("i",$id); $stmt->execute(); }
header('Location: /hospital_system/patients/view_patients.php'); exit;
