<?php
require_once "../includes/config.php";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";

// Fetch admissions
$sql = "SELECT m.id, p.full_name, m.admission_date, m.ward, m.status 
        FROM maternity_admissions m
        JOIN patients p ON p.id = m.patient_id
        ORDER BY m.id DESC";

$result = $conn->query($sql);
?>
<div class="page-header">
    <h1>Maternity Admissions</h1>
    <a href="new_admission.php" class="btn btn-primary">New Admission</a>
</div>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Date</th>
            <th>Ward</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><?php echo $row['admission_date']; ?></td>
            <td><?php echo htmlspecialchars($row['ward']); ?></td>
            <td><?php echo htmlspecialchars($row['status']); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once "../includes/footer.php"; ?>
