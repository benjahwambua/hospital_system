<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

if (isset($_GET['action']) && $_GET['action']=='serve' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE queue SET status='serving' WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    header('Location: /hospital_system/queue/index.php');
    exit;
}

$res = $conn->query("SELECT q.*, p.full_name FROM queue q LEFT JOIN patients p ON p.id=q.patient_id WHERE q.status='waiting' ORDER BY q.created_at ASC");
?>
<div class="main">
  <div class="page-title">Queue</div>
  <div class="card">
    <table class="table"><thead><tr><th>ID</th><th>Patient</th><th>Added</th><th>Action</th></tr></thead>
      <tbody>
        <?php while ($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo htmlspecialchars($r['full_name']); ?></td>
          <td><?php echo htmlspecialchars($r['created_at']); ?></td>
          <td><a class="btn" href="/hospital_system/queue/index.php?action=serve&id=<?php echo (int)$r['id']; ?>">Serve</a></td>
        </tr>
        <?php endwhile; ?>
      </tbody></table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
