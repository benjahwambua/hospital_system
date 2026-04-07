<?php
require_once __DIR__.'/../config/config.php';

$pid = (int)$_GET['patient_id'];

$res = $conn->query("
SELECT * FROM invoices 
WHERE patient_id=$pid 
ORDER BY id DESC
");

while($r=$res->fetch_assoc()){
    echo "<div class='card mb-2 p-2'>
        <strong>{$r['invoice_number']}</strong><br>
        Total: KES ".number_format($r['total'],2)."<br>
        Status: {$r['status']}
    </div>";
}
