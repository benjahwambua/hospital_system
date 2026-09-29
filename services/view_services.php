<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    require_role(['admin']);

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Invalid security token.');
    }

    $service_id = (int)($_POST['service_id'] ?? 0);
    $new_price = (float)($_POST['price'] ?? -1);
    $effective_from = trim((string)($_POST['effective_from'] ?? ''));
    $reason = trim((string)($_POST['reason'] ?? ''));

    if ($service_id <= 0 || $new_price < 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $effective_from)) {
        $error = 'Invalid service, price or effective date.';
    } else {
        $conn->begin_transaction();

        try {
            $check = $conn->prepare("SELECT id FROM services_master WHERE id = ? AND active = 1 LIMIT 1");
            $check->bind_param("i", $service_id);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                throw new RuntimeException('Service not found or inactive.');
            }
            $check->close();

            // End the currently open standard/cash price the day before the new price.
            $stmt = $conn->prepare(
                "UPDATE service_prices
                 SET effective_to = DATE_SUB(?, INTERVAL 1 DAY), active = 0
                 WHERE service_id = ? AND payer_id IS NULL AND plan_id IS NULL
                   AND active = 1 AND (effective_to IS NULL OR effective_to >= ?)"
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

            // Keep legacy consumers aligned until every billing screen uses service_prices.
            $stmt = $conn->prepare("UPDATE services_master SET price = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("dii", $new_price, $user_id, $service_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $message = 'Price updated with an effective date. Previous pricing history was preserved.';
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

$search = trim((string)($_GET['search'] ?? ''));
if ($search !== '') {
    $like = "%{$search}%";
    $stmt = $conn->prepare(
        "SELECT sm.*,
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
         FROM services_master sm
         WHERE sm.service_name LIKE ? OR sm.service_code LIKE ? OR sm.category LIKE ? OR sm.department LIKE ?
         ORDER BY sm.category, sm.service_name"
    );
    $stmt->bind_param("ssss", $like, $like, $like, $like);
} else {
    $stmt = $conn->prepare(
        "SELECT sm.*,
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
         FROM services_master sm
         ORDER BY sm.category, sm.service_name"
    );
}
$stmt->execute();
$services = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="container">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
            <h2>Service Catalog</h2>
            <?php if (current_user_is_super() || current_user_role() === 'admin'): ?>
                <a href="add_service.php"><button type="button">+ New Service</button></a>
            <?php endif; ?>
        </div>

        <?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="GET" style="margin-bottom:15px;">
            <input type="text" name="search" placeholder="Search code, name, category or department" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
            <a href="view_services.php"><button type="button">Reset</button></a>
        </form>

        <?php if ($services && $services->num_rows > 0): ?>
        <table class="table-blue">
            <tr>
                <th>Code</th>
                <th>Service Name</th>
                <th>Category</th>
                <th>Department</th>
                <th>Unit</th>
                <th>Cash Price (KSH)</th>
                <th>Active</th>
                <?php if (current_user_is_super() || current_user_role() === 'admin'): ?><th>Pricing</th><?php endif; ?>
            </tr>

            <?php while ($s = $services->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($s['service_code'] ?? '') ?></td>
                <td><?= htmlspecialchars($s['service_name']) ?></td>
                <td><?= htmlspecialchars(ucfirst($s['category'])) ?></td>
                <td><?= htmlspecialchars($s['department'] ?? '') ?></td>
                <td><?= htmlspecialchars($s['unit'] ?? 'Each') ?></td>
                <td><?= number_format((float)$s['current_cash_price'], 2) ?></td>
                <td><?= !empty($s['active']) ? 'Yes' : 'No' ?></td>
                <?php if (current_user_is_super() || current_user_role() === 'admin'): ?>
                <td>
                    <form method="POST" style="display:grid;grid-template-columns:130px 150px;gap:5px;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="service_id" value="<?= (int)$s['id'] ?>">
                        <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($s['current_cash_price']) ?>" required>
                        <input type="date" name="effective_from" value="<?= date('Y-m-d') ?>" required>
                        <input type="text" name="reason" placeholder="Reason for change">
                        <button type="submit" name="update_price">Set Price</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
            <p>No services found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>