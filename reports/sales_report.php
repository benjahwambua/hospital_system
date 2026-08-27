<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

// Get date filters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
$to_date   = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Ensure valid date range
if (empty($from_date)) $from_date = date('Y-m-d');
if (empty($to_date)) $to_date = $from_date;
if ($from_date > $to_date) $to_date = $from_date;

// Query to get all payment entries (revenue collected) from accounting_entries
$sql = "
    SELECT
        ae.created_at,
        ae.account AS payment_method,
        ae.debit AS amount,
        ae.note,
        COALESCE(i.id, ae.invoice_id) AS invoice_id,
        COALESCE(p.full_name, 'Walk-in Customer') AS patient_name,
        CASE
            WHEN i.patient_id IS NOT NULL THEN 'Clinical'
            ELSE 'Pharmacy'
        END AS transaction_type
    FROM accounting_entries ae
    LEFT JOIN invoices i ON ae.invoice_id = i.id
    LEFT JOIN patients p ON i.patient_id = p.id
    WHERE ae.account IN ('Cash', 'M-Pesa', 'Bank')
      AND ae.debit > 0
      AND DATE(ae.created_at) BETWEEN ? AND ?
    ORDER BY ae.created_at DESC, ae.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $from_date, $to_date);
$stmt->execute();
$payments = $stmt->get_result();

// Calculate totals
$totalRevenue = 0;
$paymentMethodTotals = [];
$transactionTypeTotals = ['Clinical' => 0, 'Pharmacy' => 0];
$payments_data = [];

while ($payment = $payments->fetch_assoc()) {
    $amount = (float)$payment['amount'];
    $totalRevenue += $amount;
    
    $method = $payment['payment_method'];
    if (!isset($paymentMethodTotals[$method])) {
        $paymentMethodTotals[$method] = 0;
    }
    $paymentMethodTotals[$method] += $amount;
    
    $type = $payment['transaction_type'];
    $transactionTypeTotals[$type] += $amount;
    
    $payments_data[] = $payment;
}

$totalTransactions = count($payments_data);

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="SalesReport_' . $from_date . '_to_' . $to_date . '.csv"');
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Date', 'Time', 'Invoice ID', 'Customer', 'Type', 'Payment Method', 'Amount (KSH)', 'Note']);
    
    foreach ($payments_data as $payment) {
        fputcsv($output, [
            date('Y-m-d', strtotime($payment['created_at'])),
            date('H:i:s', strtotime($payment['created_at'])),
            $payment['invoice_id'] ?: 'N/A',
            $payment['patient_name'],
            $payment['transaction_type'],
            $payment['payment_method'],
            number_format($payment['amount'], 2),
            $payment['note']
        ]);
    }
    fclose($output);
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
.main-content {
    padding: 30px 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.page-title {
    font-size: 28px;
    color: #007bff;
    margin-bottom: 10px;
}

.page-subtitle {
    color: #666;
    margin-bottom: 20px;
}

.filter-section {
    display: flex;
    gap: 15px;
    align-items: end;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}

.form-group input,
.form-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    min-width: 150px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    margin-right: 10px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-success {
    background: #28a745;
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-left: 4px solid #007bff;
}

.stat-label {
    font-size: 12px;
    font-weight: 700;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #007bff;
}

.stat-subtext {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
    font-weight: 500;
}

.stat-card.sales {
    border-left-color: #28a745;
}

.stat-card.sales .stat-value {
    color: #28a745;
}

.stat-card.clinical {
    border-left-color: #17a2b8;
}

.stat-card.clinical .stat-value {
    color: #17a2b8;
}

.stat-card.pharmacy {
    border-left-color: #ffc107;
}

.stat-card.pharmacy .stat-value {
    color: #ffc107;
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #007bff;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #007bff;
}

.card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table thead {
    background: #007bff;
    color: white;
}

.table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
}

.table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.table tbody tr:hover {
    background: #f8f9fa;
}

.type-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.type-clinical {
    background: #d1ecf1;
    color: #0c5460;
}

.type-pharmacy {
    background: #fff3cd;
    color: #856404;
}

.no-data {
    padding: 40px 20px;
    text-align: center;
    color: #999;
    font-size: 16px;
}

.table-responsive {
    overflow-x: auto;
}

@media (max-width: 768px) {
    .filter-section {
        flex-direction: column;
    }

    .form-group {
        width: 100%;
    }

    .form-group input,
    .form-group select {
        min-width: 100%;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .table {
        font-size: 12px;
    }

    .table th,
    .table td {
        padding: 8px;
    }

    .type-badge {
        font-size: 10px;
    }
}

@media print {
    .filter-section {
        display: none;
    }
    .btn {
        display: none;
    }
}
</style>

<div class="main-content">
    <h1 class="page-title">Daily Sales Report</h1>
    <p class="page-subtitle">View total revenue collected by date range</p>

    <div class="filter-section">
        <div class="form-group">
            <label for="from_date">From Date</label>
            <input type="date" id="from_date" value="<?= htmlspecialchars($from_date); ?>">
        </div>
        <div class="form-group">
            <label for="to_date">To Date</label>
            <input type="date" id="to_date" value="<?= htmlspecialchars($to_date); ?>">
        </div>
        <button class="btn btn-primary" onclick="filterReport()">Filter</button>
        <button class="btn btn-secondary" onclick="printReport()">Print</button>
        <button class="btn btn-success" onclick="exportCSV()">Export CSV</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card sales">
            <div class="stat-label">Total Collected Revenue</div>
            <div class="stat-value">KSH <?= number_format($totalRevenue, 2); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Payments</div>
            <div class="stat-value"><?= $totalTransactions; ?></div>
        </div>
        <div class="stat-card clinical">
            <div class="stat-label">Clinical Revenue</div>
            <div class="stat-value">KSH <?= number_format($transactionTypeTotals['Clinical'], 2); ?></div>
        </div>
        <div class="stat-card pharmacy">
            <div class="stat-label">Pharmacy Revenue</div>
            <div class="stat-value">KSH <?= number_format($transactionTypeTotals['Pharmacy'], 2); ?></div>
        </div>
    </div>

    <?php if (!empty($paymentMethodTotals)): ?>
    <div class="card">
        <h2 class="section-title">Revenue by Payment Method</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Payment Method</th>
                        <th>Total Amount (KSH)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($paymentMethodTotals as $method => $amount): ?>
                    <tr>
                        <td><?= htmlspecialchars($method); ?></td>
                        <td><?= number_format($amount, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2 class="section-title">Payment Details (<?= htmlspecialchars($from_date); ?> to <?= htmlspecialchars($to_date); ?>)</h2>
        <?php if($totalTransactions > 0): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Invoice ID</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Payment Method</th>
                        <th>Amount (KSH)</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($payments_data as $payment): ?>
                    <tr>
                        <td><?= date('Y-m-d', strtotime($payment['created_at'])); ?></td>
                        <td><?= date('H:i:s', strtotime($payment['created_at'])); ?></td>
                        <td><strong><?= htmlspecialchars($payment['invoice_id'] ?: 'N/A'); ?></strong></td>
                        <td><?= htmlspecialchars($payment['patient_name']); ?></td>
                        <td>
                            <span class="type-badge type-<?= strtolower($payment['transaction_type']); ?>">
                                <?= htmlspecialchars($payment['transaction_type']); ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($payment['payment_method']); ?></td>
                        <td><?= number_format($payment['amount'], 2); ?></td>
                        <td><?= htmlspecialchars($payment['note']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="no-data">
            No payments found for the selected date range (<?= htmlspecialchars($from_date); ?> to <?= htmlspecialchars($to_date); ?>).
            <br><small>This could mean no payments were recorded in the accounting system for these dates.</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterReport() {
    const fromDate = document.getElementById('from_date').value;
    const toDate = document.getElementById('to_date').value;
    
    let params = [];
    if (fromDate) params.push('from_date=' + encodeURIComponent(fromDate));
    if (toDate) params.push('to_date=' + encodeURIComponent(toDate));
    
    const url = params.length > 0 ? '?' + params.join('&') : '?';
    window.location.href = url;
}

function exportCSV() {
    const fromDate = document.getElementById('from_date').value;
    const toDate = document.getElementById('to_date').value;
    
    let params = ['export=csv'];
    if (fromDate) params.push('from_date=' + encodeURIComponent(fromDate));
    if (toDate) params.push('to_date=' + encodeURIComponent(toDate));
    
    window.location.href = '?' + params.join('&');
}

function printReport() {
    window.print();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
