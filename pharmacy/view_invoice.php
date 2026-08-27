<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

// Get invoice ID from query string
$invoice_id = intval($_GET['id'] ?? 0);
if (!$invoice_id) {
    die("Invalid invoice ID.");
}

// Fetch invoice
$stmt = $conn->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    die("Invoice not found.");
}

// Determine patient or walk-in
$patient_name = 'Walk-in Customer';
$patient_id = null;
if (!empty($invoice['patient_id'])) {
    $stmt = $conn->prepare("SELECT full_name FROM patients WHERE id = ?");
    $stmt->bind_param("i", $invoice['patient_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result) {
        $patient_name = $result['full_name'];
        $patient_id = $invoice['patient_id'];
    }
    $stmt->close();
} elseif (!empty($invoice['walkin_id'])) {
    $stmt = $conn->prepare("SELECT full_name FROM walkin_customers WHERE id = ?");
    $stmt->bind_param("i", $invoice['walkin_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result) {
        $patient_name = $result['full_name'];
    }
    $stmt->close();
}

// --- ADDITION: Fetch ALL billed items for this patient session ---
$items_array = [];

// 1. Fetch items specifically linked to this invoice
$stmt = $conn->prepare("SELECT description, quantity, price, total as amount FROM invoice_items WHERE invoice_id = ? AND description NOT LIKE '%Payment%'");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) {
    $items_array[] = $row;
}
$stmt->close();


// 2. If it's a patient AND invoice_items is empty, fetch Services (including LAB), Prescriptions, and Consultation
if ($patient_id && empty($items_array)) {
    // Fetch ALL Services (both regular and lab) - get ALL statuses except Cancelled
    $svc_res = $conn->query("SELECT sm.service_name as description, 1 as quantity, ps.price, ps.price as amount 
                             FROM patient_services ps 
                             JOIN services_master sm ON ps.service_id = sm.id 
                             WHERE ps.patient_id = $patient_id 
                             AND (ps.status IS NULL OR ps.status NOT IN ('Cancelled', 'Deleted'))");
    if($svc_res) {
        while($row = $svc_res->fetch_assoc()) {
            $items_array[] = $row;
        }
    }

    // Fetch Prescriptions
    $rx_res = $conn->query("SELECT s.drug_name as description, pr.quantity, s.selling_price as price, (pr.quantity * s.selling_price) as amount 
                            FROM prescriptions pr 
                            JOIN pharmacy_stock s ON pr.medicine_id = s.id 
                            WHERE pr.patient_id = $patient_id");
    if($rx_res) {
        while($row = $rx_res->fetch_assoc()) {
            $items_array[] = $row;
        }
    }
    
    // Add Consultation Fee (Check for existing to avoid visual doubling)
    $has_cons = false;
    foreach($items_array as $item) { 
        if(stripos($item['description'], 'Consultation') !== false) {
            $has_cons = true;
            break;
        }
    }
    if(!$has_cons) {
        $items_array[] = ['description' => 'Consultation Fee', 'quantity' => 1, 'price' => 200, 'amount' => 200];
    }
}

// --- ADDITION: Fetch Payment History for Balance Calculation ---
$total_paid = 0;
if ($patient_id) {
    $pay_stmt = $conn->prepare("SELECT SUM(amount) as paid_sum FROM billing WHERE patient_id = ?");
    $pay_stmt->bind_param("i", $patient_id);
    $pay_stmt->execute();
    $pay_res = $pay_stmt->get_result()->fetch_assoc();
    $total_paid = $pay_res['paid_sum'] ?? 0;
    $pay_stmt->close();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
/* --- HOSPITAL BRANDING STYLES --- */
.hospital-branding {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    position: relative;
    z-index: 2;
}
.hospital-logo img {
    max-height: 75px;
    width: auto;
}
.hospital-details {
    text-align: right;
}
.hospital-details h2 {
    margin: 0;
    color: #333;
    font-size: 22px;
    text-transform: uppercase;
}
.hospital-details p {
    margin: 2px 0;
    font-size: 13px;
    color: #555;
}

/* --- WATERMARK STYLING --- */
.invoice-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
    position: relative;
    overflow: hidden;
}

.watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);
    opacity: 0.06;
    width: 60%;
    pointer-events: none;
    z-index: 0;
}

/* --- YOUR ORIGINAL STYLING --- */
.invoice-container {
    max-width: 900px;
    margin: 30px auto;
    padding: 0 20px;
}
.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #007bff;
    position: relative;
    z-index: 2;
}
.invoice-title {
    font-size: 28px;
    color: #007bff;
    margin: 0;
}
.invoice-number {
    font-size: 14px;
    color: #666;
}
.invoice-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: rgba(248, 249, 250, 0.8);
    border-radius: 6px;
    position: relative;
    z-index: 2;
}
.meta-label {
    font-size: 12px;
    font-weight: 700;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 5px;
}
.meta-value {
    font-size: 16px;
    color: #333;
    font-weight: 500;
}
.table-container {
    margin: 30px 0;
    position: relative;
    z-index: 2;
}
.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    background: transparent;
}
.invoice-table thead {
    background: #007bff;
    color: white;
}
.invoice-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
}
.invoice-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}
.amount-col {
    text-align: right;
}
.total-section {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 30px;
    position: relative;
    z-index: 2;
}
.total-box {
    width: 350px;
    padding: 20px;
    background: #f8f9fa;
    color: #333;
    border-radius: 6px;
    border: 1px solid #ddd;
    text-align: right;
}
.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 15px;
}
.total-final {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 2px solid #007bff;
    font-size: 22px;
    font-weight: 700;
    color: #007bff;
}

/* --- NEW SIGNATURE & QR STYLES --- */
.invoice-extra {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 50px;
    padding: 0 10px;
    position: relative;
    z-index: 2;
}
.qr-section {
    text-align: center;
    font-size: 10px;
    color: #666;
}
.signature-section {
    text-align: center;
    width: 250px;
}
.signature-line {
    border-top: 1px solid #333;
    margin-bottom: 5px;
}
.stamp-circle {
    width: 100px;
    height: 100px;
    border: 2px dashed #007bff;
    border-radius: 50%;
    margin: 0 auto 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #007bff;
    font-size: 10px;
    font-weight: bold;
    opacity: 0.3;
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #eee;
}
.btn {
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
}
.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn-success { background: #28a745; color: white; }

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}
.status-paid { background: #d4edda; color: #155724; }
.status-unpaid { background: #f8d7da; color: #721c24; }

.invoice-footer {
    margin-top: 40px;
    padding-top: 15px;
    border-top: 1px dashed #ddd;
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #777;
    position: relative;
    z-index: 2;
}

/* --- THE "FIX EVERYTHING" PRINT OVERRIDE --- */
@media print {
    /* Hide everything except our invoice */
    header, footer, nav, aside, .sidebar, .navbar, .action-buttons, .main-footer, .btn {
        display: none !important;
    }

    /* Force the main wrappers to behave like a standard page */
    html, body {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
    }

    /* Override dashboard layout classes (Commonly .content-wrapper or .main-content) */
    .content-wrapper, .main-content, .container-fluid, .content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        min-width: 100% !important;
        position: static !important;
        display: block !important;
    }

    /* Expand the invoice container to fill the A4 width */
    .invoice-container {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 10mm !important; /* Standard spacing for print */
    }

    .invoice-card {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .invoice-table {
        width: 100% !important;
    }

    /* Ensure headers print with color */
    .invoice-table thead {
        background-color: #007bff !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .watermark {
        opacity: 0.05 !important;
    }
}
</style>

<div class="invoice-container">
    <div class="invoice-card">
        
        <img src="/hospital_system/assets/img/logo.png" class="watermark" alt="Watermark" onerror="this.style.display='none'">

        <div class="hospital-branding">
            <div class="hospital-logo">
                <img src="/hospital_system/assets/img/logo.png" alt="Emaqure Logo" onerror="this.style.display='none'">
            </div>
            <div class="hospital-details">
                <p>Biashara Street, Opposite Old</p> 
                <p>Naiwe School, Mlolongo</p>
                <p>Contact: +254793069565</p>
                <p>emaquremedicalcentre@gmail.com</p>
            </div>
        </div>
        
        <div class="invoice-header">
            <div>
                <h1 class="invoice-title">OFFICIAL INVOICE</h1>
                <p class="invoice-number">Invoice #<?= htmlspecialchars($invoice['id']); ?></p>
            </div>
            <div class="status-badge status-<?= strtolower($invoice['status']); ?>">
                <?= htmlspecialchars($invoice['status']); ?>
            </div>
        </div>

        <div class="invoice-meta">
            <div class="meta-item">
                <span class="meta-label">Customer Name</span>
                <span class="meta-value"><?= htmlspecialchars($patient_name); ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Invoice Date</span>
                <span class="meta-value"><?= date("d-m-Y H:i", strtotime($invoice['created_at'])); ?></span>
            </div>
        </div>

        <div class="table-container">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th class="amount-col">Price (KSH)</th>
                        <th class="amount-col">Amount (KSH)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    $grand_total = 0;
                    foreach ($items_array as $row):
                        $grand_total += $row['amount'];
                    ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= htmlspecialchars($row['description']); ?></td>
                            <td class="amount-col"><?= (int)$row['quantity']; ?></td>
                            <td class="amount-col"><?= number_format($row['price'] ?? 0, 2); ?></td>
                            <td class="amount-col"><?= number_format($row['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>Total Bill:</span>
                    <span>KSH <?= number_format($grand_total, 2); ?></span>
                </div>
                <div class="total-row" style="color: #28a745; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                    <span>Amount Paid:</span>
                    <span>- KSH <?= number_format($total_paid, 2); ?></span>
                </div>
                <div class="total-final">
                    <div style="font-size: 11px; color: #666; font-weight: 400; text-transform: uppercase; letter-spacing: 1px;">Outstanding Balance</div>
                    <span>KSH <?= number_format($grand_total - $total_paid, 2); ?></span>
                </div>
            </div>
        </div>

        <?php if(!empty($invoice['notes'])): ?>
        <div class="notes-section">
            <div class="notes-label" style="position:relative; z-index:2;">Notes:</div>
            <div class="notes-text" style="position:relative; z-index:2;"><?= htmlspecialchars($invoice['notes']); ?></div>
        </div>
        <?php endif; ?>

        <div class="invoice-extra">
            <div class="qr-section">
                <div id="qrcode"></div>
                <p>Scan to verify</p>
            </div>
            <div class="signature-section">
                <div class="stamp-circle">OFFICIAL STAMP</div>
                <div class="signature-line"></div>
                <p style="font-size: 12px; color: #333; margin:0;">Authorized Signature</p>
            </div>
        </div>

        <div class="invoice-footer">
            <span>Generated By: <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'System Administrator'); ?></strong></span>
            <span>Printed On: <?= date("d-m-Y H:i:s"); ?></span>
        </div>

        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
            <?php if($patient_id): ?>
                <a href="/hospital_system/patients/patient_dashboard.php?id=<?= $patient_id; ?>" class="btn btn-success">View Patient Profile</a>
            <?php endif; ?>
            <a href="/hospital_system/pharmacy/sell_medicine.php" class="btn btn-secondary">Back to Sales</a>
        </div>
    </div>
</div>

<script>
    var qrData = "Invoice: <?= $invoice['id']; ?>\nPatient: <?= $patient_name; ?>\nBalance: KSH <?= number_format($grand_total - $total_paid, 2); ?>";
    new QRCode(document.getElementById("qrcode"), {
        text: qrData,
        width: 100,
        height: 100,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
