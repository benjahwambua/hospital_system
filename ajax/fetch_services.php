<?php
require_once __DIR__ . '/../config/config.php';

$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

if(!$category || !$search) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, price FROM services WHERE category=? AND name LIKE ? LIMIT 20");
$like = "%$search%";
$stmt->bind_param("ss", $category, $like);
$stmt->execute();
$result = $stmt->get_result();

$services = [];
while($row = $result->fetch_assoc()) {
    $services[] = $row;
}

echo json_encode($services);
?>