<?php
include('../config.php');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=deliveries_export.csv');

$output = fopen("php://output", "w");

// CSV column headers
fputcsv($output, ['ID', 'Mother Name', 'Delivery Date', 'Type', 'Baby Weight', 'Remarks']);

$query = mysqli_query($conn, "SELECT * FROM deliveries ORDER BY id DESC");

while ($row = mysqli_fetch_assoc($query)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
