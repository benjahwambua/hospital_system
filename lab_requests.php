<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_login();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$res = $conn->query("SELECT l.*, p.full_name FROM lab_requests l LEFT JOIN patients p ON p.id=l.patient_id ORDER BY l.created_at DESC");
?>
<div class="main">
  <div class="page-title">Lab Requests</div>
  <div class="card">
    <table class="table"><thead><tr><th>ID</th><th>Patient</th><th>Tests</th><th>Status</th><th>Requested</th></tr></thead>
      <tbody>
        <?php while ($r = $res->fetch_assoc()): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['full_name']); ?></td>
            <td><?php echo nl2br(htmlspecialchars($r['tests'])); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody></table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
