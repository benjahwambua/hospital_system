<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once '../vendor/autoload.php';
use Dompdf\Dompdf;

$id = intval($_GET['invoice_id']);
$data = $conn->query("
SELECT i.*, p.full_name
FROM invoices i
JOIN encounters e ON e.id=i.encounter_id
JOIN patients p ON p.id=e.patient_id
WHERE i.id=$id
")->fetch_assoc();

$pdf = new Dompdf();
$pdf->loadHtml("<h3>Invoice</h3>
Patient: {$data['full_name']}<br>
Total: {$data['total']}");
$pdf->render();
$pdf->stream("invoice.pdf");
