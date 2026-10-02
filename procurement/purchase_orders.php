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
.proc-page{background:radial-gradient(circle at 5% 0%,rgba(33,150,210,.09),transparent 27%),#f4f7fb;min-height:calc(100vh - 70px);padding:30px 24px 54px}
.proc-shell{max-width:1480px;margin:0 auto}
.proc-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#082f55 0%,#0d5f91 52%,#2196d2 100%);color:#fff;border-radius:22px;padding:30px 32px;margin-bottom:20px;box-shadow:0 18px 42px rgba(8,47,85,.2)}
.proc-hero:after{content:"";position:absolute;width:300px;height:300px;border:1px solid rgba(255,255,255,.12);border-radius:50%;right:-85px;top:-145px;box-shadow:0 0 0 35px rgba(255,255,255,.025),0 0 0 70px rgba(255,255,255,.015)}
.proc-hero>div{position:relative;z-index:1}
.proc-kicker{font-size:.68rem;text-transform:uppercase;letter-spacing:.18em;font-weight:800;opacity:.72;margin-bottom:7px}
.proc-hero h1{font-size:1.85rem;font-weight:800;letter-spacing:-.025em;margin:0 0 7px;color:#fff}
.proc-hero p{margin:0;color:rgba(255,255,255,.82);font-size:.92rem}
.proc-actions{position:absolute!important;right:30px;top:50%;transform:translateY(-50%);display:flex;gap:9px;z-index:3}
.proc-actions .btn{border-radius:10px;padding:10px 15px;font-weight:800;font-size:.74rem}
.proc-actions .btn-primary,.proc-actions .btn-light{background:#fff;border-color:#fff;color:#0d5f91;box-shadow:0 5px 14px rgba(0,0,0,.12)}
.proc-actions .btn-outline-light{background:rgba(255,255,255,.11);border-color:rgba(255,255,255,.3);color:#fff}
.proc-card{background:#fff;border:1px solid #e2e8f0;border-radius:17px;box-shadow:0 8px 25px rgba(20,40,70,.065);overflow:hidden;margin-bottom:20px}
.proc-filter-head{display:flex;justify-content:space-between;align-items:center;gap:15px;padding:18px 21px;border-bottom:1px solid #e8edf3;background:linear-gradient(180deg,#fff,#fbfcfe)}
.proc-filter-head strong{font-size:.98rem;color:#182334}
.proc-filter-head span{font-size:.78rem;color:#7a8494}
.proc-card .card-body{padding:18px 21px}
.proc-card form.form-row{display:grid;grid-template-columns:minmax(260px,1.8fr) minmax(170px,1fr) auto;gap:11px;margin:0;align-items:center}
.proc-card form.form-row>div{padding:0!important;margin:0!important}
.proc-card .form-control{height:43px;border:1px solid #dfe5ed;border-radius:10px;background:#f9fbfd;color:#25324a;font-size:.8rem;box-shadow:none}
.proc-card .form-control:focus{background:#fff;border-color:#1769aa;box-shadow:0 0 0 3px rgba(23,105,170,.10)}
.proc-card form.form-row .btn{height:43px;border-radius:10px;padding:0 14px;font-size:.72rem;font-weight:800}
.proc-card form.form-row .btn-primary{background:#1769aa;border-color:#1769aa}
.proc-card form.form-row .btn-dark{background:#25324a;border-color:#25324a}
.proc-card form.form-row .btn-light{background:#f5f7fa;border-color:#e1e6ed;color:#475467}
.proc-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid #e8edf3}
.proc-toolbar-title{display:flex;align-items:center;gap:9px;color:#182334;font-weight:800;font-size:.88rem}
.proc-toolbar-title .dot{width:8px;height:8px;border-radius:50%;background:#1769aa;box-shadow:0 0 0 4px #eaf4fb}
.proc-toolbar-actions{display:flex;gap:8px}
.proc-toolbar .btn{border-radius:9px;padding:8px 11px;font-size:.68rem;font-weight:800}
.proc-table-wrap{overflow:auto}
.proc-table{width:100%;margin:0;border-collapse:separate;border-spacing:0}
.proc-table thead th{padding:13px 16px;background:#f7f9fc;border:0;border-bottom:1px solid #e5eaf0;color:#687386;font-size:.67rem;text-transform:uppercase;letter-spacing:.07em;font-weight:800;white-space:nowrap}
.proc-table tbody td{padding:14px 16px;border:0;border-bottom:1px solid #edf1f5;color:#374151;font-size:.82rem;vertical-align:middle;background:#fff}
.proc-table tbody tr{transition:background .15s ease}
.proc-table tbody tr:hover td{background:#f7fbff}
.proc-table tbody tr:last-child td{border-bottom:0}
.po-ref{display:inline-flex;align-items:center;padding:6px 10px;border-radius:9px;background:#edf6fc;border:1px solid #d7eaf7;color:#126ba5;font-size:.72rem;font-weight:800}
.supplier-name{font-weight:750;color:#25324a}
.date-text{color:#7a8494;font-variant-numeric:tabular-nums}
.amount-text{font-weight:850;color:#152033;font-variant-numeric:tabular-nums;white-space:nowrap}
.proc-status{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#edf6fc;border:1px solid #d7eaf7;color:#126ba5;font-size:.65rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase}
.proc-table td:last-child{min-width:270px}
.proc-table td .btn{margin:2px 3px 2px 0;padding:6px 9px;border-radius:8px;font-size:.65rem;font-weight:800;border-width:1px;transition:.16s}
.proc-table td .btn-info{background:#eef4ff;border-color:#dbe6ff;color:#2f6fed}
.proc-table td .btn-secondary{background:#f3f5f8;border-color:#e1e6ed;color:#475467}
.proc-table td .btn-warning{background:#fff7e8;border-color:#f2ddb4;color:#a16207}
.proc-table td .btn-danger{background:#fff1f1;border-color:#ffd7d7;color:#c92a2a}
.proc-table td .btn-success{background:#edf9f1;border-color:#ccebd6;color:#19713a}
.proc-table td .btn:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(20,40,70,.08)}
.proc-table td form{display:inline-block;margin:0}
.proc-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:15px 20px;border-top:1px solid #e8edf3;background:#fbfcfe}
.proc-footer small{color:#7a8494;font-size:.7rem}
.proc-footer .btn{border-radius:9px;border-color:#dfe5ed;color:#475467;background:#fff;font-size:.68rem;font-weight:800}
.proc-detail{max-width:1120px;margin:0 auto}
.po-card{position:relative;overflow:hidden;background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 10px 30px rgba(20,40,70,.07)}
.po-content{position:relative;z-index:1;padding:30px}
.po-branding{display:flex;align-items:center;gap:18px;padding-bottom:20px;border-bottom:1px solid #e8edf3}
.po-branding>div:first-child img{max-width:78px;max-height:78px}
.po-hospital h2{margin:0 0 5px;color:#182334;font-size:22px;font-weight:850}
.po-hospital div{color:#667085;font-size:10px;line-height:1.6}
.po-top{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;padding:22px 0 18px;margin-bottom:17px;border-bottom:1px solid #edf1f5}
.po-top h3{margin:0!important;color:#1769aa!important;font-size:21px!important;font-weight:850!important}
.po-top>div:last-child{font-size:11px;line-height:1.9;color:#667085}
.status-pill{display:inline-flex;padding:6px 10px;border-radius:999px;background:#edf6fc;border:1px solid #d7eaf7;color:#126ba5;font-size:9px;font-weight:800;text-transform:uppercase}
.po-table{width:100%;border-collapse:separate;border-spacing:0}
.po-table th{padding:12px;background:#f7f9fc;color:#687386;border:0;border-bottom:1px solid #e5eaf0;font-size:9px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
.po-table td{padding:13px 12px;border-bottom:1px solid #edf1f5;color:#344054}
.po-total{margin-top:17px;padding-top:16px;border-top:1px solid #edf1f5;color:#1769aa;font-size:20px;font-weight:850}
.po-signatures{display:grid;grid-template-columns:1fr 1fr 1fr;gap:28px;margin-top:42px;padding-top:22px;border-top:1px solid #edf1f5}
.stamp-space{height:92px;border:1px dashed #cbd5e1;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#98a2b3;font-size:10px;text-transform:uppercase;letter-spacing:.7px}
.sig-box{padding-top:35px;text-align:center;color:#475467;font-size:10px}.sig-line{border-top:1px dotted #667085;margin-bottom:8px}
.po-card .watermark{position:absolute;left:50%;top:52%;transform:translate(-50%,-50%);width:320px;opacity:.035;pointer-events:none}
@media(max-width:950px){.proc-hero{padding-right:30px}.proc-actions{position:static!important;transform:none!important;margin-top:18px}.proc-card form.form-row{grid-template-columns:1fr}.proc-table{min-width:980px}.po-top{flex-direction:column}.po-signatures{grid-template-columns:1fr}}
@media(max-width:600px){.proc-page{padding:18px 11px 32px}.proc-hero{padding:23px 20px;border-radius:17px}.proc-hero h1{font-size:1.5rem}.proc-actions{display:grid;width:100%;grid-template-columns:1fr}.proc-actions .btn{width:100%}.proc-toolbar{align-items:flex-start;flex-direction:column}.proc-toolbar-actions{width:100%}.proc-toolbar-actions .btn{flex:1}.po-content{padding:19px}}
@media print{.no-print{display:none!important}.proc-page{padding:0;background:#fff}.proc-hero,.proc-card,.po-card{box-shadow:none!important;border:1px solid #ddd}}
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
        <div class="proc-filter-head"><strong>Purchase Order Register</strong><span>Search and filter procurement activity</span></div>
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
        <div class="card-body table-responsive p-0">
            <div class="proc-toolbar"><div class="proc-toolbar-title"><span class="dot"></span>Purchase Order Register</div><div class="proc-toolbar-actions"><button onclick="exportTableToCSV('po_report_<?= date('Y-m-d') ?>.csv')" class="btn btn-sm btn-outline-dark mr-2">Download CSV</button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary">Print Report</button>
            </div>
            <div class="proc-table-wrap"><table class="table proc-table table-hover">
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
                            <td><span class="po-ref">PO-<?= str_pad((string)$row['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                            <td><span class="date-text"><?= !empty($row['order_date']) ? date('d M Y', strtotime($row['order_date'])) : 'N/A' ?></span></td>
                            <td><span class="supplier-name"><?= htmlspecialchars((string)$row['s_name']) ?></span></td>
                            <td><span class="proc-status"><?= htmlspecialchars((string)($row['status'] ?? 'Pending')) ?></span></td>
                            <td><span class="amount-text">KES <?= number_format((float)$row['total_amount'], 2) ?></span></td>
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

            </div><div class="proc-footer">
                <small><?= $generateReport ? 'Report mode: showing up to 10,000 records' : ('Page ' . $page . ' of ' . $totalPages) ?></small>
                <div>
                    <?php $base = ['search' => $search, 'status' => $statusFilter]; ?>
                    <?php if (!$generateReport): ?>
                        <?php if ($page > 1): ?><a class="btn btn-sm btn-light" href="?<?= htmlspecialchars(http_build_query(array_merge($base, ['page' => $page - 1]))) ?>">Previous</a><?php endif; ?>
                        <?php if ($page < $totalPages): ?><a class="btn btn-sm btn-light" href="?<?= htmlspecialchars(http_build_query(array_merge($base, ['page' => $page + 1]))) ?>">Next</a><?php endif; ?>
                    <?php endif; ?>
                </div>
            </div></div>
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
