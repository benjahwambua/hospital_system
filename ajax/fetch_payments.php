<?php
require_once '../config/config.php';

$patient_id = intval($_GET['patient_id']);

$result = $conn->query("
    SELECT * FROM payments 
    WHERE patient_id = $patient_id 
    ORDER BY created_at DESC
");

echo "<table class='table-blue'>";
echo "<tr><th>Amount</th><th>Method</th><th>Date</th></tr>";

while($row = $result->fetch_assoc()){
    echo "<tr>
        <td>".number_format($row['amount'],2)."</td>
        <td>{$row['method']}</td>
        <td>{$row['created_at']}</td>
    </tr>";
}
echo "</table>";
