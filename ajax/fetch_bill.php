<?php
require_once '../config/config.php';

$patient_id = intval($_GET['patient_id']);

$result = $conn->query("
    SELECT * FROM billing 
    WHERE patient_id = $patient_id 
    ORDER BY created_at DESC
");

echo "<table class='table-blue'>";
echo "<tr><th>Item</th><th>Amount</th><th>Paid</th><th>Date</th></tr>";

while($row = $result->fetch_assoc()){
    echo "<tr>
        <td>{$row['item']}</td>
        <td>".number_format($row['amount'],2)."</td>
        <td>".number_format($row['paid_amount'],2)."</td>
        <td>{$row['created_at']}</td>
    </tr>";
}
echo "</table>";
