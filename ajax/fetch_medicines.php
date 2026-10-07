<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'pharmacy', 'view');

$search = $_GET['search'] ?? '';
$data = [];
$stmt = $conn->prepare("SELECT id, drug_name, selling_price, quantity FROM pharmacy_stock WHERE drug_name LIKE CONCAT('%', ?, '%') LIMIT 10");
if (!$stmt) { http_response_code(500); echo json_encode(['error' => 'Unable to load medicines.']); exit; }
$stmt->bind_param('s', $search);
$stmt->execute();
$res = $stmt->get_result();
if($res && $res->num_rows>0){
    while($row = $res->fetch_assoc()){
        $data[] = $row;
    }
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($data);
