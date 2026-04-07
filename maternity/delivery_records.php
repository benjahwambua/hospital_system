<?php
require_once "../includes/config.php";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";

$sql = "SELECT d.id, p.full_name, d.delivery_date, d.type, d.baby_weight 
        FROM maternity_deliveries d
        JOIN patients p ON p.id = d.patient_id
        ORDER BY d.id DESC";
$res = $conn->query($sql);
?>

<div class="page-header">
    <h1>Delivery Records</h1>
    <a class="btn btn-primary" href="new_delivery.php">Record Delivery</a>
</div>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Date</th>
            <th>Type</th>
            <th>Baby Weight</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($r = $res->fetch_assoc()): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td><?= $r['delivery_date'] ?></td>
            <td><?= htmlspecialchars($r['type']) ?></td>
            <td><?= $r['baby_weight'] ?> kg</td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once "../includes/footer.php"; ?>
