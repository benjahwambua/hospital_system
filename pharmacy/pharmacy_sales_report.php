<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to'] ?? date('Y-m-d');

$sql = "
SELECT 
    i.id AS invoice_id,
    i.created_at,
    i.total,
    i.status,
    p.full_name AS patient_name,
    w.full_name AS walkin_name
FROM invoices i
LEFT JOIN patients p ON p.id = i.patient_id
LEFT JOIN walkin_customers w ON w.id = i.walkin_id
WHERE DATE(i.created_at) BETWEEN '$from' AND '$to'
ORDER BY i.created_at DESC
";

$res = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main">
    <h3>Pharmacy Sales Report</h3>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="date" name="from" value="<?= $from ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <input type="date" name="to" value="<?= $to ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary">Filter</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Total (KES)</th>
                <th>Status</th>
                <th>Invoice</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $i=1; 
        $grand = 0;
        while($r = $res->fetch_assoc()):
            $grand += $r['total'];
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
                <td><?= htmlspecialchars($r['patient_name'] ?? $r['walkin_name'] ?? 'N/A') ?></td>
                <td><?= number_format($r['total'],2) ?></td>
                <td><?= strtoupper($r['status']) ?></td>
                <td>
                    <a class="btn btn-sm btn-secondary"
                       href="/hospital_system/invoices/print_invoice.php?id=<?= $r['invoice_id'] ?>">
                        Print
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">TOTAL</th>
                <th><?= number_format($grand,2) ?></th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
