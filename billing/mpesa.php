<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/mpesa.php';
require_login();

if (!isset($_SESSION['is_super']) || (int)$_SESSION['is_super'] !== 1) {
    http_response_code(403);
    exit('Access denied.');
}

$mpesa_message = '';
$mpesa_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initiate_stk'])) {
    $invoiceId = (int)($_POST['invoice_id'] ?? 0);
    $stkAmount = round((float)($_POST['amount'] ?? 0), 2);
    $phone = trim((string)($_POST['phone'] ?? ''));

    if ($invoiceId <= 0 || $stkAmount <= 0 || $phone === '') {
        $mpesa_error = 'Invoice, amount and M-Pesa phone number are required.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, patient_id, total FROM invoices WHERE id=? LIMIT 1");
            if (!$stmt) throw new Exception('Unable to load invoice: ' . $conn->error);
            $stmt->bind_param('i', $invoiceId);
            $stmt->execute();
            $invoice = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$invoice) throw new Exception('Invoice not found.');

            $paidStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS paid FROM payments WHERE invoice_id=?");
            if (!$paidStmt) throw new Exception('Unable to calculate invoice balance.');
            $paidStmt->bind_param('i', $invoiceId);
            $paidStmt->execute();
            $paid = (float)($paidStmt->get_result()->fetch_assoc()['paid'] ?? 0);
            $paidStmt->close();

            $balance = max((float)$invoice['total'] - $paid, 0);
            if ($balance <= 0) throw new Exception('This invoice is already fully paid.');
            if ($stkAmount > $balance) $stkAmount = $balance;

            mpesa_initiate_stk($conn, $invoiceId, (int)$invoice['patient_id'], $stkAmount, $phone);
            $mpesa_message = 'STK Push sent to ' . htmlspecialchars($phone) . ' for KES ' . number_format($stkAmount, 2) . '. Awaiting customer confirmation.';
        } catch (Throwable $e) {
            $mpesa_error = $e->getMessage();
        }
    }
}

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to   = $_GET['to'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT mt.*, i.invoice_number, p.full_name AS patient_name
    FROM mpesa_transactions mt
    LEFT JOIN invoices i ON i.id = mt.invoice_id
    LEFT JOIN patients p ON p.id = mt.patient_id
    WHERE DATE(mt.created_at) BETWEEN ? AND ?
    ORDER BY mt.created_at DESC
");
$transactions = [];
$error = '';

if (!$stmt) {
    $error = 'Unable to load M-Pesa transactions. Please run mpesa_migration.sql first.';
} else {
    $stmt->bind_param('ss', $from, $to);
    if (!$stmt->execute()) {
        $error = 'Unable to load M-Pesa transactions: ' . $stmt->error;
    } else {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $transactions[] = $row;
    }
    $stmt->close();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-1 text-gray-800"><i class="fas fa-mobile-alt"></i> M-Pesa Payments</h2>
                <p class="text-muted mb-0">STK Push transactions and payment status</p>
            </div>
            <a href="/hospital_system/billing/view_bills.php" class="btn btn-primary">
                <i class="fas fa-file-invoice-dollar"></i> Billing Management
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-mobile-alt"></i> Daraja STK Push</h6>
            </div>
            <div class="card-body">
                <?php if ($mpesa_message): ?><div class="alert alert-success"><?= $mpesa_message ?></div><?php endif; ?>
                <?php if ($mpesa_error): ?><div class="alert alert-danger"><?= htmlspecialchars($mpesa_error) ?></div><?php endif; ?>
                <form method="POST" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Invoice ID</label>
                        <input type="number" name="invoice_id" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Amount (KES)</label>
                        <input type="number" name="amount" class="form-control" min="1" step="0.01" required>
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold">M-Pesa Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="0712345678" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="initiate_stk" class="btn btn-success btn-block">
                            <i class="fas fa-paper-plane"></i> Send STK Push
                        </button>
                    </div>
                </form>
                <small class="text-muted d-block mt-2">The payment is posted to the invoice only after Safaricom sends a successful Daraja callback.</small>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-4">
                        <label class="small font-weight-bold">From Date</label>
                        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="small font-weight-bold">To Date</label>
                        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-info btn-block"><i class="fas fa-filter"></i> Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">M-Pesa Transaction History</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Patient</th>
                                <th>Phone</th>
                                <th>Amount</th>
                                <th>M-Pesa Receipt</th>
                                <th>Status</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$transactions): ?>
                            <tr><td colspan="8" class="text-center text-muted">No M-Pesa transactions found for this period.</td></tr>
                        <?php else: foreach ($transactions as $tx): ?>
                            <tr>
                                <td><?= htmlspecialchars($tx['created_at']) ?></td>
                                <td><?= htmlspecialchars($tx['invoice_number'] ?: ('#' . $tx['invoice_id'])) ?></td>
                                <td><?= htmlspecialchars($tx['patient_name'] ?: 'Unknown') ?></td>
                                <td><?= htmlspecialchars($tx['phone']) ?></td>
                                <td>KES <?= number_format((float)$tx['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($tx['mpesa_receipt'] ?: '—') ?></td>
                                <td>
                                    <span class="badge badge-<?= $tx['status'] === 'completed' ? 'success' : ($tx['status'] === 'failed' ? 'danger' : 'warning') ?>">
                                        <?= htmlspecialchars(ucfirst($tx['status'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($tx['result_desc'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
