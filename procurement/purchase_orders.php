<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_module_access($conn, 'procurement', 'view');

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token'];
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['approve_po'])) {
    require_module_access($conn, 'procurement', 'approve');
    if (!hash_equals($csrfToken,(string)($_POST['csrf_token']??''))) die('Invalid security token.');
    $approveId=(int)($_POST['po_id']??0);
    $stmt=$conn->prepare("UPDATE purchase_orders SET status='Approved' WHERE id=? AND status='Pending'");
    $stmt->bind_param('i',$approveId); $stmt->execute(); $stmt->close();
    header('Location: view_po.php?id='.$approveId.'&approved=1'); exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$viewId = max(0, (int)($_GET['view_id'] ?? 0));

if ($viewId > 0):
    $po_stmt = $conn->prepare("SELECT po.*, s.name AS s_name, s.phone, s.email, u.username
                               FROM purchase_orders po
                               JOIN suppliers s ON po.supplier_id = s.id
                               LEFT JOIN users u ON po.user_id = u.id
                               WHERE po.id = ? LIMIT 1");
    $po_stmt->bind_param('i', $viewId);
    $po_stmt->execute();
    $po = $po_stmt->get_result()->fetch_assoc();
    $po_stmt->close();

    $items = [];
    if ($po) {
        $items_stmt = $conn->prepare('SELECT item_name, quantity, unit_price, line_total FROM purchase_order_items WHERE purchase_order_id = ? ORDER BY id ASC');
        $items_stmt->bind_param('i', $viewId);
        $items_stmt->execute();
        $itemsRes = $items_stmt->get_result();
        while ($row = $itemsRes->fetch_assoc()) {
            $items[] = $row;
        }
        $items_stmt->close();
    }

    // PO accounting is recognized at GRN/receiving, when inventory is actually received.
?>
<style>
/* PROCUREMENT PURCHASE ORDERS — dashboard-matched visual overhaul */
.proc-page{
    padding:28px 24px 44px!important;
    background:#f5f7fb!important;
    min-height:calc(100vh - 75px)!important;
}
.proc-shell{width:100%!important;max-width:none!important;margin:0!important}

/* Header */
.proc-hero{
    position:relative!important;
    overflow:hidden!important;
    min-height:126px!important;
    padding:28px 32px!important;
    margin-bottom:22px!important;
    display:flex!important;
    align-items:center!important;
    justify-content:space-between!important;
    gap:24px!important;
    color:#fff!important;
    border-radius:18px!important;
    background:linear-gradient(135deg,#063b73 0%,#075b9d 52%,#2b78b8 100%)!important;
    box-shadow:0 14px 32px rgba(6,59,115,.18)!important;
}
.proc-hero:before,.proc-hero:after{
    content:""!important;
    position:absolute!important;
    border-radius:50%!important;
    pointer-events:none!important;
    background:rgba(255,255,255,.07)!important;
}
.proc-hero:before{width:250px!important;height:250px!important;right:-75px!important;top:-135px!important}
.proc-hero:after{width:130px!important;height:130px!important;right:130px!important;bottom:-100px!important}
.proc-hero>div{position:relative!important;z-index:2!important}
.proc-kicker{
    margin:0 0 5px!important;
    color:#d9ebfb!important;
    font-size:10px!important;
    font-weight:800!important;
    letter-spacing:1.8px!important;
    text-transform:uppercase!important;
}
.proc-hero h1{
    margin:0 0 7px!important;
    color:#fff!important;
    font-size:30px!important;
    line-height:1.15!important;
    font-weight:800!important;
    letter-spacing:-.4px!important;
}
.proc-hero p{margin:0!important;color:rgba(255,255,255,.82)!important;font-size:13px!important}
.proc-actions{display:flex!important;align-items:center!important;justify-content:flex-end!important;gap:9px!important;flex-wrap:wrap!important;position:relative!important;z-index:3!important}
.proc-actions>div:first-child{display:none!important}
.proc-actions .btn{
    min-height:40px!important;
    padding:9px 15px!important;
    border-radius:9px!important;
    font-size:11px!important;
    font-weight:800!important;
    border-width:1px!important;
}
.proc-actions .btn-primary{
    background:#fff!important;
    border-color:#fff!important;
    color:#063b73!important;
    box-shadow:0 5px 14px rgba(0,0,0,.12)!important;
}
.proc-actions .btn-light{
    background:#fff!important;
    border-color:#fff!important;
    color:#063b73!important;
}
.proc-actions .btn-outline-light{
    background:rgba(255,255,255,.10)!important;
    border-color:rgba(255,255,255,.48)!important;
    color:#fff!important;
}
.proc-actions .btn:hover{transform:translateY(-1px)!important}

/* Filter panel */
.proc-card{
    margin-bottom:20px!important;
    overflow:hidden!important;
    background:#fff!important;
    border:1px solid #e5e9f0!important;
    border-radius:14px!important;
    box-shadow:0 5px 18px rgba(31,45,61,.045)!important;
}
.proc-card .card-body{padding:18px 20px!important}
.proc-card form.form-row{
    display:flex!important;
    align-items:center!important;
    gap:10px!important;
    margin:0!important;
}
.proc-card form.form-row>div{padding:0!important;margin:0!important}
.proc-card form.form-row>div:first-child{flex:1.6!important}
.proc-card form.form-row>div:nth-child(2){flex:1!important}
.proc-card form.form-row>div:nth-child(3){flex:0 0 auto!important}
.proc-card .form-control{
    height:43px!important;
    padding:0 13px!important;
    border:1px solid #dfe4eb!important;
    border-radius:9px!important;
    background:#fbfcfe!important;
    color:#344054!important;
    font-size:12px!important;
    box-shadow:none!important;
}
.proc-card .form-control::placeholder{color:#98a2b3!important}
.proc-card .form-control:focus{
    background:#fff!important;
    border-color:#075b9d!important;
    box-shadow:0 0 0 3px rgba(7,91,157,.10)!important;
}
.proc-card form.form-row .btn{
    height:43px!important;
    padding:0 14px!important;
    border-radius:9px!important;
    font-size:11px!important;
    font-weight:800!important;
}
.proc-card form.form-row .btn-primary{background:#075b9d!important;border-color:#075b9d!important}
.proc-card form.form-row .btn-dark{background:#25324a!important;border-color:#25324a!important}
.proc-card form.form-row .btn-light{background:#f8f9fb!important;border-color:#e1e5eb!important;color:#475467!important}

/* Report toolbar */
.proc-card>.card-body.table-responsive{padding:0!important}
.proc-card>.card-body.table-responsive>.mb-2{
    display:flex!important;
    align-items:center!important;
    justify-content:flex-end!important;
    gap:8px!important;
    min-height:54px!important;
    padding:9px 18px!important;
    margin:0!important;
    border-bottom:1px solid #edf0f4!important;
    background:#fff!important;
}
.proc-card>.card-body.table-responsive>.mb-2 .btn{
    margin:0!important;
    padding:7px 11px!important;
    border-radius:8px!important;
    font-size:10px!important;
    font-weight:800!important;
}
.proc-card>.card-body.table-responsive>.mb-2 .btn-outline-dark{
    color:#344054!important;
    border-color:#d9dee7!important;
    background:#fff!important;
}
.proc-card>.card-body.table-responsive>.mb-2 .btn-outline-primary{
    color:#075b9d!important;
    border-color:#d7e6f4!important;
    background:#f6faff!important;
}

/* Table */
.proc-table{
    width:100%!important;
    margin:0!important;
    border-collapse:separate!important;
    border-spacing:0!important;
}
.proc-table thead th{
    height:45px!important;
    padding:0 16px!important;
    vertical-align:middle!important;
    background:#fafbfc!important;
    color:#7b8798!important;
    border:0!important;
    border-bottom:1px solid #e5e9ef!important;
    font-size:9.5px!important;
    font-weight:800!important;
    letter-spacing:.75px!important;
    text-transform:uppercase!important;
    white-space:nowrap!important;
}
.proc-table tbody td{
    height:66px!important;
    padding:9px 16px!important;
    vertical-align:middle!important;
    background:#fff!important;
    color:#475467!important;
    border:0!important;
    border-bottom:1px solid #edf0f4!important;
    font-size:12px!important;
}
.proc-table tbody tr{transition:background .15s ease!important}
.proc-table tbody tr:hover td{background:#f5f9fd!important}
.proc-table tbody tr:last-child td{border-bottom:0!important}
.proc-table tbody td:first-child strong{
    display:inline-flex!important;
    align-items:center!important;
    min-width:76px!important;
    justify-content:center!important;
    padding:6px 9px!important;
    border-radius:8px!important;
    border:1px solid #cfe1f3!important;
    background:#f1f7fc!important;
    color:#075b9d!important;
    font-size:11px!important;
    font-weight:800!important;
}
.proc-table tbody td:nth-child(2){color:#7b8798!important;font-size:11px!important;white-space:nowrap!important}
.proc-table tbody td:nth-child(3){color:#344054!important;font-weight:700!important}
.proc-table tbody td:nth-child(5){color:#25324a!important;font-weight:800!important;white-space:nowrap!important}
.proc-status{
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    min-width:78px!important;
    padding:6px 10px!important;
    border:1px solid #cfe1f3!important;
    border-radius:20px!important;
    background:#f1f7fc!important;
    color:#075b9d!important;
    font-size:9px!important;
    font-weight:800!important;
    letter-spacing:.35px!important;
    text-transform:uppercase!important;
}
.proc-table td:last-child{white-space:nowrap!important}
.proc-table td .btn{
    margin:2px 3px 2px 0!important;
    padding:6px 9px!important;
    border-radius:7px!important;
    font-size:9.5px!important;
    line-height:1.2!important;
    font-weight:800!important;
    border-width:1px!important;
    box-shadow:none!important;
    transition:.15s ease!important;
}
.proc-table td .btn-info{background:#eef4ff!important;border-color:#dbe6ff!important;color:#2f6fed!important}
.proc-table td .btn-secondary{background:#f2f4f7!important;border-color:#e1e5eb!important;color:#475467!important}
.proc-table td .btn-warning{background:#fff7e8!important;border-color:#f0d9a9!important;color:#075b9d!important}
.proc-table td .btn-danger{background:#fff1f1!important;border-color:#ffd6d6!important;color:#c92a2a!important}
.proc-table td .btn-success{background:#edf9f1!important;border-color:#cdebd6!important;color:#19713a!important}
.proc-table td .btn:hover{transform:translateY(-1px)!important;box-shadow:0 3px 8px rgba(31,45,61,.08)!important}
.proc-table td .btn:disabled{opacity:.38!important;cursor:not-allowed!important}
.proc-table td form{display:inline-block!important;margin:0!important}

/* Pagination footer */
.proc-card>.card-body.table-responsive>.d-flex:last-child{
    min-height:54px!important;
    padding:9px 18px!important;
    border-top:1px solid #edf0f4!important;
    background:#fff!important;
}
.proc-card>.card-body.table-responsive>.d-flex:last-child small{
    color:#98a2b3!important;
    font-size:10px!important;
}
.proc-card>.card-body.table-responsive>.d-flex:last-child .btn{
    min-width:68px!important;
    border-radius:8px!important;
    border-color:#e0e5ec!important;
    color:#475467!important;
    font-size:10px!important;
    font-weight:800!important;
}

/* PO detail */
.proc-detail{max-width:1120px!important;margin:0 auto!important}
.po-card{
    overflow:hidden!important;
    background:#fff!important;
    border:1px solid #e5e9f0!important;
    border-radius:16px!important;
    box-shadow:0 8px 24px rgba(31,45,61,.06)!important;
}
.po-content{padding:28px!important}
.po-branding{padding-bottom:18px!important;border-bottom:1px solid #edf0f4!important}
.po-hospital h2{color:#25324a!important;font-weight:800!important}
.status-pill{
    display:inline-flex!important;
    padding:6px 10px!important;
    border-radius:20px!important;
    background:#f1f7fc!important;
    border:1px solid #cfe1f3!important;
    color:#075b9d!important;
    font-size:9px!important;
    font-weight:800!important;
    text-transform:uppercase!important;
}
.po-table{width:100%!important;border-collapse:separate!important;border-spacing:0!important}
.po-table th{
    padding:12px!important;
    background:#fafbfc!important;
    color:#7b8798!important;
    border-bottom:1px solid #e5e9ef!important;
    font-size:9px!important;
    font-weight:800!important;
    letter-spacing:.5px!important;
    text-transform:uppercase!important;
}
.po-table td{padding:13px 12px!important;border-bottom:1px solid #edf0f4!important;color:#344054!important}
.po-total{margin-top:16px!important;padding-top:16px!important;border-top:1px solid #edf0f4!important;color:#075b9d!important;font-size:20px!important;font-weight:800!important}

@media(max-width:900px){
    .proc-page{padding:20px 14px 32px!important}
    .proc-hero{flex-direction:column!important;align-items:flex-start!important;padding:24px!important}
    .proc-actions{width:100%!important;justify-content:flex-start!important}
    .proc-card form.form-row{display:block!important}
    .proc-card form.form-row>div{width:100%!important;margin-bottom:10px!important}
    .proc-card form.form-row>div:last-child{margin-bottom:0!important}
    .proc-card>.card-body.table-responsive{overflow-x:auto!important}
    .proc-table{min-width:900px!important}
}
@media(max-width:600px){
    .proc-page{padding:16px 10px 26px!important}
    .proc-hero{border-radius:15px!important;padding:20px!important}
    .proc-hero h1{font-size:24px!important}
    .proc-actions{display:grid!important;grid-template-columns:1fr!important}
    .proc-actions .btn{width:100%!important}
    .po-content{padding:18px!important}
}
@media print{
    .no-print{display:none!important}
    .proc-page{padding:0!important;background:#fff!important}
    .proc-hero,.proc-card,.po-card{box-shadow:none!important}
}
</style>

<div class="proc-page"><div class="proc-shell"><div class="proc-hero"><div><div class="proc-kicker">Supply Chain</div><h1>Purchase Orders</h1><p>Create, approve and track procurement orders.</p></div><div class="proc-actions"><a href="purchase_orders.php" class="btn btn-light">Purchase Orders</a><?php if (can_create($conn, 'procurement')): ?><a href="create_po.php" class="btn btn-outline-light">New Order</a><?php endif; ?></div></div><div class="proc-detail"><div class="po-container">
    <div class="no-print d-flex justify-content-between align-items-center mb-3">
        <a href="purchase_orders.php" class="btn btn-secondary btn-sm">← Back to History</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">Print Order</button>
    </div>

    <?php if ($po): ?>
        <div class="po-card">
            <img src="../assets/img/logo.png" class="watermark" alt="Watermark" onerror="this.style.display='none'">
            <div class="po-content">
                <div class="po-branding">
                    <div><img src="/hospital_system/assets/img/logo.png" alt="Hospital Logo" onerror="this.style.display='none'"></div>
                    <div class="po-hospital">
                        <h2>Emaqure Medical Centre</h2>
                        <div>Biashara Street, Opposite Old Naiwe School, Mlolongo</div>
                        <div>Contact: +254793069565</div>
                        <div>emaquremedicalcentre@gmail.com</div>
                    </div>
                </div>
                <div class="po-top">
                    <div>
                        <h3 style="margin:0;color:#1d4ed8;">Official Purchase Order</h3>
                        <div style="color:#6b7280;">PO-<?= str_pad((string)$po['id'], 5, '0', STR_PAD_LEFT) ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div><strong>Date:</strong> <?= !empty($po['order_date']) ? date('d M Y', strtotime($po['order_date'])) : 'N/A' ?></div>
                        <div><strong>Status:</strong> <span class="status-pill"><?= htmlspecialchars((string)($po['status'] ?? 'Pending')) ?></span></div>
                        <div><strong>Issued By:</strong> <?= htmlspecialchars((string)($po['username'] ?? 'System')) ?></div>
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <small style="text-transform:uppercase;color:#6b7280;">Supplier</small>
                    <div><strong><?= htmlspecialchars((string)$po['s_name']) ?></strong></div>
                    <div><?= htmlspecialchars((string)($po['phone'] ?? '')) ?></div>
                    <div><?= htmlspecialchars((string)($po['email'] ?? '')) ?></div>
                </div>

                <table class="po-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items): foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$item['item_name']) ?></td>
                                <td class="text-right"><?= (int)$item['quantity'] ?></td>
                                <td class="text-right">KES <?= number_format((float)$item['unit_price'], 2) ?></td>
                                <td class="text-right">KES <?= number_format((float)$item['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-center">No line items found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="po-total">Grand Total: KES <?= number_format((float)$po['total_amount'], 2) ?></div>
                <div style="margin-top:8px; text-align:right;">
                    <small>Accounting is posted when goods are received through GRN.</small>
                </div>

                <div class="po-signatures">
                    <div class="stamp-space">Hospital Stamp</div>
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div><strong>Hospital Authorized Signature</strong></div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div><strong>Supplier Signature</strong></div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">Purchase Order not found.</div>
    <?php endif; ?>
</div>

<?php else:
$statusFilter = trim((string)($_GET['status'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));
$generateReport = isset($_GET['generate']) && $_GET['generate'] === '1';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = $generateReport ? 10000 : 25;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
$types = '';
if ($statusFilter !== '') {
    $where[] = 'po.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $where[] = '(s.name LIKE ? OR po.id LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSql = "SELECT COUNT(*) AS total FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id {$whereSql}";
$countStmt = $conn->prepare($countSql);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$listSql = "SELECT po.*, s.name AS s_name
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            {$whereSql}
            ORDER BY po.id DESC
            LIMIT ? OFFSET ?";
$listTypes = $types . 'ii';
$listParams = $params;
$listParams[] = $perPage;
$listParams[] = $offset;
$listStmt = $conn->prepare($listSql);
$listStmt->bind_param($listTypes, ...$listParams);
$listStmt->execute();
$listRes = $listStmt->get_result();
?>
<div class="proc-page"><div class="proc-shell">
    <div class="proc-hero"><div><div class="proc-kicker">Supply Chain</div><h1>Purchase Order History</h1><p>Review, approve and receive procurement orders.</p></div><div class="proc-actions">
        <?php if (can_create($conn, 'procurement')): ?><a href="create_po.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> New Order</a><?php endif; ?>
    </div></div>

    <div class="proc-card">
        <div class="card-body">
            <form class="form-row">
                <div class="col-md-4 mb-2"><input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search supplier or PO number"></div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All statuses</option>
                        <?php foreach (['Pending', 'Approved', 'Partial', 'Received', 'Cancelled'] as $status): ?>
                            <option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <button type="submit" name="generate" value="1" class="btn btn-dark">Generate Report</button>
                    <a href="purchase_orders.php" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="proc-card">
        <div class="card-body table-responsive">
            <div class="mb-2 d-flex justify-content-end">
                <button onclick="exportTableToCSV('po_report_<?= date('Y-m-d') ?>.csv')" class="btn btn-sm btn-outline-dark mr-2">Download CSV</button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary">Print Report</button>
            </div>
            <table class="table proc-table table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>PO #</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        
                        <th>Total Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($listRes && $listRes->num_rows > 0): while ($row = $listRes->fetch_assoc()): ?>
                        <tr>
                            <td><strong>PO-<?= str_pad((string)$row['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                            <td><?= !empty($row['order_date']) ? date('d M Y', strtotime($row['order_date'])) : 'N/A' ?></td>
                            <td><?= htmlspecialchars((string)$row['s_name']) ?></td>
                            <td><span class="proc-status"><?= htmlspecialchars((string)($row['status'] ?? 'Pending')) ?></span></td>
                            <td>KES <?= number_format((float)$row['total_amount'], 2) ?></td>
                            <td>
                                <a href="view_po.php?id=<?= (int)$row['id'] ?>" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> View</a>
                                <?php if (can_create($conn, 'procurement') && in_array(($row['status']??''), ['Approved','Partial'], true)): ?><a href="receive_inventory.php?po_id=<?= (int)$row['id'] ?>" class="btn btn-secondary btn-sm mt-1">Receive</a><?php endif; ?> <?php if (can_edit($conn, 'procurement') && in_array(($row['status']??''), ['Pending','Cancelled'], true)): ?><a href="edit_po.php?id=<?= (int)$row['id'] ?>" class="btn btn-warning btn-sm mt-1"><i class="fa fa-edit"></i> Edit</a><?php endif; ?> <?php if (can_delete($conn, 'procurement') && in_array(($row['status']??''), ['Pending','Cancelled'], true)): ?><form method="post" action="delete_po.php" class="d-inline" onsubmit="return confirm('Delete this purchase order? This cannot be undone.');"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>"><input type="hidden" name="id" value="<?= (int)$row['id']?>"><button class="btn btn-danger btn-sm mt-1">Delete</button></form><?php endif; ?> <?php if (can_approve($conn, 'procurement')): ?><form method="post" class="d-inline">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
<input type="hidden" name="po_id" value="<?= (int)$row['id']?>">
<button name="approve_po" class="btn btn-success btn-sm mt-1" <?=($row['status']??'')!=='Pending'?'disabled':''?>>Approve</button>
</form><?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="d-flex justify-content-between align-items-center">
                <small><?= $generateReport ? 'Report mode: showing up to 10,000 records' : ('Page ' . $page . ' of ' . $totalPages) ?></small>
                <div>
                    <?php $base = ['search' => $search, 'status' => $statusFilter]; ?>
                    <?php if (!$generateReport): ?>
                        <?php if ($page > 1): ?><a class="btn btn-sm btn-light" href="?<?= htmlspecialchars(http_build_query(array_merge($base, ['page' => $page - 1]))) ?>">Previous</a><?php endif; ?>
                        <?php if ($page < $totalPages): ?><a class="btn btn-sm btn-light" href="?<?= htmlspecialchars(http_build_query(array_merge($base, ['page' => $page + 1]))) ?>">Next</a><?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function exportTableToCSV(filename) {
    const rows = document.querySelectorAll('table tr');
    const csv = [];
    rows.forEach((row) => {
        const cols = row.querySelectorAll('th, td');
        csv.push(Array.from(cols).map((c) => '"' + c.innerText.replace(/"/g, '""') + '"').join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
}
</script>
<?php
$listStmt->close();
endif;

include __DIR__ . '/../includes/footer.php';
?>
