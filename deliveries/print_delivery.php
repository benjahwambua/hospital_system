<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

// Get delivery ID from query string
$delivery_id = intval($_GET['id'] ?? 0);
if (!$delivery_id) {
    die("Invalid delivery ID.");
}

// Fetch delivery record
$stmt = $conn->prepare("SELECT * FROM deliveries WHERE id = ?");
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$delivery = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$delivery) {
    die("Delivery record not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Report - <?= htmlspecialchars($delivery['mother_name']) ?></title>
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

        /* Delivery Info Section */
        .delivery-info-table { width: 100%; border: 1px solid #ddd; border-collapse: collapse; margin-bottom: 30px; font-size: 13px; z-index: 2; position: relative; }
        .delivery-info-table td { border: 1px solid #ddd; padding: 8px 12px; }
        .label { background: #f9f9f9; font-weight: bold; width: 25%; }
        .value { width: 25%; }

        .report-title { text-align: center; text-decoration: underline; text-transform: uppercase; font-size: 18px; margin-bottom: 25px; color: #333; }

        /* Details Section */
        .details-section { margin-bottom: 40px; }
        .details-section h4 { color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 5px; margin-bottom: 15px; }

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
                    Phone: +254 712 345 678 | Email: info@emaqure.com<br>
                    P.O. Box 12345-00100, Nairobi, Kenya
                </div>
            </td>
        </tr>
    </table>

    <h2 class="report-title">Delivery Report</h2>

    <table class="delivery-info-table">
        <tr>
            <td class="label">Delivery ID:</td>
            <td class="value"><?= htmlspecialchars($delivery['id']) ?></td>
            <td class="label">Delivery Date:</td>
            <td class="value"><?= htmlspecialchars(date('d/m/Y', strtotime($delivery['delivery_date']))) ?></td>
        </tr>
        <tr>
            <td class="label">Mother Name:</td>
            <td class="value" colspan="3"><?= htmlspecialchars($delivery['mother_name']) ?></td>
        </tr>
        <tr>
            <td class="label">Delivery Type:</td>
            <td class="value"><?= htmlspecialchars($delivery['delivery_type']) ?></td>
            <td class="label">Baby Weight:</td>
            <td class="value"><?= htmlspecialchars($delivery['baby_weight']) ?> KG</td>
        </tr>
    </table>

    <div class="details-section">
        <h4>Additional Information</h4>
        <p><strong>Remarks:</strong></p>
        <p style="white-space: pre-line;"><?= htmlspecialchars($delivery['remarks'] ?: 'No remarks recorded.') ?></p>
    </div>

    <div class="report-footer">
        <p><strong>Report Generated:</strong> <?= date('d/m/Y H:i:s') ?></p>
        <p><strong>Generated By:</strong> <?= htmlspecialchars($_SESSION['user_name'] ?? 'System') ?></p>
    </div>

    <div class="sig-container">
        <div class="sig-box">
            <div class="sig-line"></div>
            <p>Doctor/Midwife Signature</p>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <p>Mother Signature</p>
        </div>
    </div>

    <div class="disclaimer">
        This is a computer-generated report. Please verify all information before use.
    </div>

</div>

<script>
    window.onload = function() {
        window.print();
    }
</script>

</body>
</html>