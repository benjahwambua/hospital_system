<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patient_id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT p.*, u.full_name as doctor_name FROM patients p LEFT JOIN users u ON p.doctor_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$p_res = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Report - <?= htmlspecialchars($p_res['patient_number'] ?? 'N/A') ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        /* Base Styles */
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f4f4f4; color: #333; margin: 0; }
        
        /* A4 Page Container */
        .report-card { 
            width: 210mm; 
            min-height: 297mm; 
            margin: 20px auto; 
            background: #fff; 
            padding: 20mm; 
            position: relative; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }

        .watermark { position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); opacity: 0.04; width: 70%; z-index: 0; pointer-events: none; }
        
        /* Header & Branding */
        .hospital-branding { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #0056b3; padding-bottom: 15px; margin-bottom: 20px; position: relative; z-index: 1; }
        .hospital-details { text-align: right; }
        .hospital-details h2 { margin: 0; color: #0056b3; font-size: 22px; }
        .hospital-details p { margin: 2px 0; font-size: 13px; }
        
        /* Patient Info Box */
        .patient-details { display: grid; grid-template-columns: 1fr 1fr; background: #f9f9f9; padding: 15px; border: 1px solid #eee; border-radius: 5px; margin-bottom: 25px; position: relative; z-index: 1; }
        .patient-details p { margin: 4px 0; font-size: 14px; }

        /* Clinical Records */
        .encounter-block { margin-bottom: 20px; border: 1px solid #e0e0e0; border-radius: 6px; overflow: hidden; page-break-inside: avoid; position: relative; z-index: 1; }
        .encounter-header { background: #f0f7ff; padding: 10px 15px; font-weight: bold; font-size: 14px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; }
        
        .clinical-section { padding: 12px 15px; }
        .clinical-label { font-weight: bold; color: #0056b3; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 5px; }
        .clinical-text { font-size: 13.5px; line-height: 1.5; white-space: pre-line; color: #444; }

        /* Footer & Signatures */
        .report-footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; position: relative; z-index: 1; }
        .signature-section { text-align: center; width: 220px; }
        .signature-line { border-top: 1px solid #444; margin-bottom: 8px; }
        .stamp-circle { width: 85px; height: 85px; border: 2px dashed #0056b3; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: #0056b3; font-size: 9px; font-weight: bold; opacity: 0.3; text-transform: uppercase; }
        
        /* Print Overrides */
        @media print {
            @page { size: A4; margin: 0; }
            body { background: #fff; padding: 0; }
            .report-card { margin: 0; box-shadow: none; width: 100%; border: none; }
            .no-print { display: none; }
            .encounter-block { border: 1px solid #ccc; } /* Sharper border for ink */
        }
    </style>
</head>
<body>

<div class="report-card">
    <img src="../assets/img/logo.png" class="watermark" alt="Watermark">

    <div class="hospital-branding">
        <img src="../assets/img/logo.png" style="max-height: 75px;" alt="Logo">
        <div class="hospital-details">
            <h2>EMAQURE MEDICAL CENTRE</h2>
            <p>Biashara Street, Mlolongo</p>
            <p>Tel: +254793069565</p>
        </div>
    </div>

    <div class="patient-details">
        <div>
            <p><strong>Patient Name:</strong> <?= htmlspecialchars($p_res['full_name'] ?? 'N/A') ?></p>
            <p><strong>Patient No:</strong> <?= htmlspecialchars($p_res['patient_number'] ?? 'N/A') ?></p>
        </div>
        <div style="text-align: right;">
            <p><strong>Age/Gender:</strong> <?= htmlspecialchars($p_res['age'] ?? '--') ?>Y | <?= htmlspecialchars($p_res['gender'] ?? '--') ?></p>
            <p><strong>Date of Issue:</strong> <?= date('d M Y') ?></p>
        </div>
    </div>

    <h3 style="text-align: center; font-size: 18px; margin-bottom: 20px; color: #222; text-transform: uppercase; letter-spacing: 1px;">Medical Examination Report</h3>
    
    <?php 
    $enc_sql = "SELECT e.*, u.full_name as doc_name FROM encounters e 
                LEFT JOIN users u ON e.doctor_id = u.id 
                WHERE e.patient_id = $patient_id 
                ORDER BY e.created_at DESC";
    $enc_res = $conn->query($enc_sql);
    
    if ($enc_res && $enc_res->num_rows > 0):
        while($e = $enc_res->fetch_assoc()): ?>
            <div class="encounter-block">
                <div class="encounter-header">
                    <span>Encounter Date: <?= date('d/m/Y', strtotime($e['created_at'])) ?></span>
                    <span>Physician: Dr. <?= htmlspecialchars($e['doc_name'] ?? 'Medical Officer') ?></span>
                </div>

                <div class="clinical-section" style="border-bottom: 1px solid #f0f0f0;">
                    <span class="clinical-label">Primary Diagnosis</span>
                    <div class="clinical-text"><strong><?= htmlspecialchars($e['diagnosis'] ?: 'Clinical Assessment Pending') ?></strong></div>
                </div>

                <?php if(!empty($e['presenting_complaint'])): ?>
                <div class="clinical-section" style="border-bottom: 1px solid #f0f0f0;">
                    <span class="clinical-label">Clinical Presentation</span>
                    <div class="clinical-text"><?= htmlspecialchars($e['presenting_complaint']) ?></div>
                </div>
                <?php endif; ?>

                <div class="clinical-section">
                    <span class="clinical-label">Clinical Observations & Management</span>
                    <div class="clinical-text">
                        <?= nl2br(htmlspecialchars($e['management_plan'] . "\n" . $e['doctor_notes'])) ?>
                    </div>
                </div>
            </div>
        <?php endwhile; 
    else: ?>
        <p style="text-align: center; color: #888; margin-top: 50px; font-style: italic;">No clinical records available for this patient.</p>
    <?php endif; ?>

    <div class="report-footer">
        <div class="qr-section" style="text-align: center;">
            <div id="qrcode"></div>
            <p style="font-size: 9px; margin-top: 8px; color: #777;">Verify Authenticity</p>
        </div>
        <div class="signature-section">
            <div class="stamp-circle">Official Stamp</div>
            <div class="signature-line"></div>
            <p style="font-size: 13px; font-weight: bold; margin: 0;">Authorized Signature</p>
            <p style="font-size: 11px; color: #666;">EMAQURE MEDICAL CENTRE</p>
        </div>
    </div>

    <div class="no-print" style="margin-top: 50px; text-align: center; border-top: 1px dashed #ccc; padding-top: 20px;">
        <button onclick="window.print()" style="padding: 12px 30px; background:#0056b3; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight: bold; font-size: 14px;">Print Report (A4)</button>
    </div>
</div>

<script>
    new QRCode(document.getElementById("qrcode"), {
        text: "Patient: <?= $p_res['full_name'] ?> | No: <?= $p_res['patient_number'] ?> | Verify: <?= date('Ymd') ?>",
        width: 80, height: 80,
        correctLevel: QRCode.CorrectLevel.H
    });
</script>
</body>
</html>