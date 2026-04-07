<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_role('cashier');

if (isset($_GET['pay'])) {
    $id = intval($_GET['pay']);
    $conn->query("UPDATE invoices SET status='paid' WHERE id=$id");
}

$invoices = $conn->query("
SELECT i.*, p.full_name
FROM invoices i
JOIN encounters e ON e.id=i.encounter_id
JOIN patients p ON p.id=e.patient_id
");
?>

<table>
<?php while($i=$invoices->fetch_assoc()): ?>
<tr>
<td><?= $i['full_name'] ?></td>
<td><?= $i['total'] ?></td>
<td><?= $i['status'] ?></td>
<td>
<?php if($i['status']=='unpaid'): ?>
<a href="?pay=<?= $i['id'] ?>">Mark Paid</a>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</table>
