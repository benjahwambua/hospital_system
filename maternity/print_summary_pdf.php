<?php
// maternity/print_summary_pdf.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patient_id = intval($_GET['patient_id'] ?? 0);
if (!$patient_id) die('Invalid patient id');

// reuse the same data fetching logic from print_maternity_summary.php
// (copy the same fetch blocks)
$stmt = $conn->prepare("SELECT * FROM patients WHERE id=?");
$stmt->bind_param("i",$patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM maternity WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i",$patient_id);
$stmt->execute();
$maternity = $stmt->get_result()->fetch_assoc();
$stmt->close();

// fetch visits, delivery, babies, billing same as earlier...
$mid = $maternity['id'] ?? 0;
$visits = $conn->query("SELECT * FROM maternity_visits WHERE maternity_id={$mid} ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$delivery = $conn->query("SELECT * FROM maternity_delivery WHERE maternity_id={$mid} ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
$babies = $conn->query("SELECT * FROM maternity_baby WHERE maternity_id={$mid} ORDER BY created_at ASC")->fetch_all(MYSQLI_ASSOC);
$billing = $conn->query("SELECT * FROM maternity_billing WHERE maternity_id={$mid}")->fetch_all(MYSQLI_ASSOC);

// build HTML
ob_start();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Maternity Summary</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;font-size:12px}
.header{display:flex;align-items:center;gap:12px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{border:1px solid #ddd;padding:6px}
</style>
</head>
<body>
<div class="header">
  <div><img src="<?= __DIR__ . '/../' . ltrim($SITE_LOGO,'/') ?>" alt="logo" style="width:64px"></div>
  <div>
    <h2><?= htmlspecialchars($SITE_NAME) ?></h2>
    <div>Maternity Summary</div>
  </div>
</div>
<hr>
<h3>Patient</h3>
<p><strong>Name:</strong> <?= htmlspecialchars($patient['full_name'] ?? '') ?></p>
<h3>Antenatal</h3>
<p><?= nl2br(htmlspecialchars($maternity['antenatal_notes'] ?? '')) ?></p>
<h3>Visits</h3>
<table class="table">
<thead><tr><th>Date</th><th>Type</th><th>BP</th><th>Notes</th></tr></thead>
<tbody>
<?php foreach($visits as $v): ?>
<tr><td><?= htmlspecialchars($v['created_at']) ?></td><td><?= htmlspecialchars($v['visit_type']) ?></td><td><?= htmlspecialchars($v['bp']) ?></td><td><?= htmlspecialchars($v['notes']) ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</body>
</html>
<?php
$html = ob_get_clean();

// generate PDF if dompdf installed
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream("maternity_summary_{$patient_id}.pdf", ["Attachment" => 0]);
exit;
