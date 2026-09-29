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
.proc-page{padding:28px 24px 42px;background:#f5f7fb;min-height:calc(100vh - 60px)}.proc-shell{max-width:1500px;margin:auto}.proc-hero{background:linear-gradient(135deg,#344e41,#588157);color:#fff;border-radius:18px;padding:26px 30px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:18px;box-shadow:0 12px 30px rgba(52,78,65,.16)}.proc-hero h1{margin:4px 0;font-size:27px}.proc-hero p{margin:0;color:rgba(255,255,255,.8);font-size:13px}.proc-kicker{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;font-weight:800;color:#d9f0da}.proc-actions{display:flex;gap:8px;flex-wrap:wrap}.proc-card{background:#fff;border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden;margin-bottom:16px}.proc-card .card-body{padding:20px}.proc-filter{display:flex;gap:10px;flex-wrap:wrap;align-items:center}.proc-filter .form-control{min-height:42px;border-radius:9px;border:1px solid #d7dee8}.proc-table{margin:0}.proc-table thead th{font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:#667085;background:#fafbfc;border-top:0}.proc-table td{vertical-align:middle;border-color:#edf0f5;font-size:13px}.proc-table tbody tr:hover{background:#f8fbff}.proc-status{display:inline-flex;padding:5px 9px;border-radius:20px;background:#eef5ff;color:#245ea8;font-size:11px;font-weight:800}.proc-actions-cell{white-space:nowrap}.proc-actions-cell .btn{border-radius:8px;font-size:11px;padding:6px 9px}.proc-detail{max-width:1100px;margin:auto}.po-card{border-radius:16px;box-shadow:0 6px 24px rgba(31,45,61,.07);border:1px solid #e5eaf1}.po-branding{padding-bottom:18px;border-bottom:1px solid #edf0f5}.status-pill{background:#eef5ff;color:#245ea8}.po-table th{background:#f8fafc}.po-total{color:#075b9d}
@media(max-width:700px){.proc-page{padding:18px 12px}.proc-hero{padding:22px;align-items:flex-start;flex-direction:column}.proc-filter{display:grid;grid-template-columns:1fr;width:100%}.proc-actions-cell{white-space:normal}}
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
