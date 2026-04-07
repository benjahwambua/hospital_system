<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../session.php';
require_login();

$patient_id = intval($_GET['patient_id']);
if(!$patient_id) exit;

$billings = $conn->query("SELECT description, amount, created_at FROM billing WHERE patient_id=$patient_id ORDER BY created_at DESC");
?>
<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th>Description</th>
            <th>Amount (KES)</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $total = 0;
        while($b = $billings->fetch_assoc()): 
            $total += $b['amount'];
        ?>
        <tr>
            <td><?= htmlspecialchars($b['description']) ?></td>
            <td><?= number_format($b['amount'],2) ?></td>
            <td><?= $b['created_at'] ?></td>
        </tr>
        <?php endwhile; ?>
        <tr class="table-primary">
            <td><strong>Total</strong></td>
            <td colspan="2"><strong><?= number_format($total,2) ?></strong></td>
        </tr>
    </tbody>
</table>
