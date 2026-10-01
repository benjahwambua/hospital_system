<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

require_login();
require_module_access($conn, 'administration', 'view');

$search = trim((string)($_GET['search'] ?? ''));
$category = strtolower(trim((string)($_GET['category'] ?? '')));
$department = trim((string)($_GET['department'] ?? ''));
$status = strtolower(trim((string)($_GET['status'] ?? 'active')));

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $like = "%{$search}%";
    $where[] = "(sm.service_name LIKE ? OR sm.service_code LIKE ? OR sm.category LIKE ? OR sm.department LIKE ?)";
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}
if ($category !== '') {
    $where[] = "sm.category = ?";
    $params[] = $category;
    $types .= 's';
}
if ($department !== '') {
    $where[] = "sm.department = ?";
    $params[] = $department;
    $types .= 's';
}
if ($status === 'active') {
    $where[] = "sm.active = 1";
} elseif ($status === 'inactive') {
    $where[] = "sm.active = 0";
}

$sql = "SELECT sm.service_name, sm.service_code, sm.category, sm.department, sm.unit, sm.active,
        COALESCE((
            SELECT sp.price
            FROM service_prices sp
            WHERE sp.service_id = sm.id
              AND sp.payer_id IS NULL AND sp.plan_id IS NULL
              AND sp.active = 1
              AND sp.effective_from <= CURDATE()
              AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
            ORDER BY sp.effective_from DESC, sp.id DESC
            LIMIT 1
        ), sm.price) AS current_cash_price
        FROM services_master sm";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY sm.active DESC, sm.category, sm.department, sm.service_name";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit('Unable to load service catalogue.');
}
if ($params) {
    $bind = [$types];
    foreach ($params as $k => $v) $bind[] = &$params[$k];
    call_user_func_array([$stmt, 'bind_param'], $bind);
}
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;
$stmt->close();

$filterParts = [];
if ($search !== '') $filterParts[] = 'Search: ' . $search;
if ($category !== '') $filterParts[] = 'Category: ' . ucfirst($category);
if ($department !== '') $filterParts[] = 'Department: ' . $department;
$filterParts[] = 'Status: ' . ($status === 'inactive' ? 'Inactive only' : ($status === 'all' ? 'All services' : 'Active only'));
$filterSummary = implode('  •  ', $filterParts);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Service Catalogue - Emaqure Medical Centre</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
@page{size:A4 landscape;margin:10mm}
*{box-sizing:border-box}
html,body{margin:0;padding:0;background:#f1f5f9;color:#172033;font-family:Arial,Helvetica,sans-serif;font-size:10px;line-height:1.4}
.toolbar{width:277mm;margin:14px auto 10px;display:flex;justify-content:flex-end;gap:8px}
.toolbar a,.toolbar button{border:0;border-radius:5px;padding:8px 13px;text-decoration:none;cursor:pointer;font-weight:700;font-size:11px}
.back{background:#e2e8f0;color:#334155}.print{background:#075b9d;color:#fff}
.paper{position:relative;width:277mm;min-height:190mm;margin:0 auto 18px;padding:10mm;background:#fff;box-shadow:0 4px 20px rgba(15,23,42,.1);overflow:hidden}.watermark{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;z-index:0}.watermark img{width:125mm;max-width:55%;opacity:.055}.paper>*:not(.watermark){position:relative;z-index:1}
.topbar{height:5px;background:#075b9d;margin:-10mm -10mm 8mm}
.branding{display:flex;justify-content:space-between;align-items:flex-start;gap:15px;border-bottom:1px solid #cbd5e1;padding-bottom:9px}
.brand-left{display:flex;align-items:center;gap:10px}.logo{width:52px;height:52px;object-fit:contain}
.hospital-name{margin:0;color:#075b9d;font-size:20px;text-transform:uppercase;line-height:1.1}
.hospital-subtitle{margin:3px 0 0;color:#475569;font-size:9px}
.contact{text-align:right;color:#475569;font-size:8.5px;line-height:1.5}.contact strong{display:block;color:#172033;font-size:10px}
.document-head{display:flex;justify-content:space-between;align-items:flex-end;margin:12px 0 9px}
.document-title{margin:0;color:#172033;font-size:19px;text-transform:uppercase;letter-spacing:.4px}
.document-meta{text-align:right;color:#64748b;font-size:8.5px}.document-meta strong{color:#172033;font-size:10px}
.filters{background:#f8fafc;border:1px solid #dbe2ea;padding:7px 9px;margin-bottom:9px;color:#475569;font-size:8.5px}
table{width:100%;border-collapse:collapse}.catalog th{background:#075b9d;color:#fff;padding:6px 6px;font-size:8.5px;text-transform:uppercase;text-align:left;border:1px solid #075b9d}
.catalog td{padding:6px;border:1px solid #dbe2ea;vertical-align:top;font-size:9px}.catalog tbody tr:nth-child(even){background:#f8fafc}
.service-name{font-weight:800;color:#172b4d}.service-code{color:#64748b;font-size:7.5px;margin-top:1px}
.price{font-weight:800;text-align:right;white-space:nowrap}.center{text-align:center}.status{font-weight:800}.active{color:#166534}.inactive{color:#667085}
.summary{display:flex;justify-content:space-between;margin-top:9px;color:#475569;font-size:8.5px;font-weight:700}
.footer{margin-top:14px;padding-top:7px;border-top:1px solid #cbd5e1;display:flex;justify-content:space-between;color:#64748b;font-size:7.5px}
.empty{text-align:center;padding:16px!important;color:#64748b}
@media print{
 html,body{background:#fff}.toolbar{display:none!important}.paper{width:100%;min-height:0;margin:0;padding:0;box-shadow:none}
 .topbar{margin:0 0 8mm}.catalog th{background:#075b9d!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
 .catalog tbody tr:nth-child(even){-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
</style>
</head>
<body>
<div class="toolbar">
<a class="back" href="view_services.php">Back to Service Catalogue</a>
<button class="print" type="button" onclick="window.print()">Print / Save PDF</button>
</div>

<main class="paper">
<div class="watermark"><img src="/hospital_system/assets/img/logo.png" alt=""></div>
<div class="topbar"></div>
<section class="branding">
<div class="brand-left">
<?php if (is_file(__DIR__ . '/../assets/img/logo.png')): ?>
<img class="logo" src="/hospital_system/assets/img/logo.png" alt="Emaqure Medical Centre">
<?php endif; ?>
<div><h1 class="hospital-name">Emaqure Medical Centre</h1><p class="hospital-subtitle">Quality Healthcare • Patient-Centred Service</p></div>
</div>
<div class="contact"><strong>Medical Centre</strong>Biashara Street, Opposite Old Naiwe School, Mlolongo<br>+254 793 069 565<br>emaquremedicalcentre@gmail.com</div>
</section>

<section class="document-head">
<div><h2 class="document-title">Service Catalogue</h2></div>
<div class="document-meta"><strong>Official Service Price List</strong><br>Generated <?= htmlspecialchars(date('d M Y H:i')) ?></div>
</section>

<div class="filters"><?= htmlspecialchars($filterSummary) ?></div>

<table class="catalog">
<thead>
<tr><th style="width:42%">Service</th><th style="width:17%">Category</th><th style="width:17%">Department</th><th style="width:10%">Unit</th><th style="width:14%;text-align:right">Price (KES)</th></tr>
</thead>
<tbody>
<?php if ($rows): foreach ($rows as $row): ?>
<tr>
<td><div class="service-name"><?= htmlspecialchars((string)$row['service_name']) ?></div><div class="service-code"><?= htmlspecialchars((string)($row['service_code'] ?? '')) ?></div></td>
<td><?= htmlspecialchars(ucfirst((string)($row['category'] ?? 'Other'))) ?></td>
<td><?= htmlspecialchars((string)($row['department'] ?? '—')) ?></td>
<td><?= htmlspecialchars((string)($row['unit'] ?? 'Each')) ?></td>
<td class="price"><?= number_format((float)($row['current_cash_price'] ?? 0), 2) ?></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="5" class="empty">No services match the selected filters.</td></tr>
<?php endif; ?>
</tbody>
</table>

<div class="summary">
<span><?= count($rows) ?> service<?= count($rows) === 1 ? '' : 's' ?> listed</span>
<span>Prices are in Kenya Shillings (KES). Please confirm applicable charges with reception before service.</span>
</div>

<footer class="footer">
<span>Emaqure Medical Centre • Service Catalogue</span>
<span>Generated <?= htmlspecialchars(date('d M Y H:i')) ?></span>
</footer>
</main>
</body>
</html>
