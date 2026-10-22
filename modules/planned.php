<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$isSuperUser = (isset($_SESSION['is_super']) && (int) $_SESSION['is_super'] === 1);
if (!$isSuperUser) {
    http_response_code(403);
    exit('Access denied. This module roadmap is available to super users only.');
}

$modules = [
    'icu' => ['name' => 'ICU / High Dependency Unit', 'scope' => 'Critical-care beds, observations, fluid balance, escalation and critical-care documentation.'],
    'neonatal' => ['name' => 'Neonatal Care', 'scope' => 'Newborn registration, APGAR, newborn observations, feeding, immunisation and neonatal admissions.'],
    'blood-bank' => ['name' => 'Blood Bank & Transfusion', 'scope' => 'Blood components, compatibility checks, cross-matching, issue and transfusion traceability.'],
    'physiotherapy' => ['name' => 'Physiotherapy & Rehabilitation', 'scope' => 'Referrals, assessments, treatment plans, therapy sessions and progress notes.'],
    'nutrition' => ['name' => 'Nutrition & Dietetics', 'scope' => 'Nutrition assessments, diet orders, therapeutic diets and follow-up.'],
    'infection-control' => ['name' => 'Infection Prevention & Control', 'scope' => 'Infection surveillance, isolation precautions, hospital-acquired infection and outbreak reporting.'],
    'assets' => ['name' => 'Asset & Equipment Management', 'scope' => 'Asset register, maintenance schedules, repair history, calibration and equipment downtime.'],
    'facilities' => ['name' => 'Facilities & Maintenance', 'scope' => 'Facility fault reporting, work orders, utilities, cleaning schedules and inspections.'],
    'mortuary' => ['name' => 'Mortuary Management', 'scope' => 'Admission, identification, storage location, documentation and authorised release.'],
    'ambulance' => ['name' => 'Ambulance & Patient Transport', 'scope' => 'Dispatch, trip records, crew, vehicle status, patient transfers and related charges.'],
    'payroll' => ['name' => 'Payroll & Attendance', 'scope' => 'Attendance, payroll, statutory deductions, payslips and payroll reconciliation.'],
    'notifications' => ['name' => 'Notifications & Escalations', 'scope' => 'Role-based alerts, critical-result escalation, task reminders, delivery status and acknowledgement.'],
    'patient-portal' => ['name' => 'Patient Portal', 'scope' => 'Patient identity verification, appointments, approved results, statements and permitted record access.'],
    'communications' => ['name' => 'Communications Centre', 'scope' => 'Governed SMS and email templates, consent, delivery logs and communication history.'],
    'integrations' => ['name' => 'Healthcare Integrations & API', 'scope' => 'Versioned API, integration credentials, access logs, status monitoring and external system connections.'],
];

$key = isset($_GET['module']) && is_string($_GET['module']) ? $_GET['module'] : '';
$selected = $modules[$key] ?? null;
$pageTitle = 'Hospital Module Roadmap';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="container-fluid py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-1">Hospital Module Roadmap</h1>
      <p class="text-muted mb-0">Navigation inventory for modules to build or validate next.</p>
    </div>
    <span class="badge badge-warning">Planning only — not clinical functionality</span>
  </div>
  <?php if ($selected): ?>
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <div class="small text-uppercase text-muted font-weight-bold mb-2">Selected module</div>
        <h2 class="h4"><?= htmlspecialchars($selected['name'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="mb-3"><?= htmlspecialchars($selected['scope'], ENT_QUOTES, 'UTF-8') ?></p>
        <div class="alert alert-warning mb-0">This is a planning entry only. The operational workflow, database changes, permissions, audit trail and tests must be implemented and verified before this module is used in the hospital.</div>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-info">Choose a module below to review its intended scope. These entries are a roadmap, not completed workflows.</div>
  <?php endif; ?>
  <div class="row">
    <?php foreach ($modules as $moduleKey => $module): ?>
      <div class="col-xl-4 col-md-6 mb-3">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <h2 class="h6 mb-2"><?= htmlspecialchars($module['name'], ENT_QUOTES, 'UTF-8') ?></h2>
              <span class="badge badge-secondary ml-2">Planned</span>
            </div>
            <p class="text-muted small"><?= htmlspecialchars($module['scope'], ENT_QUOTES, 'UTF-8') ?></p>
            <a class="btn btn-sm btn-outline-primary" href="?module=<?= rawurlencode($moduleKey) ?>">Review scope</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
