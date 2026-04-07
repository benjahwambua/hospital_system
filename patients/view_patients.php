<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// Fetch all patients
$patients = $conn->query("SELECT id, full_name, age, gender, phone, created_at FROM patients ORDER BY full_name ASC");
?>

<div class="card">
    <h3>All Patients</h3>
    <?php if($patients->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Registered At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $patients->fetch_assoc()): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                    <td><?= $p['age'] ?></td>
                    <td><?= $p['gender'] ?></td>
                    <td><?= htmlspecialchars($p['phone']) ?></td>
                    <td><?= $p['created_at'] ?></td>
                    <td>
                        <a href="/hospital_system/patients/patient_dashboard.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">
                            View Dashboard
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No patients found.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
