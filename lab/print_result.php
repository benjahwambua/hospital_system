<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$id = intval($_GET['id'] ?? 0);
// Enhanced query to get all necessary clinical data
$query = "SELECT ps.*, p.full_name, p.patient_number, p.gender, p.age, sm.service_name 
          FROM patient_services ps 
          JOIN patients p ON ps.patient_id = p.id 
          JOIN services_master sm ON ps.service_id = sm.id 
          WHERE ps.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) die("Result not found.");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Report - <?= htmlspecialchars($res['patient_number']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f0f0; margin: 0; padding: 20px; color: #222; }
        
        /* A4 Container */
        .report-paper { 
            width: 210mm; 
            min-height: 297mm; 
            margin: auto; 
            background: #fff; 
            padding: 15mm 20mm; 
            position: relative; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            opacity: 0.03;
            width: 80%;
            pointer-events: none;
            z-index: 0;
        }

        /* Branding */
        .header-table { width: 100%; border-bottom: 3px solid #0056b3; margin-bottom: 20px; }
        .hospital-name { color: #0056b3; font-size: 24px; font-weight: bold; margin: 0; }
        .hospital-info { font-size: 12px; color: #555; line-height: 1.4; }

        /* Patient Info Section */
        .patient-info-table { width: 100%; border: 1px solid #ddd; border-collapse: collapse; margin-bottom: 30px; font-size: 13px; z-index: 2; position: relative; }
        .patient-info-table td { border: 1px solid #ddd; padding: 8px 12px; }
        .label { background: #f9f9f9; font-weight: bold; width: 18%; }
        .value { width: 32%; }

        .report-title { text-align: center; text-decoration: underline; text-transform: uppercase; font-size: 18px; margin-bottom: 25px; color: #333; }

        /* Results Section */
        .results-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .results-table th { border-bottom: 2px solid #333; text-align: left; padding: 10px; font-size: 14px; text-transform: uppercase; }
        .results-table td { padding: 15px 10px; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: top; }
        
        .findings-text { font-weight: 600; font-size: 15px; color: #000; white-space: pre-line; }

        /* Footer & Signatures */
        .report-footer { margin-top: 60px; font-size: 12px; }
        .sig-container { display: flex; justify-content: space-between; margin-top: 50px; }
        .sig-box { text-align: center; width: 200px; }
        .sig-line { border-top: 1px solid #333; margin-bottom: 5px; }

        .disclaimer { font-size: 10px; color: #888; text-align: center; margin-top: 50px; font-style: italic; }

        @media print {
            body { background: #fff; padding: 0; }
            .report-paper { box-shadow: none; margin: 0; width: 100%; padding: 10mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="report-paper">
    <img src="../assets/img/logo.png" class="watermark" alt="Watermark">

    <table class="header-table">
        <tr>
            <td style="padding-bottom: 10px;">
                <img src="../assets/img/logo.png" style="max-height: 70px;" alt="Hospital Logo">
            </td>
            <td style="text-align: right; padding-bottom: 10px;">
                <h1 class="hospital-name">EMAQURE MEDICAL CENTRE</h1>
                <div class="hospital-info">
                    Biashara Street, Mlolongo, Kenya<br>
                    Contact: +254 793 069 565<br>
                    Email: info@emaqure.co.ke
                </div>
            </td>
        </tr>
    </table>

    <table class="patient-info-table">
        <tr>
            <td class="label">Patient Name</td>
            <td class="value"><strong><?= htmlspecialchars($res['full_name']) ?></strong></td>
            <td class="label">Lab No.</td>
            <td class="value">LAB-<?= $res['id'] ?></td>
        </tr>
        <tr>
            <td class="label">Age / Sex</td>
            <td class="value"><?= $res['age'] ?? 'N/A' ?> Yrs / <?= $res['gender'] ?></td>
            <td class="label">Registered</td>
            <td class="value"><?= date('d-M-Y H:i', strtotime($res['created_at'])) ?></td>
        </tr>
        <tr>
            <td class="label">Patient ID</td>
            <td class="value"><?= htmlspecialchars($res['patient_number']) ?></td>
            <td class="label">Reported Date</td>
            <td class="value"><?= date('d-M-Y H:i') ?></td>
        </tr>
    </table>

    <h2 class="report-title">Department of Laboratory Medicine</h2>

    <table class="results-table">
        <thead>
            <tr>
                <th style="width: 40%;">Investigation</th>
                <th style="width: 30%;">Result / Observation</th>
                <th style="width: 30%;">Reference Range / Units</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($res['service_name']) ?></strong>
                </td>
                <td class="findings-text">
                    <?= nl2br(htmlspecialchars($res['results'])) ?>
                </td>
                <td style="color: #666; font-size: 13px;">
                    As per clinical correlation
                </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <p style="font-size: 13px;"><strong>Note:</strong> All results are correlated with the clinical history and other investigations. Please consult your physician for further management.</p>
    </div>

    <div class="sig-container">
        <div class="sig-box">
            <div class="sig-line"></div>
            <strong>Lab Technologist</strong><br>
            <span style="font-size: 11px;">Emaqure Medical Centre</span>
        </div>
        <div class="sig-box">
            <div style="height: 60px; border: 1px dashed #eee; margin-bottom: 5px; line-height: 60px; color: #ccc;">Stamp Area</div>
            <div class="sig-line"></div>
            <strong>Pathologist / Authorized</strong>
        </div>
    </div>

    <div class="disclaimer">
        *** End of Report ***<br>
        This is a computer-generated report and does not require a physical signature unless stamped.
    </div>

    <div class="no-print" style="margin-top: 40px; text-align: center;">
        <button onclick="window.print()" style="padding: 12px 30px; background: #0056b3; color: white; border: none; border-radius: 5px; font-weight: bold; cursor:pointer; font-size: 16px;">Print Official Report</button>
    </div>
</div>

</body>
</html>