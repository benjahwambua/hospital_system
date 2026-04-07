<?php
include '../config/config.php';
session_start();

$patient_id = $_GET['id'];

$patient = $conn->query("SELECT * FROM patients WHERE id = $patient_id")->fetch_assoc();

$history = $conn->query("
    SELECT * FROM patient_history
    WHERE patient_id = $patient_id
    ORDER BY id DESC
");
?>

<h2>Patient History: <?= $patient['fullname'] ?></h2>

<table border="1" width="100%">
<tr>
    <th>Type</th>
    <th>Details</th>
    <th>Date</th>
</tr>
<?php while($h = $history->fetch_assoc()): ?>
<tr>
    <td><?= $h['type'] ?></td>
    <td><?= $h['details'] ?></td>
    <td><?= $h['created_at'] ?></td>
</tr>
<?php endwhile; ?>
</table>
