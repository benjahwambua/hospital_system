<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

// Get date and type filters
$date      = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'); // kept for backward compatibility
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : $date;
$to_date   = isset($_GET['to_date']) ? $_GET['to_date'] : $date;
$type      = isset($_GET['type']) ? $_GET['type'] : 'All';
$method    = isset($_GET['method']) ? $_GET['method'] : 'All';

// Build the type condition for the SQL query
$type_condition = "";
if ($type === 'Clinical') {
    $type_condition = " AND b.patient_id IS NOT NULL ";
} elseif ($type === 'Pharmacy') {
    $type_condition = " AND b.patient_id IS NULL ";
}

// Build payment method condition
$method_condition = "";
if ($method !== 'All') {
    $method_condition = " AND b.method = '" . $conn->real_escape_string($method) . "' ";
}

// Always work with a proper range even if one side is missing.
if (empty($from_date)) {
    $from_date = $date;
}
if (empty($to_date)) {
    $to_date = $from_date;
}

// Daily collected revenue primarily comes from billing because that is where money received is recorded.
// ADD: Pharmacy sales from sell_medicine may exist as paid pharmacy invoices, so we include them too.
$pharmacy_invoice_type_condition = '';
if ($type === 'Clinical') {
    $pharmacy_invoice_type_condition = " AND 1 = 0 ";
} elseif ($type === 'Pharmacy') {
    $pharmacy_invoice_type_condition = " AND 1 = 1 ";
}

$pharmacy_invoice_method_condition = '';
if ($method !== 'All') {
    $pharmacy_invoice_method_condition = " AND 1 = 0 ";
}

$sql = "
    SELECT * FROM (
        SELECT
            CONCAT('billing-', b.id) AS row_key,
            b.id AS billing_id,
            COALESCE(b.invoice_id, i.id, b.id) AS id,
            b.invoice_id,
            b.patient_id,
            COALESCE(CONVERT(i.status USING utf8mb4), CONVERT(b.status USING utf8mb4), CONVERT('paid' USING utf8mb4)) COLLATE utf8mb4_unicode_ci AS status,
            b.created_at,
            CONVERT(p.full_name USING utf8mb4) COLLATE utf8mb4_unicode_ci AS patient_name,
            COALESCE(NULLIF(b.paid_amount, 0), NULLIF(b.amount, 0), 0) AS amount,
            COALESCE(CONVERT(b.method USING utf8mb4), CONVERT('Billing Receipt' USING utf8mb4)) COLLATE utf8mb4_unicode_ci AS payment_method,
            CASE
                WHEN b.patient_id IS NOT NULL THEN CONVERT('Clinical' USING utf8mb4) COLLATE utf8mb4_unicode_ci
                ELSE CONVERT('Pharmacy' USING utf8mb4) COLLATE utf8mb4_unicode_ci
            END AS transaction_type
        FROM billing b
        LEFT JOIN patients p ON b.patient_id = p.id
        LEFT JOIN invoices i ON (
            (b.invoice_id IS NOT NULL AND i.id = b.invoice_id)
            OR (
                b.invoice_id IS NULL
                AND b.patient_id IS NOT NULL
                AND i.patient_id = b.patient_id
                AND DATE(i.created_at) = DATE(b.created_at)
            )
        )
        WHERE DATE(b.created_at) BETWEEN '" . $conn->real_escape_string($from_date) . "' AND '" . $conn->real_escape_string($to_date) . "'
          AND COALESCE(NULLIF(b.paid_amount, 0), NULLIF(b.amount, 0), 0) > 0
          AND (
                b.paid = 1
                OR b.paid = '1'
                OR LOWER(b.paid) = 'yes'
                OR LOWER(b.paid) = 'paid'
              )
          $type_condition
          $method_condition

        UNION ALL

        SELECT
            CONCAT('invoice-', i.id) AS row_key,
            NULL AS billing_id,
            i.id AS id,
            i.id AS invoice_id,
            i.patient_id,
            CONVERT(i.status USING utf8mb4) COLLATE utf8mb4_unicode_ci AS status,
            i.created_at,
            COALESCE(CONVERT(p.full_name USING utf8mb4), CONVERT('Walk-in Customer' USING utf8mb4)) COLLATE utf8mb4_unicode_ci AS patient_name,
            SUM(ii.total) AS amount,
            CONVERT('Cash / Mpesa' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS payment_method,
            CONVERT('Pharmacy' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS transaction_type
        FROM invoices i
        INNER JOIN invoice_items ii ON ii.invoice_id = i.id
        LEFT JOIN patients p ON i.patient_id = p.id
        WHERE DATE(i.created_at) BETWEEN '" . $conn->real_escape_string($from_date) . "' AND '" . $conn->real_escape_string($to_date) . "'
          AND i.patient_id IS NULL
          AND LOWER(i.status) = 'paid'
          $pharmacy_invoice_type_condition
          $pharmacy_invoice_method_condition
          AND NOT EXISTS (
                SELECT 1
                FROM billing b2
                WHERE b2.invoice_id = i.id
                  AND DATE(b2.created_at) BETWEEN '" . $conn->real_escape_string($from_date) . "' AND '" . $conn->real_escape_string($to_date) . "'
                  AND COALESCE(NULLIF(b2.paid_amount, 0), NULLIF(b2.amount, 0), 0) > 0
                  AND (
                        b2.paid = 1
                        OR b2.paid = '1'
                        OR LOWER(b2.paid) = 'yes'
                        OR LOWER(b2.paid) = 'paid'
                      )
            )
        GROUP BY i.id, i.patient_id, i.status, i.created_at, p.full_name
    ) AS collected_revenue
    ORDER BY created_at DESC, id DESC
";

$invoices = $conn->query($sql);

// Calculate totals dynamically using the fetched data to ensure 100% accuracy
// between the table and the summary statistics.
$totalSales = 0;
$clinicalCount = 0;
$pharmacyCount = 0;
$clinicalSales = 0;
$pharmacySales = 0;
$invoices_data = [];

if ($invoices && $invoices->num_rows > 0) {
    while($inv = $invoices->fetch_assoc()) {
        $amount = (float)$inv['amount'];
        $totalSales += $amount;

        if($inv['transaction_type'] === 'Clinical') {
            $clinicalCount++;
            $clinicalSales += $amount;
        } else {
            $pharmacyCount++;
            $pharmacySales += $amount;
        }
        $invoices_data[] = $inv; // Store in array for table loop and export
    }
}
$totalTransactions = count($invoices_data);

// --- ADVANCED FEATURE: CSV Export Logic ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="SalesReport_' . $from_date . '_to_' . $to_date . '_' . $type . '_' . $method . '.csv"');
    $output = fopen('php://output', 'w');

    // Output CSV headers
    fputcsv($output, ['Ref #', 'Customer', 'Type', 'Method', 'Amount Received (KSH)', 'Status', 'Time']);

    // Output rows
    foreach ($invoices_data as $inv) {
        fputcsv($output, [
            $inv['id'],
            $inv['patient_name'] ?? 'Walk-in Customer',
            $inv['transaction_type'],
            $inv['payment_method'],
            number_format($inv['amount'], 2, '.', ''),
            $inv['status'],
            date('H:i', strtotime($inv['created_at']))
        ]);
    }
    fclose($output);
    exit;
}
// ------------------------------------------

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
    margin-bottom: 30px;
    font-size: 14px;
}

.filter-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
    color: #333;
}

.form-group input,
.form-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    min-width: 200px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0,123,255,0.3);
}

.btn {
    padding: 8px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
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

.action-link {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
}

.action-link:hover {
    text-decoration: underline;
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
    <p class="page-subtitle">View daily collected revenue by date range, type and payment method</p>

    <div class="filter-section">
        <div class="form-group">
            <label for="from_date">From Date</label>
            <input type="date" id="from_date" value="<?= htmlspecialchars($from_date); ?>">
        </div>
        <div class="form-group">
            <label for="to_date">To Date</label>
            <input type="date" id="to_date" value="<?= htmlspecialchars($to_date); ?>">
        </div>
        <div class="form-group">
            <label for="report_type">Transaction Type</label>
            <select id="report_type">
                <option value="All" <?= $type === 'All' ? 'selected' : ''; ?>>All Transactions</option>
                <option value="Clinical" <?= $type === 'Clinical' ? 'selected' : ''; ?>>Clinical Only</option>
                <option value="Pharmacy" <?= $type === 'Pharmacy' ? 'selected' : ''; ?>>Pharmacy Only</option>
            </select>
        </div>
        <div class="form-group">
            <label for="payment_method">Method</label>
            <select id="payment_method">
                <option value="All" <?= $method === 'All' ? 'selected' : ''; ?>>All Methods</option>
                <option value="Cash" <?= $method === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                <option value="Mpesa" <?= $method === 'Mpesa' ? 'selected' : ''; ?>>Mpesa</option>
                <option value="M-Pesa" <?= $method === 'M-Pesa' ? 'selected' : ''; ?>>M-Pesa</option>
                <option value="Card" <?= $method === 'Card' ? 'selected' : ''; ?>>Card</option>
                <option value="Credit" <?= $method === 'Credit' ? 'selected' : ''; ?>>Credit</option>
                <option value="Bank" <?= $method === 'Bank' ? 'selected' : ''; ?>>Bank</option>
            </select>
        </div>
        <button class="btn btn-primary" onclick="filterReport()">Filter</button>
        <button class="btn btn-secondary" onclick="printReport()">Print</button>
        <button class="btn btn-success" onclick="exportCSV()">Export CSV</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card sales">
            <div class="stat-label">Total Collected Revenue</div>
            <div class="stat-value">KSH <?= number_format($totalSales, 2); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Receipts</div>
            <div class="stat-value"><?= $totalTransactions; ?></div>
        </div>
        <div class="stat-card clinical">
            <div class="stat-label">Clinical Collections</div>
            <div class="stat-value"><?= $clinicalCount; ?></div>
            <div class="stat-subtext">Rev: KSH <?= number_format($clinicalSales, 2); ?></div>
        </div>
        <div class="stat-card pharmacy">
            <div class="stat-label">Pharmacy Collections</div>
            <div class="stat-value"><?= $pharmacyCount; ?></div>
            <div class="stat-subtext">Rev: KSH <?= number_format($pharmacySales, 2); ?></div>
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Collected Revenue <?= $type !== 'All' ? ' - ' . htmlspecialchars($type) : ''; ?><?= $method !== 'All' ? ' (' . htmlspecialchars($method) . ')' : ''; ?></h2>
        <?php if($totalTransactions > 0): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ref #</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Amount Received (KSH)</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($invoices_data as $inv): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($inv['id']); ?></strong></td>
                        <td><?= htmlspecialchars($inv['patient_name'] ?? 'Walk-in Customer'); ?></td>
                        <td>
                            <span class="type-badge type-<?= strtolower($inv['transaction_type']); ?>">
                                <?= htmlspecialchars($inv['transaction_type']); ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($inv['payment_method'] ?? 'Cash / Mpesa'); ?></td>
                        <td><?= number_format($inv['amount'] ?? 0, 2); ?></td>
                        <td><?= htmlspecialchars($inv['status']); ?></td>
                        <td><?= date('H:i', strtotime($inv['created_at'])); ?></td>
                        <td>
                            <?php if (!empty($inv['invoice_id'])): ?>
                                <a href="/hospital_system/pharmacy/view_invoice.php?id=<?= urlencode($inv['invoice_id']); ?>" class="action-link">View</a>
                            <?php elseif (!empty($inv['patient_id'])): ?>
                                <a href="/hospital_system/patients/patient_dashboard.php?id=<?= urlencode($inv['patient_id']); ?>&tab=billing" class="action-link">Open Patient</a>
                            <?php else: ?>
                                <span style="color:#999;">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="no-data">No collected revenue found for this selection.</div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterReport() {
    const fromDate = document.getElementById('from_date').value;
    const toDate = document.getElementById('to_date').value;
    const type = document.getElementById('report_type').value;
    const method = document.getElementById('payment_method').value;
    window.location.href = `?from_date=${encodeURIComponent(fromDate)}&to_date=${encodeURIComponent(toDate)}&type=${encodeURIComponent(type)}&method=${encodeURIComponent(method)}`;
}

function exportCSV() {
    const fromDate = document.getElementById('from_date').value;
    const toDate = document.getElementById('to_date').value;
    const type = document.getElementById('report_type').value;
    const method = document.getElementById('payment_method').value;
    window.location.href = `?from_date=${encodeURIComponent(fromDate)}&to_date=${encodeURIComponent(toDate)}&type=${encodeURIComponent(type)}&method=${encodeURIComponent(method)}&export=csv`;
}

function printReport() {
    window.print();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
