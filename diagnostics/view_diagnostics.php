<?php
require_once "../includes/session.php";
require_once "../config/config.php";
require_login();
require_role(['admin','pharmacist']);

include "../includes/header.php";
include "../includes/sidebar.php";

$results = $conn->query("
    SELECT d.*, p.fullname AS patient, u.fullname AS doctor
    FROM diagnostics d
    JOIN patients p ON d.patient_id = p.id
    JOIN users u ON d.doctor_id = u.id
    ORDER BY d.id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Diagnostics</title>
</head>
<body>

<h2>All Diagnostics</h2>

<table border="1" width="100%">
<tr>
    <th>ID</th>
    <th>Patient</th>
    <th>Doctor</th>
    <th>Diagnosis</th>
    <th>Recommendations</th>
    <th>Date</th>
</tr>

<?php while($row = $results->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['patient'] ?></td>
    <td><?= $row['doctor'] ?></td>
    <td><?= $row['diagnosis'] ?></td>
    <td><?= $row['recommendations'] ?></td>
    <td><?= $row['created_at'] ?></td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
<?php include __DIR__.'/../includes/footer.php'; ?>