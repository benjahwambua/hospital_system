<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_login();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$patients = [];
$res = $conn->query("SELECT id, hospital_number, full_name, gender, phone, created_at FROM patients ORDER BY created_at DESC");
if ($res) while ($r = $res->fetch_assoc()) $patients[] = $r;
?>
<div class="main">
  <div class="page-title">Patients</div>
  <div style="margin-bottom:12px;"><a class="btn" href="/hospital_system/patients/add_patient.php">+ Add Patient</a></div>

  <div class="card">
    <table class="table">
      <thead><tr><th>ID</th><th>HN</th><th>Name</th><th>Gender</th><th>Phone</th><th>Registered</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($patients as $p): ?>
        <tr>
          <td><?php echo (int)$p['id']; ?></td>
          <td><?php echo htmlspecialchars($p['hospital_number']); ?></td>
          <td><?php echo htmlspecialchars($p['full_name']); ?></td>
          <td><?php echo htmlspecialchars($p['gender']); ?></td>
          <td><?php echo htmlspecialchars($p['phone']); ?></td>
          <td><?php echo htmlspecialchars($p['created_at']); ?></td>
          <td>
            <a class="btn btn-secondary" href="/hospital_system/doctors.php?patient_id=<?php echo (int)$p['id']; ?>">Open</a>
            <a class="btn" href="/hospital_system/patients/view_patient.php?id=<?php echo (int)$p['id']; ?>">View</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
