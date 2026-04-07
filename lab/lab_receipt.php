<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$id = intval($_GET['id'] ?? 0);

// Query to get the service details and price
$query = "SELECT ps.*, p.full_name, p.patient_number, sm.service_name, sm.price 
          FROM patient_services ps 
          JOIN patients p ON ps.patient_id = p.id 
          JOIN services_master sm ON ps.service_id = sm.id 
          WHERE ps.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) die("Record not found.");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?= htmlspecialchars($res['patient_number']) ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #f0f0f0; margin: 0; padding: 20px; color: #000; }
        
        /* Thermal Receipt Style (80mm) */
        .receipt-container { 
            width: 80mm; 
            margin: auto; 
            background: #fff; 
            padding: 10mm 5mm; 
            box-shadow: 0 0 5px rgba(0,0,0,0.1); 
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }
        .dashed-line { border-top: 1px dashed #000; margin: 10px 0; }
        
        .hospital-name { font-size: 16px; margin-bottom: 5px; text-transform: uppercase; }
        .receipt-title { font-size: 14px; margin: 10px 0; border: 1px solid #000; padding: 2px; }

        .info-table { width: 100%; font-size: 12px; margin-bottom: 10px; }
        .items-table { width: 100%; font-size: 12px; border-collapse: collapse; }
        .items-table th { border-bottom: 1px dashed #000; text-align: left; padding: 5px 0; }
        .items-table td { padding: 5px 0; }

        .total-section { text-align: right; margin-top: 10px; font-size: 14px; }

        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { box-shadow: none; width: 100%; margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="center">
        <div class="hospital-name bold">EMAQURE MEDICAL CENTRE</div>
        <div style="font-size: 11px;">Biashara Street, Mlolongo</div>
        <div style="font-size: 11px;">Tel: +254 793 069 565</div>
        <div class="receipt-title bold">OFFICIAL RECEIPT</div>
    </div>

    <div class="dashed-line"></div>
    <table class="info-table">
        <tr>
            <td>Date: <?= date('d/m/Y H:i', strtotime($res['created_at'])) ?></td>
        </tr>
        <tr>
            <td class="bold">Patient: <?= htmlspecialchars($res['full_name']) ?></td>
        </tr>
        <tr>
            <td>ID: <?= htmlspecialchars($res['patient_number']) ?></td>
        </tr>
        <tr>
            <td>Receipt No: #REC-<?= $res['id'] ?></td>
        </tr>
    </table>
    <div class="dashed-line"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= htmlspecialchars($res['service_name']) ?></td>
                <td style="text-align: right;"><?= number_format($res['price'], 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="dashed-line"></div>
    <div class="total-section">
        <table style="width: 100%;">
            <tr class="bold">
                <td style="text-align: left;">TOTAL PAID</td>
                <td style="text-align: right;">KES <?= number_format($res['price'], 2) ?></td>
            </tr>
        </table>
    </div>

    <div class="dashed-line"></div>
    <div class="center" style="font-size: 11px; margin-top: 15px;">
        Payment Status: <span class="bold">PAID</span><br>
        Served By: Laboratory System<br><br>
        * Quick Recovery *
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 20px; background: #000; color: #fff; border: none; cursor:pointer;">Print Slip</button>
    </div>
</div>

</body>
</html>