<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main">
  <div class="page-title">Maternity Records</div>

  <div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <div><strong>All maternity records</strong></div>
      <div><a class="btn" href="add.php">+ New Maternity Record</a></div>
    </div>
    <hr>
    <table class="table">
      <thead>
        <tr><th>#</th><th>Patient</th><th>ANC#</th><th>EDD</th><th>Gravida/Parity</th><th>Created</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php
        $res = $conn->query("SELECT m.*, p.full_name FROM maternity m LEFT JOIN patients p ON p.id=m.patient_id ORDER BY m.created_at DESC");
        $i = 1;
        while ($r = $res->fetch_assoc()):
        ?>
        <tr>
          <td><?= $i++; ?></td>
          <td><?= htmlspecialchars($r['full_name'] ?? ''); ?></td>
          <td><?= htmlspecialchars($r['anc_number']); ?></td>
          <td><?= htmlspecialchars($r['expected_delivery']); ?></td>
          <td><?= htmlspecialchars($r['gravida']).'/'.htmlspecialchars($r['parity']); ?></td>
          <td><?= htmlspecialchars($r['created_at']); ?></td>
          <td>
            <a class="btn btn-sm" href="view.php?id=<?= $r['id'] ?>">Open</a>
            <a class="btn btn-sm" href="visit_history.php?id=<?= $r['id'] ?>">Visits</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
