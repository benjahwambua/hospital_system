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

// Fetch invoice items
$stmt = $conn->prepare("SELECT description, quantity, price, total as amount FROM invoice_items WHERE invoice_id = ?");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$billing = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
/* Global Styling */
.invoice-container {
    max-width: 850px;
    margin: 20px auto;
    padding: 20px;
}

.invoice-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 40px;
    position: relative;
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    border-bottom: 2px solid #333;
    padding-bottom: 20px;
    margin-bottom: 20px;
}

.invoice-title { font-size: 32px; font-weight: bold; color: #333; margin: 0; }
.invoice-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
.meta-label { font-size: 11px; color: #777; text-transform: uppercase; display: block; }
.meta-value { font-size: 15px; font-weight: 600; color: #333; }

.invoice-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
.invoice-table th { background: #f4f4f4; padding: 10px; border: 1px solid #ddd; text-align: left; }
.invoice-table td { padding: 10px; border: 1px solid #ddd; }
.amount-col { text-align: right; }

.total-section { display: flex; justify-content: flex-end; margin-top: 20px; }
.total-box { border: 2px solid #333; padding: 15px 25px; text-align: right; min-width: 250px; }
.total-label { font-size: 12px; font-weight: bold; }
.total-amount { font-size: 24px; font-weight: 800; }

/* Action Buttons (Hidden on Print) */
.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 30px;
    padding: 15px;
    background: #eee;
    border-radius: 8px;
}

.btn {
    padding: 10px 20px;
    border-radius: 5px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
}
.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-secondary { background: #6c757d; color: white; }

/* PRINT LOGIC: THIS IS THE KEY PART */
@media print {
    /* Hide the sidebar, header, and footer provided by includes */
    header, .sidebar, .navbar, footer, .action-buttons, .main-footer {
        display: none !important;
    }
    
    /* Reset margins for the print page */
    body { background: white; margin: 0; padding: 0; }
    .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
    .invoice-container { max-width: 100%; margin: 0; padding: 0; }
    .invoice-card { border: none; box-shadow: none; padding: 0; }
    
    /* Ensure colors print correctly */
    .invoice-header { border-bottom: 2px solid #000 !important; }
    .invoice-table th { background: #eee !important; -webkit-print-color-adjust: exact; }
}
</style>

<div class="invoice-container">
    <div class="invoice-card">
        
        <div class="invoice-header">
            <div>
                <h1 class="invoice-title">INVOICE</h1>
                <span class="meta-label">Serial Number</span>
                <span class="meta-value">#INV-<?= str_pad($invoice['id'], 5, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div style="text-align: right;">
                <h2 style="margin:0;"><?= SITE_NAME; ?></h2>
                <p style="margin:0; font-size:12px; color:#666;">Hospital Management System</p>
            </div>
        </div>

        <div class="invoice-meta">
            <div>
                <span class="meta-label">Billing To:</span>
                <span class="meta-value"><?= htmlspecialchars($patient_name); ?></span>
            </div>
            <div style="text-align: right;">
                <span class="meta-label">Date Issued:</span>
                <span class="meta-value"><?= date("d M, Y H:i", strtotime($invoice['created_at'])); ?></span>
            </div>
            <div>
                <span class="meta-label">Payment Status:</span>
                <span class="meta-value" style="color: <?= $invoice['status'] == 'Paid' ? '#28a745' : '#dc3545'; ?>;">
                    <?= strtoupper(htmlspecialchars($invoice['status'])); ?>
                </span>
            </div>
            <div style="text-align: right;">
                <span class="meta-label">Invoice Type:</span>
                <span class="meta-value"><?= !empty($invoice['patient_id']) ? 'In-Patient' : 'Out-Patient/Walk-in'; ?></span>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Service/Item Description</th>
                    <th>Qty</th>
                    <th class="amount-col">Price (KSH)</th>
                    <th class="amount-col">Total (KSH)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                $total = 0;
                while ($row = $billing->fetch_assoc()):
                    $total += $row['amount'];
                ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= htmlspecialchars($row['description']); ?></td>
                    <td><?= (int)$row['quantity']; ?></td>
                    <td class="amount-col"><?= number_format($row['price'], 2); ?></td>
                    <td class="amount-col"><?= number_format($row['amount'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-box">
                <span class="total-label">AMOUNT DUE:</span>
                <div class="total-amount">KSH <?= number_format($total, 2); ?></div>
            </div>
        </div>

        <?php if(!empty($invoice['notes'])): ?>
        <div style="margin-top: 30px; font-size: 12px; border-top: 1px solid #eee; padding-top: 10px;">
            <strong>Remarks:</strong> <?= htmlspecialchars($invoice['notes']); ?>
        </div>
        <?php endif; ?>

        <div style="margin-top: 50px; text-align: center; font-size: 11px; color: #aaa;">
            Thank you for choosing <?= SITE_NAME; ?>. This is a computer-generated invoice.
        </div>
    </div>

    <div class="action-buttons">
        <button onclick="window.print()" class="btn btn-primary">
            Print Official Invoice
        </button>
        
        <?php if($patient_id): ?>
        <a href="/hospital_system/patients/patient_dashboard.php?id=<?= $patient_id; ?>" class="btn btn-success">
            Patient Profile
        </a>
        <?php endif; ?>
        
        <a href="/hospital_system/pharmacy/sell_medicine.php" class="btn btn-secondary">
            New Sale
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>