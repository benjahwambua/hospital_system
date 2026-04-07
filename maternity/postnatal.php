<?php
// maternity/postnatal.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maternity_id = intval($_POST['maternity_id']);
    $bp = $conn->real_escape_string($_POST['bp'] ?? '');
    $temp = $conn->real_escape_string($_POST['temp'] ?? '');
    $weight = $conn->real_escape_string($_POST['weight'] ?? '');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    $vt = 'PNC';
    $stmt = $conn->prepare("INSERT INTO maternity_visits (maternity_id, visit_type, bp, temp, weight, notes) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss", $maternity_id, $vt, $bp, $temp, $weight, $notes);
    $stmt->execute();
    $stmt->close();
    audit('maternity_pnc_add', "maternity_id={$maternity_id}");
    header("Location: postnatal.php?saved=1");
    exit;
}

$res = $conn->query("SELECT v.*, p.full_name FROM maternity_visits v JOIN maternity m ON m.id=v.maternity_id JOIN patients p ON p.id=m.patient_id WHERE v.visit_type='PNC' ORDER BY v.created_at DESC LIMIT 200");
$mat_list = $conn->query("SELECT m.id, p.full_name FROM maternity m JOIN patients p ON p.id=m.patient_id ORDER BY p.full_name ASC");
?>

<div class="main">
  <div class="page-title">Postnatal (PNC) Visits</div>
  <div class="card" style="display:flex;gap:18px">
    <div style="flex:1">
      <h4>Record PNC Visit</h4>
      <form method="post">
        <label>Maternity Record</label>
        <select name="maternity_id" class="form-control" required>
          <option value="">-- select --</option>
          <?php while($m=$mat_list->fetch_assoc()): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?></option>
          <?php endwhile; ?>
        </select>
        <div style="display:flex;gap:8px;margin-top:8px;">
          <input name="bp" class="form-control" placeholder="BP">
          <input name="temp" class="form-control" placeholder="Temp">
          <input name="weight" class="form-control" placeholder="Weight">
        </div>
        <label style="margin-top:8px">Notes</label>
        <textarea name="notes" class="form-control"></textarea>
        <div style="margin-top:10px"><button class="btn" type="submit">Save PNC Visit</button></div>
      </form>
    </div>

    <div style="flex:1">
      <h4>Recent PNC Visits</h4>
      <table class="table">
        <thead><tr><th>Date</th><th>Patient</th><th>BP</th><th>Weight</th><th>Notes</th></tr></thead>
        <tbody>
          <?php while($r=$res->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($r['created_at']) ?></td>
              <td><?= htmlspecialchars($r['full_name']) ?></td>
              <td><?= htmlspecialchars($r['bp']) ?></td>
              <td><?= htmlspecialchars($r['weight']) ?></td>
              <td><?= nl2br(htmlspecialchars($r['notes'])) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
