<?php
require_once "../includes/session.php";
require_once "../includes/auth.php";
require_once "../config/config.php";
require_login();

include "../includes/header.php";
include "../includes/sidebar.php";

$apps = $conn->query("
SELECT a.*, 
       p.full_name AS patient,
       d.fullname AS doctor
FROM appointments a
JOIN patients p ON p.id = a.patient_id
JOIN doctors d ON d.id = a.doctor_id
ORDER BY a.appointment_date DESC
");
?>
<div class="content-area">
<h2><i class="fa fa-calendar"></i> Appointments</h2>

<div class="table-wrapper">

<div class="table-actions">
    <a class="btn" href="appointments.php"><i class="fa fa-plus"></i> New Appointment</a>
</div>

<table>
<thead>
<tr>
    <th>#</th>
    <th>Patient</th>
    <th>Doctor</th>
    <th>Date</th>
    <th>Reason</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($a = $apps->fetch_assoc()): ?>
<tr>
    <td><?= $a['id'] ?></td>
    <td><?= $a['patient'] ?></td>
    <td><?= $a['doctor'] ?></td>
    <td><?= date("d M Y H:i", strtotime($a['appointment_date'])) ?></td>
    <td><?= $a['reason'] ?></td>
    <td>
        <span class="badge <?= strtolower($a['status']) ?>"><?= $a['status'] ?></span>
    </td>
    <td>
        <a href="edit_appointment.php?id=<?= $a['id'] ?>"><i class="fa fa-edit"></i></a>
        <a onclick="return confirm('Delete?')" href="delete_appointment.php?id=<?= $a['id'] ?>"><i class="fa fa-trash" style="color:red"></i></a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</div>
</div>
<?php include "../includes/footer.php"; ?>
