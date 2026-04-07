<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';

require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$res = $conn->query("
    SELECT m.name,
           SUM(ps.qty) AS total_qty,
           SUM(ps.total) AS total_sales
    FROM pharmacy_sales ps
    JOIN medications m ON m.id = ps.med_id
    GROUP BY ps.med_id
    ORDER BY total_sales DESC
");
?>

<div class="card">
    <h3>Total Medicine Sales</h3>

    <table class="table">
        <thead>
            <tr>
                <th>Medicine</th>
                <th>Total Qty Sold</th>
                <th>Total Sales (KES)</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($r = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td><?= $r['total_qty'] ?></td>
                    <td><?= number_format($r['total_sales'], 2) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
