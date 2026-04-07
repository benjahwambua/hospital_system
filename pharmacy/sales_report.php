<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// Date filter
$date = $_GET['date'] ?? date('Y-m-d');

// Fetch sales summary
$stmt = $conn->prepare("
    SELECT payment_mode, SUM(total) AS amount
    FROM invoices
    WHERE DATE(created_at) = ?
    GROUP BY payment_mode
");
$stmt->bind_param("s", $date);
$stmt->execute();
$res = $stmt->get_result();

$sales_summary = [
    'cash' => 0,
    'mpesa' => 0,
    'credit' => 0
];

while($row = $res->fetch_assoc()){
    $mode = strtolower($row['payment_mode']);
    if(isset($sales_summary[$mode])) $sales_summary[$mode] = $row['amount'];
}

$total_sales = array_sum($sales_summary);

// Fetch detailed invoices
$stmt2 = $conn->prepare("
    SELECT i.id, i.invoice_no, i.total, i.payment_mode, 
           p.full_name AS patient_name, i.created_at
    FROM invoices i
    LEFT JOIN patients p ON p.id = i.patient_id
    WHERE DATE(i.created_at) = ?
    ORDER BY i.payment_mode, i.created_at ASC
");
$stmt2->bind_param("s", $date);
$stmt2->execute();
$invoices = $stmt2->get_result();

// Prepare chart data
$chart_labels = ['Cash', 'M-Pesa', 'Credit'];
$chart_data = [
    $sales_summary['cash'],
    $sales_summary['mpesa'],
    $sales_summary['credit']
];
?>

<div class="content-area mt-4">
    <h2>Daily Sales Report - <?= date('d M Y', strtotime($date)) ?></h2>

    <!-- Date Filter -->
    <form method="GET" class="mb-3 d-flex align-items-center gap-2">
        <label for="date">Select Date:</label>
        <input type="date" id="date" name="date" value="<?= htmlspecialchars($date) ?>" class="form-control" style="max-width:200px;">
        <button class="btn btn-primary">Filter</button>
    </form>

    <!-- Sales Summary -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Sales Summary</div>
        <div class="card-body">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Payment Mode</th>
                        <th>Amount Received (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Cash</td><td><?= number_format($sales_summary['cash'],2) ?></td></tr>
                    <tr><td>M-Pesa</td><td><?= number_format($sales_summary['mpesa'],2) ?></td></tr>
                    <tr><td>Credit</td><td><?= number_format($sales_summary['credit'],2) ?></td></tr>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th>Total Sales</th>
                        <th><?= number_format($total_sales,2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Sales Chart -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">Sales by Payment Mode</div>
        <div class="card-body">
            <canvas id="salesChart" style="max-height:300px;"></canvas>
        </div>
    </div>

    <!-- Detailed Invoices -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">Invoice Details</div>
        <div class="card-body">
            <?php
            $current_mode = '';
            $mode_total = 0;
            $grand_total = 0;
            while($inv = $invoices->fetch_assoc()):
                if($inv['payment_mode'] !== $current_mode):
                    if($current_mode !== ''): ?>
                        <tr class="table-secondary">
                            <th colspan="4">Subtotal (<?= ucfirst($current_mode) ?>)</th>
                            <th><?= number_format($mode_total,2) ?></th>
                        </tr>
                    </tbody>
                    </table>
                    <?php endif;

                    $current_mode = $inv['payment_mode'];
                    $mode_total = 0;
                    ?>
                    <h5 class="mt-3"><?= ucfirst($current_mode) ?> Sales</h5>
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Invoice No</th>
                                <th>Patient</th>
                                <th>Date & Time</th>
                                <th>Amount (KES)</th>
                            </tr>
                        </thead>
                        <tbody>
                <?php endif; ?>
                <tr>
                    <td><?= $inv['id'] ?></td>
                    <td><?= htmlspecialchars($inv['invoice_no']) ?></td>
                    <td><?= htmlspecialchars($inv['patient_name'] ?? 'Walk-in') ?></td>
                    <td><?= date("H:i", strtotime($inv['created_at'])) ?></td>
                    <td><?= number_format($inv['total'],2) ?></td>
                </tr>
                <?php
                $mode_total += $inv['total'];
                $grand_total += $inv['total'];
            endwhile;

            if($current_mode !== ''): ?>
                <tr class="table-secondary">
                    <th colspan="4">Subtotal (<?= ucfirst($current_mode) ?>)</th>
                    <th><?= number_format($mode_total,2) ?></th>
                </tr>
            </tbody>
            </table>
            <?php endif; ?>

            <!-- Grand Total -->
            <div class="mt-3 text-end">
                <h5>Grand Total: KES <?= number_format($grand_total,2) ?></h5>
            </div>

            <div class="mt-3">
                <button onclick="window.print()" class="btn btn-success">Print Report</button>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Sales Amount (KES)',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: ['#0d6efd','#198754','#ffc107'],
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: 'Sales by Payment Mode'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php
$stmt->close();
$stmt2->close();
include __DIR__ . '/../includes/footer.php';
?>