<?php
require_once __DIR__ . '/../config/config.php';

$patient_id = intval($_GET['patient_id'] ?? 0);
$category   = $_GET['category'] ?? '';

if(!$patient_id || !$category) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT ps.id, s.name, ps.price, ps.created_at
    FROM patient_services ps
    JOIN services s ON s.id = ps.service_id
    WHERE ps.patient_id=? AND s.category=?
    ORDER BY ps.created_at DESC
");
$stmt->bind_param("is", $patient_id, $category);
$stmt->execute();
$result = $stmt->get_result();

$services = [];
while($row = $result->fetch_assoc()) {
    $services[] = $row;
}

echo json_encode($services);
?>