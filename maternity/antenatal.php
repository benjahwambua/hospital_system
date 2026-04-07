<?php
// maternity/antenatal.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// optionally select a maternity record for quick add via dropdown
$patient_id = intval($_GET['patient_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maternity_id = intval($_POST['maternity_id']);
    $bp = $conn->real_escape_string($_POST['bp'] ?? '');
    $temp = $conn->real_escape_string($_POST['temp'] ?? '');
    $pulse = $conn->real_escape_string($_POST['pulse'] ?? '');
    $weight = $conn->real_escape_string($_POST['weight'] ?? '');
    $fhr = $conn->real_escape_string($_POST['fetal_heart_rate'] ?? '');
    $cervix = $conn->real_escape_string($_POST['cervix'] ?? '');
    $membrane = $conn->real_escape_string($_POST['membrane_status'] ?? '');
    $drugs = $conn->real_escape_string($_POST['drugs_given'] ?? '');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    $stmt = $conn->prepare("INSERT INTO maternity_visits (maternity_id, visit_type, bp, temp, pulse, weight, fetal_heart_rate, cervix, membrane_status, drugs_given, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $vt = 'ANC';
    $stmt->bind_param("issssssssss", $maternity_id, $vt, $bp, $temp, $pulse, $weight, $fhr, $cervix, $membrane, $drugs, $notes);
    $stmt->execute();
    $stmt->close();
    audit('maternity_anc_add', "maternity_id={$maternity_id}");
    header("Location: antenatal.php?patient_id={$patient_id}&saved=1");
    exit;
}

// list recent ANC visits (optionally filter by patient)
$where = '';
if ($patient_id) {
  $where = "WHERE m.patient_id = {$patient_id}";
}
$query = "SELECT v.*, p.full_name, m.patient_id FROM maternity_visits v JOIN maternity m ON m.id=v.maternity_id JOIN patients p ON p.id=m.patient_id WHERE v.visit_type='ANC' " . ($patient_id ? "AND m.patient_id={$patient_id}" : "") . " ORDER BY v.created_at DESC LIMIT 200";
$res = $conn->query($query);

// maternity list for the add form
$mat_list = $conn->query("SELECT m.id, p.full_name, p.hospital_number FROM maternity m JOIN patients p ON p.id=m.patient_id ORDER BY p.full_name ASC");
?>

<div class="main">
  <div class="page-title">Antenatal (ANC) Visits</div>

  <div class="card" style="display:flex;gap:18px;">
    <div style="flex:1">
      <h4>Record ANC Visit</h4>
      <form method="post">
        <label>Maternity Record</label>
        <select name="maternity_id" class="form-control" required>
          <option value="">-- select maternity record --</option>
          <?php while($m = $mat_list->fetch_assoc()): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['full_name'] . ' ['.$m['hospital_number'].']') ?></option>
          <?php endwhile; ?>
        </select>

        <div style="display:flex;gap:8px;margin-top:8px;">
          <input name="bp" class="form-control" placeholder="BP">
          <input name="temp" class="form-control" placeholder="Temp">
          <input name="pulse" class="form-control" placeholder="Pulse">
        </div>

        <div style="display:flex;gap:8px;margin-top:8px;">
          <input name="weight" class="form-control" placeholder="Weight">
          <input name="fetal_heart_rate" class="form-control" placeholder="Fetal HR">
          <input name="cervix" class="form-control" placeholder="Cervix">
        </div>

        <label style="margin-top:8px;">Drugs Given</label>
        <textarea name="drugs_given" class="form-control"></textarea>

        <label style="margin-top:8px;">Notes</label>
        <textarea name="notes" class="form-control"></textarea>

        <div style="margin-top:10px;"><button class="btn" type="submit">Save ANC Visit</button></div>
      </form>
    </div>

    <div style="flex:1">
      <h4>Recent ANC Visits</h4>
      <table class="table">
        <thead><tr><th>Date</th><th>Patient</th><th>BP</th><th>FHR</th><th>Notes</th></tr></thead>
        <tbody>
          <?php while($r = $res->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($r['created_at']) ?></td>
              <td><?= htmlspecialchars($r['full_name']) ?></td>
              <td><?= htmlspecialchars($r['bp']) ?></td>
              <td><?= htmlspecialchars($r['fetal_heart_rate']) ?></td>
              <td><?= nl2br(htmlspecialchars($r['notes'])) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
