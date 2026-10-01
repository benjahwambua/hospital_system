<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

require_login();
require_module_access($conn, 'administration', 'view');

$message = '';
$error = '';
$isAdmin = current_user_is_super() || can_module_action($conn, 'administration', 'edit');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        http_response_code(403);
        exit('Forbidden.');
    }

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Invalid security token.');
    }

    $service_id = (int)($_POST['service_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');

    if ($service_id <= 0) {
        $error = 'Invalid service selected.';
    } elseif ($action === 'update_price') {
        $new_price = (float)($_POST['price'] ?? -1);
        $effective_from = trim((string)($_POST['effective_from'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));

        if ($new_price < 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $effective_from) || $effective_from < date('Y-m-d')) {
            $error = 'Price must be valid and the effective date cannot be in the past.';
        } else {
            $conn->begin_transaction();
            try {
                $check = $conn->prepare("SELECT id FROM services_master WHERE id = ? LIMIT 1");
                $check->bind_param("i", $service_id);
                $check->execute();
                if ($check->get_result()->num_rows === 0) {
                    throw new RuntimeException('Service not found.');
                }
                $check->close();

                $stmt = $conn->prepare(
                    "UPDATE service_prices
                     SET effective_to = DATE_SUB(?, INTERVAL 1 DAY), active = 0
                     WHERE service_id = ? AND payer_id IS NULL AND plan_id IS NULL
                       AND active = 1 AND effective_from <= ?"
                );
                $stmt->bind_param("sis", $effective_from, $service_id, $effective_from);
                $stmt->execute();
                $stmt->close();

                $user_id = current_user_id();
                $stmt = $conn->prepare(
                    "INSERT INTO service_prices
                    (service_id, payer_id, plan_id, price, effective_from, active, reason, created_by, created_at)
                    VALUES (?, NULL, NULL, ?, ?, 1, ?, ?, NOW())"
                );
                $stmt->bind_param("idssi", $service_id, $new_price, $effective_from, $reason, $user_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("UPDATE services_master SET price = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("dii", $new_price, $user_id, $service_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = 'Service price updated. Previous pricing remains in history.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'toggle_status') {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT active FROM services_master WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $service = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$service) throw new RuntimeException('Service not found.');

            $new_active = empty($service['active']) ? 1 : 0;
            $user_id = current_user_id();

            $stmt = $conn->prepare("UPDATE services_master SET active = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("iii", $new_active, $user_id, $service_id);
            $stmt->execute();
            $stmt->close();

            if (!$new_active) {
                $stmt = $conn->prepare(
                    "UPDATE service_prices
                     SET active = 0, effective_to = COALESCE(effective_to, CURDATE())
                     WHERE service_id = ? AND active = 1"
                );
                $stmt->bind_param("i", $service_id);
                $stmt->execute();
                $stmt->close();
                $message = 'Service deactivated. It will no longer be offered as an active catalogue item.';
            } else {
                $message = 'Service reactivated. Its catalogue price remains available for legacy billing fallback.';
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

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

$sql = "SELECT sm.*,
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
if ($params) {
    $bind = [$types];
    foreach ($params as $k => $v) $bind[] = &$params[$k];
    call_user_func_array([$stmt, 'bind_param'], $bind);
}
$stmt->execute();
$services = $stmt->get_result();
$stmt->close();

$stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'categories' => 0];
$statsResult = $conn->query(
    "SELECT COUNT(*) total,
            SUM(active = 1) active,
            SUM(active = 0) inactive,
            COUNT(DISTINCT category) categories
     FROM services_master"
);
if ($statsResult) $stats = array_merge($stats, $statsResult->fetch_assoc() ?: []);

$categories = [];
$catResult = $conn->query("SELECT DISTINCT category FROM services_master WHERE category IS NOT NULL AND category <> '' ORDER BY category");
if ($catResult) while ($row = $catResult->fetch_assoc()) $categories[] = $row['category'];

$departments = [];
$deptResult = $conn->query("SELECT DISTINCT department FROM services_master WHERE department IS NOT NULL AND department <> '' ORDER BY department");
if ($deptResult) while ($row = $deptResult->fetch_assoc()) $departments[] = $row['department'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.service-page{max-width:1400px;margin:0 auto;padding:28px 24px}
.service-hero{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:20px}
.service-hero h2{margin:0;color:#172b4d;font-size:26px}.service-hero p{margin:6px 0 0;color:#667085;font-size:13px}
.service-actions{display:flex;gap:8px;align-items:center}.service-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 14px;border-radius:9px;text-decoration:none;border:1px solid #d7e0ea;background:#fff;color:#174a7e;font-weight:700;font-size:12px}.service-btn.primary{background:#075b9d;color:#fff;border-color:#075b9d}
.catalog-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}.stat-card{background:#fff;border:1px solid #e6ebf1;border-radius:12px;padding:15px 17px}.stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.7px;color:#667085;font-weight:700}.stat-value{font-size:24px;font-weight:800;color:#172b4d;margin-top:4px}
.catalog-filter{background:#fff;border:1px solid #e6ebf1;border-radius:12px;padding:15px;margin-bottom:18px;display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto auto;gap:9px;align-items:end}.catalog-filter label{font-size:10px;text-transform:uppercase;color:#667085;font-weight:800;display:block;margin-bottom:5px}.catalog-filter input,.catalog-filter select{width:100%;box-sizing:border-box;padding:9px 10px;border:1px solid #d7dee8;border-radius:8px;background:#fff}.filter-btn{padding:9px 13px;border-radius:8px;border:1px solid #075b9d;background:#075b9d;color:#fff;font-weight:700}.reset-btn{padding:9px 13px;border-radius:8px;border:1px solid #d7dee8;background:#fff;color:#475467;font-weight:700;text-decoration:none}
.catalog-table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border:1px solid #e6ebf1;border-radius:12px;overflow:hidden}.catalog-table th{background:#f6f8fb;color:#475467;text-transform:uppercase;font-size:10px;letter-spacing:.5px;padding:12px 10px;text-align:left;border-bottom:1px solid #e6ebf1}.catalog-table td{padding:11px 10px;border-bottom:1px solid #edf1f5;color:#344054;font-size:12px;vertical-align:top}.catalog-table tr:last-child td{border-bottom:0}.service-name{font-weight:800;color:#172b4d}.service-code{font-size:10px;color:#667085;margin-top:3px}.pill{display:inline-block;padding:4px 8px;border-radius:999px;font-size:10px;font-weight:800;background:#eef5fb;color:#175985}.pill.inactive{background:#f2f4f7;color:#667085}.price{font-weight:800;color:#172b4d}.history-link{color:#075b9d;text-decoration:none;font-weight:700}.price-form{display:grid;grid-template-columns:105px 125px;gap:5px}.price-form input{min-width:0;padding:7px;border:1px solid #d7dee8;border-radius:7px}.price-form .reason{grid-column:1/-1}.small-btn{grid-column:1/-1;padding:7px;border:0;border-radius:7px;background:#075b9d;color:#fff;font-size:11px;font-weight:800}.status-form{margin-top:6px}.status-btn{border:0;background:none;color:#b54708;font-size:10px;font-weight:800;padding:0;cursor:pointer}.status-btn.activate{color:#067647}.alert{padding:11px 13px;border-radius:9px;margin-bottom:14px;background:#eef7ee;color:#166534;border:1px solid #cce7cf;font-size:12px}.alert.error{background:#fff1f0;color:#b42318;border-color:#ffd6d2}
@media(max-width:1000px){.catalog-filter{grid-template-columns:1fr 1fr}.catalog-stats{grid-template-columns:1fr 1fr}.catalog-table{display:block;overflow-x:auto}.service-hero{align-items:flex-start;flex-direction:column}}
</style>

<div class="service-page">
    <div class="service-hero">
        <div>
            <h2><i class="fas fa-list-alt"></i> Service Catalogue</h2>
            <p>Manage billable hospital services, standard pricing, departments and effective-dated price history.</p>
        </div>
        <div class="service-actions">
            <?php if ($isAdmin): ?>
                <a class="service-btn primary" href="add_service.php"><i class="fas fa-plus"></i> New Service</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="catalog-stats">
        <div class="stat-card"><div class="stat-label">Total Services</div><div class="stat-value"><?= (int)$stats['total'] ?></div></div>
        <div class="stat-card"><div class="stat-label">Active</div><div class="stat-value"><?= (int)$stats['active'] ?></div></div>
        <div class="stat-card"><div class="stat-label">Inactive</div><div class="stat-value"><?= (int)$stats['inactive'] ?></div></div>
        <div class="stat-card"><div class="stat-label">Categories</div><div class="stat-value"><?= (int)$stats['categories'] ?></div></div>
    </div>

    <form method="GET" class="catalog-filter">
        <div><label>Search</label><input type="text" name="search" placeholder="Code, service, category or department" value="<?= htmlspecialchars($search) ?>"></div>
        <div><label>Category</label><select name="category"><option value="">All categories</option><?php foreach ($categories as $c): ?><option value="<?= htmlspecialchars($c) ?>" <?= $category === strtolower($c) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($c)) ?></option><?php endforeach; ?></select></div>
        <div><label>Department</label><select name="department"><option value="">All departments</option><?php foreach ($departments as $d): ?><option value="<?= htmlspecialchars($d) ?>" <?= $department === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option><?php endforeach; ?></select></div>
        <div><label>Status</label><select name="status"><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active only</option><option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive only</option><option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All services</option></select></div>
        <button class="filter-btn" type="submit"><i class="fas fa-search"></i> Filter</button>
        <a class="reset-btn" href="view_services.php">Reset</a>
    </form>

    <div style="overflow-x:auto">
    <table class="catalog-table">
        <thead><tr>
            <th>Service</th><th>Category</th><th>Department</th><th>Unit</th><th>Cash Price</th><th>History</th><th>Status</th>
            <?php if ($isAdmin): ?><th>Manage Price</th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php if ($services->num_rows > 0): ?>
            <?php while ($s = $services->fetch_assoc()): ?>
            <tr>
                <td><div class="service-name"><?= htmlspecialchars($s['service_name']) ?></div><div class="service-code"><?= htmlspecialchars($s['service_code'] ?? '') ?></div></td>
                <td><?= htmlspecialchars(ucfirst($s['category'] ?? 'Other')) ?></td>
                <td><?= htmlspecialchars($s['department'] ?? '—') ?></td>
                <td><?= htmlspecialchars($s['unit'] ?? 'Each') ?></td>
                <td class="price">KES <?= number_format((float)$s['current_cash_price'], 2) ?></td>
                <td><a class="history-link" href="price_history.php?service_id=<?= (int)$s['id'] ?>">View history</a></td>
                <td>
                    <span class="pill <?= empty($s['active']) ? 'inactive' : '' ?>"><?= !empty($s['active']) ? 'Active' : 'Inactive' ?></span>
                    <?php if ($isAdmin): ?>
                    <form method="POST" class="status-form" onsubmit="return confirm('Change this service status?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="service_id" value="<?= (int)$s['id'] ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <button class="status-btn <?= empty($s['active']) ? 'activate' : '' ?>" type="submit"><?= empty($s['active']) ? 'Reactivate' : 'Deactivate' ?></button>
                    </form>
                    <?php endif; ?>
                </td>
                <?php if ($isAdmin): ?>
                <td>
                    <?php if (!empty($s['active'])): ?>
                    <form method="POST" class="price-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="service_id" value="<?= (int)$s['id'] ?>">
                        <input type="hidden" name="action" value="update_price">
                        <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($s['current_cash_price']) ?>" required>
                        <input type="date" name="effective_from" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                        <input class="reason" type="text" name="reason" placeholder="Reason for change">
                        <button class="small-btn" type="submit">Save New Price</button>
                    </form>
                    <?php else: ?><span style="font-size:11px;color:#98a2b3">Reactivate service to manage its live price.</span><?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="<?= $isAdmin ? 8 : 7 ?>" style="text-align:center;padding:30px;color:#667085">No services match the selected filters.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>