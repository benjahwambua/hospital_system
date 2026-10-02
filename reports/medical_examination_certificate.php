<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

function h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$patientId = (int)($_GET['patient_id'] ?? $_GET['id'] ?? 0);
$patient = null;

if ($patientId > 0) {
    $stmt = $conn->prepare("SELECT id, patient_number, first_name, middle_name, last_name, date_of_birth, gender FROM patients WHERE id=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $patientId);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$patientName = $patient
    ? trim(($patient['first_name'] ?? '') . ' ' . ($patient['middle_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''))
    : '';

$age = '';
if ($patient && !empty($patient['date_of_birth'])) {
    try {
        $dob = new DateTime($patient['date_of_birth']);
        $age = $dob->diff(new DateTime())->y;
    } catch (Throwable $e) {}
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Medical Examination Certificate | Emaqure Medical Centre</title>
<style>
@page{size:A4;margin:12mm}
:root{--primary:#007bff;--ink:#111827;--muted:#64748b;--sky:#87CEFA}
*{box-sizing:border-box}
html,body{margin:0;background:#f3f6fa;color:var(--ink);font-family:Arial,Helvetica,sans-serif}
body{font-size:13.5px;line-height:1.45}
.page{width:calc(210mm - 24mm);min-height:calc(297mm - 24mm);margin:14px auto;background:#fff}
.sheet{position:relative;min-height:calc(297mm - 24mm);padding:10mm 11mm;overflow:hidden;box-shadow:0 5px 20px rgba(15,23,42,.08)}
.watermark{position:absolute;left:50%;top:52%;transform:translate(-50%,-50%) rotate(-30deg);font-size:72px;font-weight:800;color:var(--primary);opacity:.035;white-space:nowrap;pointer-events:none;z-index:0}
.content{position:relative;z-index:1}
.letterhead{display:flex;justify-content:space-between;align-items:center;gap:16px}
.logo img{max-height:62px;max-width:120px;display:block}
.org{text-align:right}
.org h1{margin:0;color:#0d3f85;font-size:19px;text-transform:uppercase;letter-spacing:.02em}
.org p{margin:2px 0;color:var(--muted);font-size:12px}
.divider{height:6px;background:var(--sky);border-radius:3px;margin:9px 0 13px;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.title{text-align:center;margin:4px 0 14px}
.title h2{margin:0;font-size:19px;letter-spacing:.04em;text-decoration:underline}
.title p{margin:3px 0 0;color:var(--muted);font-size:11px}
.patient-grid{display:grid;grid-template-columns:2.1fr .7fr 1fr 1fr;gap:10px;margin-bottom:15px}
.field{border-bottom:1px solid #111827;min-height:27px;padding:3px 2px}
.field label{font-weight:700;font-size:12px;margin-right:5px}
.section{margin-top:13px}
.lab-section{margin-top:16px}
.results-section{margin-top:18px}
.results-box{min-height:132px;padding:10px 8px;position:relative}
.result-lines{display:flex;flex-direction:column;gap:7px;margin-top:8px}
.result-lines span{display:block;border-bottom:1px dotted #111827;height:17px}
.section-title{font-weight:800;font-size:13px;text-transform:uppercase;border-bottom:2px solid #111827;padding-bottom:4px;margin-bottom:5px}
.exam-row{display:grid;grid-template-columns:72px 1fr;min-height:29px;border-bottom:1px solid #d7dee8}
.exam-row label{font-weight:700;padding:5px 4px 5px 2px}
.line{min-height:29px;padding:5px 4px;border-left:1px solid #d7dee8}
.large-line{min-height:65px}
.tests{display:block}
.test{display:grid;grid-template-columns:105px 1fr;min-height:31px;border-bottom:1px solid #d7dee8}
.test label{font-weight:700;padding:6px 2px}
.test .line{padding-left:5px;min-height:31px}
.recommendation{min-height:102px;border:1px solid #cbd5e1;padding:7px}
.signature{display:grid;grid-template-columns:1.4fr 1fr;gap:25px;margin-top:34px};align-items:end}
.sigline{border-bottom:1px solid #111827;height:25px}
.siglabel{font-size:11px;color:var(--muted);margin-top:4px}
.footer-note{margin-top:10px;text-align:center;font-size:10px;color:var(--muted)}
.actions{text-align:right;margin-top:10px}
.btn{border:0;background:var(--primary);color:#fff;padding:8px 14px;border-radius:5px;font-weight:700;cursor:pointer}
@media print{
 html,body{background:#fff}
 .page{width:auto;min-height:auto;margin:0}
 .sheet{min-height:calc(297mm - 24mm);box-shadow:none}
 .actions{display:none}
}
@media(max-width:700px){
 .page{width:100%;margin:0}.sheet{padding:20px}
 .patient-grid{grid-template-columns:1fr 1fr}
 .tests{grid-template-columns:1fr}
 .signature{grid-template-columns:1fr}
 .watermark{display:none}
}
</style>
</head>
<body>
<div class="page">
<div class="sheet">
<div class="watermark">EMAQURE</div>
<div class="content">
<header class="letterhead">
<div class="logo"><img src="/hospital_system/assets/img/logo.png" alt="Emaqure Medical Centre"></div>
<div class="org">
<h1>Emaqure Medical Centre</h1>
<p>Biashara Street, Opposite Old Naiwe School, Mlolongo</p>
<p>Contact: +254793069565</p>
<p>emaquremedicalcentre@gmail.com</p>
</div>
</header>
<div class="divider"></div>

<div class="title">
<h2>MEDICAL EXAMINATION CERTIFICATE</h2>
<p>Medical examination record</p>
</div>

<div class="patient-grid">
<div class="field"><label>NAME</label><?= h($patientName) ?></div>
<div class="field"><label>AGE</label><?= h($age) ?></div>
<div class="field"><label>BP</label></div>
<div class="field"><label>WEIGHT</label></div>
</div>

<section class="section lab-section">
<div class="section-title">Laboratory Test</div>
<div class="tests">
<div class="test"><label>P24</label><div class="line"></div></div>
<div class="test"><label>SAT</label><div class="line"></div></div>
<div class="test"><label>O/C</label><div class="line"></div></div>
<div class="test"><label>HEP</label><div class="line"></div></div>
<div class="test"><label>MALARIA</label><div class="line"></div></div>
<div class="test"><label>H. PYLORI</label><div class="line"></div></div>
<div class="test"><label>PBF</label><div class="line"></div></div>
<div class="test"><label>FULL HAEM.</label><div class="line"></div></div>
</div>
</section>

<section class="section results-section">
<div class="section-title">Lab Results</div>
<div class="recommendation results-box">
<div class="result-lines">
<span></span>
<span></span>
<span></span>
<span></span>
<span></span>
</div>
</div>
</section>

<div class="signature">
<div><div class="sigline"></div><div class="siglabel">DR. NAME / DESIGNATION</div></div>
<div><div class="sigline"></div><div class="siglabel">SIGNATURE</div></div>
</div>

<div class="footer-note">This certificate is issued by Emaqure Medical Centre following medical examination.</div>
</div>
</div>
</div>
<div class="actions"><button class="btn" onclick="window.print()">Print Certificate</button></div>
</div>
</body>
</html>
