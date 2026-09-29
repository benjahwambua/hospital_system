<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$service_id = (int)($_GET['service_id'] ?? 0);
if ($service_id <= 0) {
    http_response_code(400);
    exit('Invalid service.');
}

$stmt = $conn->prepare("SELECT id, service_code, service_name, category, department FROM services_master WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$service) {
    http_response_code(404);
    exit('Service not found.');
}

$stmt = $conn->prepare(
    "SELECT sp.*, u1.username AS created_by_name, u2.username AS approved_by_name
     FROM service_prices sp
     LEFT JOIN users u1 ON u1.id = sp.created_by
     LEFT JOIN users u2 ON u2.id = sp.approved_by
     WHERE sp.service_id = ?
     ORDER BY sp.effective_from DESC, sp.id DESC"
);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$prices = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="container">
    <div class="card">
        <h2>Price History</h2>
        <p>
            <strong><?= htmlspecialchars($service['service_name']) ?></strong><br>
            Code: <?= htmlspecialchars($service['service_code'] ?? '') ?> |
            <?= htmlspecialchars($service['department'] ?? '') ?>
        </p>

        <table class="table-blue">
            <tr>
                <th>Price</th>
                <th>Payer</th>
                <th>Plan</th>
                <th>Effective From</th>
                <th>Effective To</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Created By</th>
            </tr>
            <?php while ($p = $prices->fetch_assoc()): ?>
            <tr>
                <td>KES <?= number_format((float)$p['price'], 2) ?></td>
                <td><?= $p['payer_id'] === null ? 'Standard / Cash' : (int)$p['payer_id'] ?></td>
                <td><?= $p['plan_id'] === null ? '—' : (int)$p['plan_id'] ?></td>
                <td><?= htmlspecialchars($p['effective_from']) ?></td>
                <td><?= htmlspecialchars($p['effective_to'] ?? 'Current') ?></td>
                <td><?= !empty($p['active']) ? 'Active' : 'Historical' ?></td>
                <td><?= htmlspecialchars($p['reason'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['created_by_name'] ?? 'System') ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <p><a href="view_services.php">← Back to Service Catalog</a></p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>