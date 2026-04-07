<?php
require_once __DIR__ . '/../config/config.php';

$search = $_GET['search'] ?? '';
$search = $conn->real_escape_string($search);

$data = [];

$sql = "SELECT id, drug_name, selling_price, quantity FROM pharmacy_stock WHERE drug_name LIKE '%$search%' LIMIT 10";
$res = $conn->query($sql);
if($res && $res->num_rows>0){
    while($row = $res->fetch_assoc()){
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($data);
