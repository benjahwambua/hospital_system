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
/* HMS Procurement — dashboard-grade visual system */
.proc-page{
    padding:30px 26px 44px!important;
    background:linear-gradient(180deg,#f4f7fb 0%,#f8fafc 100%)!important;
    min-height:calc(100vh - 60px)!important;
}
.proc-shell{max-width:1500px!important;margin:0 auto!important}

/* Hero */
.proc-hero{
    position:relative!important;
    overflow:hidden!important;
    min-height:156px!important;
    padding:30px 32px!important;
    margin-bottom:22px!important;
    display:flex!important;
    align-items:center!important;
    justify-content:space-between!important;
    gap:24px!important;
    color:#fff!important;
    border:0!important;
    border-radius:20px!important;
    background:linear-gradient(135deg,#052f5f 0%,#075b9d 58%,#0b6eaa 100%)!important;
    box-shadow:0 12px 30px rgba(6,59,115,.18)!important;
}
.proc-hero:after{
    content:""!important;
    position:absolute!important;
    width:300px!important;height:300px!important;
    right:-90px!important;top:-150px!important;
    border-radius:50%!important;
    background:rgba(255,255,255,.07)!important;
    pointer-events:none!important;
}
.proc-hero:before{
    content:""!important;
    position:absolute!important;
    width:180px!important;height:180px!important;
    right:170px!important;bottom:-125px!important;
    border-radius:50%!important;
    border:30px solid rgba(255,255,255,.035)!important;
    pointer-events:none!important;
}
.proc-hero>div{position:relative!important;z-index:1!important}
.proc-kicker{
    margin-bottom:7px!important;
    color:#bfe8ff!important;
    font-size:11px!important;
    font-weight:800!important;
    letter-spacing:2px!important;
    text-transform:uppercase!important;
}
.proc-hero h1{
    margin:0 0 8px!important;
    color:#fff!important;
    font-size:30px!important;
    line-height:1.15!important;
    font-weight:800!important;
    letter-spacing:-.4px!important;
}
.proc-hero p{
    margin:0!important;
    color:rgba(255,255,255,.78)!important;
    font-size:14px!important;
}
.proc-actions{
    position:relative!important;
    z-index:2!important;
    display:flex!important;
    align-items:center!important;
    justify-content:flex-end!important;
    gap:10px!important;
    flex-wrap:wrap!important;
}
.proc-actions>div{display:none!important}
.proc-actions .btn{
    min-height:42px!important;
    padding:10px 16px!important;
    border-radius:11px!important;
    font-size:12px!important;
    font-weight:800!important;
    transition:transform .15s,box-shadow .15s!important;
}
.proc-actions .btn-light{
    color:#063b73!important;
    background:#fff!important;
    border-color:#fff!important;
    box-shadow:0 5px 15px rgba(0,0,0,.10)!important;
}
.proc-actions .btn-outline-light{
    color:#fff!important;
    background:rgba(255,255,255,.10)!important;
    border-color:rgba(255,255,255,.35)!important;
}
.proc-actions .btn:hover{transform:translateY(-2px)!important}

/* Filter workspace */
.proc-card{
    position:relative!important;
    margin-bottom:20px!important;
    overflow:hidden!important;
    background:#fff!important;
    border:1px solid #e5eaf1!important;
    border-radius:15px!important;
    box-shadow:0 5px 18px rgba(31,45,61,.055)!important;
}
.proc-card .card-body{padding:21px!important}
.proc-card form.form-row{
    display:flex!important;
    align-items:center!important;
    gap:12px!important;
    margin:0!important;
}
.proc-card form.form-row>div{padding:0!important;margin:0!important}
.proc-card form.form-row>div:first-child{flex:1.4!important}
.proc-card form.form-row>div:nth-child(2){flex:1!important}
.proc-card form.form-row>div:nth-child(3){flex:1.15!important}
.proc-card .form-control{
    height:45px!important;
    padding:0 14px!important;
    color:#344054!important;
    background:#fbfcfe!important;
    border:1px solid #dce3ec!important;
    border-radius:10px!important;
    box-shadow:none!important;
    font-size:13px!important;
}
.proc-card .form-control::placeholder{color:#98a2b3!important}
.proc-card .form-control:focus{
    background:#fff!important;
    border-color:#2f6fed!important;
    box-shadow:0 0 0 3px rgba(47,111,237,.09)!important;
}
.proc-card form.form-row .btn{
    height:45px!important;
    border-radius:10px!important;
    padding:0 14px!important;
    font-size:12px!important;
    font-weight:800!important;
}
.proc-card form.form-row .btn-primary{background:#2f6fed!important;border-color:#2f6fed!important}
.proc-card form.form-row .btn-dark{background:#25324a!important;border-color:#25324a!important}
.proc-card form.form-row .btn-light{background:#f2f4f7!important;border-color:#e4e7ec!important;color:#344054!important}

/* Report toolbar */
.proc-card>.card-body.table-responsive{padding:0!important}
.proc-card>.card-body.table-responsive>.mb-2{
    margin:0!important;
    padding:15px 19px!important;
    border-bottom:1px solid #edf0f5!important;
    background:#fff!important;
}
.proc-card>.card-body.table-responsive>.mb-2 .btn{
    border-radius:9px!important;
    font-size:11px!important;
    font-weight:800!important;
    padding:8px 12px!important;
}

/* Main data table */
.proc-table{
    width:100%!important;
    margin:0!important;
    border-collapse:separate!important;
    border-spacing:0!important;
}
.proc-table thead th{
    height:48px!important;
    padding:0 16px!important;
    vertical-align:middle!important;
    color:#667085!important;
    background:#f8fafc!important;
    border:0!important;
    border-bottom:1px solid #e7ebf2!important;
    font-size:10px!important;
    font-weight:800!important;
    letter-spacing:.8px!important;
    text-transform:uppercase!important;
    white-space:nowrap!important;
}
.proc-table tbody td{
    height:65px!important;
    padding:10px 16px!important;
    vertical-align:middle!important;
    color:#475467!important;
    background:#fff!important;
    border:0!important;
    border-bottom:1px solid #edf0f5!important;
    font-size:13px!important;
}
.proc-table tbody tr{transition:background .15s,transform .15s!important}
.proc-table tbody tr:hover td{background:#f7faff!important}
.proc-table tbody tr:last-child td{border-bottom:0!important}
.proc-table tbody td:first-child strong{
    display:inline-flex!important;
    align-items:center!important;
    min-height:32px!important;
    padding:0 10px!important;
    color:#075b9d!important;
    background:#edf5ff!important;
    border:1px solid #dcecff!important;
    border-radius:8px!important;
    font-size:12px!important;
    font-weight:800!important;
    letter-spacing:.2px!important;
}
.proc-table tbody td:nth-child(2){color:#667085!important;font-size:12px!important}
.proc-table tbody td:nth-child(3){color:#25324a!important;font-weight:700!important}
.proc-table tbody td:nth-child(5){
    color:#25324a!important;
    font-size:13px!important;
    font-weight:800!important;
    white-space:nowrap!important;
}
.proc-status{
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    min-width:80px!important;
    padding:6px 11px!important;
    border:1px solid #dbe7ff!important;
    border-radius:20px!important;
    color:#2f6fed!important;
    background:#edf3ff!important;
    font-size:10px!important;
    font-weight:800!important;
    letter-spacing:.35px!important;
    text-transform:uppercase!important;
}
.proc-table td:last-child{white-space:nowrap!important}
.proc-table td .btn{
    margin:2px 3px 2px 0!important;
    padding:7px 10px!important;
    border-radius:8px!important;
    font-size:10px!important;
    font-weight:800!important;
    box-shadow:none!important;
    transition:transform .12s!important;
}
.proc-table td .btn:hover{transform:translateY(-1px)!important}
.proc-table td .btn-info{color:#2f6fed!important;background:#eef4ff!important;border-color:#dbe7ff!important}
.proc-table td .btn-secondary{color:#344054!important;background:#f2f4f7!important;border-color:#e4e7ec!important}
.proc-table td .btn-warning{color:#9a6700!important;background:#fff7e6!important;border-color:#ffe1a8!important}
.proc-table td .btn-danger{color:#c92a2a!important;background:#fff1f1!important;border-color:#ffd5d5!important}
.proc-table td .btn-success{color:#19713a!important;background:#edf9f1!important;border-color:#ccebd5!important}
.proc-table td .btn:disabled{opacity:.42!important;cursor:not-allowed!important}
.proc-table td form{display:inline-block!important;margin:0!important}

/* Footer/pagination */
.proc-card>.card-body.table-responsive>.d-flex:last-child{
    min-height:58px!important;
    padding:12px 19px!important;
    border-top:1px solid #edf0f5!important;
    background:#fff!important;
}
.proc-card>.card-body.table-responsive>.d-flex:last-child small{
    color:#8792a5!important;
    font-size:11px!important;
    font-weight:600!important;
}
.proc-card>.card-body.table-responsive>.d-flex:last-child .btn{
    border-radius:8px!important;
    border:1px solid #e4e7ec!important;
    font-size:11px!important;
    font-weight:700!important;
}

/* PO detail */
.proc-detail{max-width:1120px!important;margin:0 auto!important}
.po-card{
    overflow:hidden!important;
    background:#fff!important;
    border:1px solid #e5eaf1!important;
    border-radius:16px!important;
    box-shadow:0 8px 26px rgba(31,45,61,.07)!important;
}
.po-content{padding:30px!important}
.po-branding{
    display:flex!important;
    align-items:center!important;
    gap:20px!important;
    padding-bottom:22px!important;
    border-bottom:1px solid #edf0f5!important;
}
.po-hospital h2{margin-bottom:5px!important;color:#25324a!important;font-weight:800!important}
.status-pill{
    display:inline-flex!important;
    padding:6px 10px!important;
    border-radius:20px!important;
    color:#2f6fed!important;
    background:#edf3ff!important;
    border:1px solid #dbe7ff!important;
    font-size:10px!important;
    font-weight:800!important;
    text-transform:uppercase!important;
}
.po-table{width:100%!important;border-collapse:separate!important;border-spacing:0!important}
.po-table th{
    padding:13px!important;
    color:#748198!important;
    background:#f8fafc!important;
    border-bottom:1px solid #e7ebf2!important;
    font-size:10px!important;
    font-weight:800!important;
    text-transform:uppercase!important;
}
.po-table td{padding:14px 13px!important;color:#344054!important;border-bottom:1px solid #edf0f5!important}
.po-total{
    margin-top:18px!important;
    padding-top:18px!important;
    color:#075b9d!important;
    border-top:1px solid #edf0f5!important;
    font-size:21px!important;
    font-weight:800!important;
}

/* Responsive */
@media(max-width:1000px){
    .proc-page{padding:22px 16px 35px!important}
    .proc-hero{align-items:flex-start!important;flex-direction:column!important}
    .proc-actions{justify-content:flex-start!important;width:100%!important}
    .proc-card form.form-row{display:block!important}
    .proc-card form.form-row>div{width:100%!important;margin-bottom:10px!important}
    .proc-card form.form-row>div:last-child{margin-bottom:0!important}
    .proc-card>.card-body.table-responsive{overflow-x:auto!important}
    .proc-table{min-width:930px!important}
}
@media(max-width:600px){
    .proc-page{padding:16px 10px 28px!important}
    .proc-hero{padding:23px 20px!important;border-radius:16px!important;min-height:0!important}
    .proc-hero h1{font-size:23px!important}
    .proc-actions{display:grid!important;grid-template-columns:1fr!important}
    .proc-actions .btn{width:100%!important}
    .proc-card .card-body{padding:15px!important}
    .po-content{padding:19px!important}
    .po-branding{align-items:flex-start!important;flex-direction:column!important}
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
        <div><div class="h3 text-gray-800 mb-0">Purchase Order History</div><small class="text-muted">Review, approve, receive, edit or delete procurement orders.</small></div>
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
