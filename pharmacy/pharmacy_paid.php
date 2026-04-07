<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if ($_SESSION['role']!=='pharmacist') die("Access denied");

$encounter_id = (int)$_GET['encounter_id'];

$inv = $conn->query("
 SELECT status FROM invoices WHERE encounter_id=$encounter_id
")->fetch_assoc();

if (!$inv || $inv['status']!=='paid') {
    die("Invoice not paid");
}

echo "Dispensing allowed";
