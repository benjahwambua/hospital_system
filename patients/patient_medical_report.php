<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patient_id = intval($_GET['id'] ?? 0);
$p_res = $conn->query("SELECT * FROM patients WHERE id = $patient_id")->fetch_assoc();
if (!$p_res) die("Patient not found.");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Medical Report - <?= $p_res['patient_number'] ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .report-card { max-width: 800px; margin: auto; border: 1px solid #ddd; padding: 40px; position: relative; }
        .watermark { position: fixed; top: 30%; left: 20%; width: 60%; opacity: 0.05; z-index: -1; pointer-events: none; }
        .section { margin-bottom: 30px; }
        h3 { border-bottom: 2px solid #007bff; padding-bottom: 5px; color: #007bff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #eee; text-align: left; font-size: 14px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

<img src="../assets/img/logo.png" class="watermark" alt="Logo">

<div class="report-card">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1>EMAQURE MEDICAL CENTRE</h1>
        <h2>Comprehensive Medical Report</h2>
        <p><strong>Patient:</strong> <?= $p_res['full_name'] ?> | <strong>ID:</strong> <?= $p_res['patient_number'] ?></p>
    </div>

    <div class="section">
        <h3>Consultation History</h3>
        <table>
            <tr><th>Date</th><th>Findings/Diagnosis</th></tr>
            <?php 
            $cons = $conn->query("SELECT created_at, findings FROM consultations WHERE patient_id = $patient_id");
            while($c = $cons->fetch_assoc()) echo "<tr><td>{$c['created_at']}</td><td>{$c['findings']}</td></tr>";
            ?>
        </table>
    </div>

    <div class="section">
        <h3>Laboratory Results</h3>
        <table>
            <tr><th>Test</th><th>Result</th><th>Status</th></tr>
            <?php 
            $labs = $conn->query("SELECT sm.service_name, ps.results, ps.status FROM patient_services ps JOIN services_master sm ON ps.service_id = sm.id WHERE ps.patient_id = $patient_id AND ps.category = 'lab'");
            while($l = $labs->fetch_assoc()) echo "<tr><td>{$l['service_name']}</td><td>{$l['results']}</td><td>{$l['status']}</td></tr>";
            ?>
        </table>
    </div>

    <div class="section">
        <h3>Prescriptions</h3>
        <table>
            <tr><th>Medicine</th><th>Quantity</th></tr>
            <?php 
            $rx = $conn->query("SELECT drug_name, quantity FROM prescriptions WHERE patient_id = $patient_id");
            while($r = $rx->fetch_assoc()) echo "<tr><td>{$r['drug_name']}</td><td>{$r['quantity']}</td></tr>";
            ?>
        </table>
    </div>

    <div class="no-print" style="text-align:center;">
        <button onclick="window.print()">Print Report</button>
    </div>
</div>
</body>
</html>