<?php
// maternity/stats_export_csv.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="maternity_deliveries.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Delivery ID','Maternity ID','Patient','Delivery Time','Mode','Baby Weight','Complications']);

$res = $conn->query("SELECT d.*, p.full_name FROM maternity_delivery d JOIN maternity m ON m.id=d.maternity_id JOIN patients p ON p.id=m.patient_id ORDER BY d.created_at DESC");
while($r=$res->fetch_assoc()){
    fputcsv($out, [$r['id'],$r['maternity_id'],$r['full_name'],$r['delivery_time'] ?? $r['created_at'],$r['delivery_mode'] ?? $r['type'],$r['baby_weight'] ?? '', strip_tags($r['complications'] ?? '')]);
}
fclose($out);
exit;
