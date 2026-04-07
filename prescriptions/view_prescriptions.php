<?php
include "../includes/db.php";
include "../includes/header.php";
$patient_id = $_GET['patient_id'];

$pres = $conn->query("
    SELECT * FROM prescriptions 
    WHERE patient_id=$patient_id 
    ORDER BY created_at DESC
");
?>

<h2>Prescriptions</h2>
<a href="add_prescription.php?patient_id=<?php echo $patient_id; ?>">+ Add Prescription</a>

<table border="1" width="100%">
<tr>
    <th>Drug</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Doctor</th><th>Date</th>
</tr>

<?php while($p = $pres->fetch_assoc()): ?>
<tr>
    <td><?= $p['drug_name'] ?></td>
    <td><?= $p['dosage'] ?></td>
    <td><?= $p['frequency'] ?></td>
    <td><?= $p['duration'] ?></td>
    <td><?= $p['prescribed_by'] ?></td>
    <td><?= $p['created_at'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<?php include "../includes/footer.php"; ?>
