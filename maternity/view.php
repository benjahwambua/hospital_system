<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$id = intval($_GET['id'] ?? 0);
if (!$id) die('Invalid ID');

$stmt = $conn->prepare("SELECT m.*, p.full_name, p.hospital_number FROM maternity m LEFT JOIN patients p ON p.id=m.patient_id WHERE m.id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$m = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$m) die('Record not found');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main">
  <div class="page-title">Maternity — <?= htmlspecialchars($m['full_name']) ?></div>

  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <strong><?= htmlspecialchars($m['full_name']) ?></strong><br>
        HN: <?= htmlspecialchars($m['hospital_number']) ?><br>
        ANC#: <?= htmlspecialchars($m['anc_number']) ?>
      </div>
      <div>
        <a class="btn" href="/hospital_system/patients/print_maternity_summary.php?patient_id=<?= $m['patient_id'] ?>" target="_blank">Print Summary</a>
      </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:18px;margin-top:18px;">
    <div>
      <div class="card">
        <h4>Record</h4>
        <table class="table">
          <tr><td>Gravida</td><td><?= htmlspecialchars($m['gravida']) ?></td></tr>
          <tr><td>Parity</td><td><?= htmlspecialchars($m['parity']) ?></td></tr>
          <tr><td>LMP</td><td><?= htmlspecialchars($m['last_menstrual_period']) ?></td></tr>
          <tr><td>EDD</td><td><?= htmlspecialchars($m['expected_delivery']) ?></td></tr>
          <tr><td>Notes</td><td><?= nl2br(htmlspecialchars($m['antenatal_notes'])) ?></td></tr>
        </table>
      </div>

      <div class="card" style="margin-top:12px">
        <h4>Start Labour / Add Delivery Details</h4>
        <form action="save_delivery.php" method="post">
          <input type="hidden" name="maternity_id" value="<?= $m['id'] ?>">
          <label>Delivery time</label>
          <input type="datetime-local" name="delivery_time" class="form-control">

          <label style="margin-top:8px">Delivery mode</label>
          <select name="delivery_mode" class="form-control">
            <option>Normal</option>
            <option>CS</option>
            <option>Assisted</option>
          </select>

          <label style="margin-top:8px">Primary Doctor (user id)</label>
          <input name="primary_doctor" class="form-control" placeholder="doctor user id">

          <label style="margin-top:8px">Mother condition</label>
          <input name="mother_condition" class="form-control">

          <label style="margin-top:8px">Complications / Notes</label>
          <textarea name="notes" class="form-control"></textarea>

          <div style="margin-top:10px"><button class="btn" type="submit">Save Delivery</button></div>
        </form>
      </div>

      <div class="card" style="margin-top:12px">
        <h4>Add Baby</h4>
        <form action="save_baby.php" method="post">
          <input type="hidden" name="maternity_id" value="<?= $m['id'] ?>">
          <label>Baby Number</label>
          <input name="baby_number" class="form-control" value="1">

          <div style="display:flex;gap:8px;margin-top:8px;">
            <div style="flex:1"><label>Gender</label><input name="gender" class="form-control"></div>
            <div style="flex:1"><label>Weight (kg)</label><input name="weight" class="form-control" type="number" step="0.01"></div>
            <div style="flex:1"><label>APGAR</label><input name="apgar" class="form-control"></div>
          </div>

          <label style="margin-top:8px">Notes</label>
          <textarea name="notes" class="form-control"></textarea>

          <div style="margin-top:10px"><button class="btn" type="submit">Add Baby</button></div>
        </form>
      </div>

    </div>

    <div>
      <div class="card">
        <h5>Visit History</h5>
        <?php
        $vis = $conn->query("SELECT * FROM maternity_visits WHERE maternity_id={$m['id']} ORDER BY created_at DESC LIMIT 20");
        if ($vis->num_rows === 0) echo '<div class="muted">No visits</div>';
        else {
          echo '<table class="table"><thead><tr><th>Date</th><th>Type</th><th>BP</th><th>FHR</th></tr></thead><tbody>';
          while ($v = $vis->fetch_assoc()) {
            echo '<tr><td>'.htmlspecialchars($v['created_at']).'</td><td>'.htmlspecialchars($v['visit_type']).'</td><td>'.htmlspecialchars($v['bp']).'</td><td>'.htmlspecialchars($v['fetal_heart_rate']).'</td></tr>';
          }
          echo '</tbody></table>';
        }
        ?>
      </div>

      <div class="card" style="margin-top:12px">
        <h5>Delivery History</h5>
        <?php
        $d = $conn->query("SELECT * FROM maternity_delivery WHERE maternity_id={$m['id']} ORDER BY created_at DESC LIMIT 5");
        if ($d->num_rows === 0) echo '<div class="muted">No deliveries recorded</div>';
        else {
          echo '<ul style="list-style:none;padding:0">';
          while ($r2 = $d->fetch_assoc()) {
            echo '<li style="padding:8px;border-bottom:1px solid #eee;"><strong>'.htmlspecialchars($r2['delivery_mode']).'</strong><div style="font-size:13px;color:#666;">'.htmlspecialchars($r2['created_at']).' — '.htmlspecialchars($r2['mother_condition']).'</div></li>';
          }
          echo '</ul>';
        }
        ?>
      </div>

      <div class="card" style="margin-top:12px">
        <h5>Babies</h5>
        <?php
        $bb = $conn->query("SELECT * FROM maternity_baby WHERE maternity_id={$m['id']} ORDER BY created_at ASC");
        if ($bb->num_rows === 0) echo '<div class="muted">No baby records</div>';
        else {
          echo '<table class="table"><thead><tr><th>#</th><th>Gender</th><th>Weight</th><th>APGAR</th></tr></thead><tbody>';
          while ($b = $bb->fetch_assoc()) {
            echo '<tr><td>'.htmlspecialchars($b['baby_number']).'</td><td>'.htmlspecialchars($b['gender']).'</td><td>'.htmlspecialchars($b['weight']).'</td><td>'.htmlspecialchars($b['apgar']).'</td></tr>';
          }
          echo '</tbody></table>';
        }
        ?>
      </div>

    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
