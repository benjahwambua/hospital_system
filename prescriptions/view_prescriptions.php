<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/billing.php';

require_login();
require_module_access($conn, 'clinical', 'view');

$patientId = max(0, (int)($_GET['patient_id'] ?? 0));
if ($patientId <= 0) {
    http_response_code(400);
    exit('Invalid patient ID.');
}

$patientStmt = $conn->prepare("SELECT id, full_name, patient_number FROM patients WHERE id=? LIMIT 1");
$patientStmt->bind_param('i', $patientId);
$patientStmt->execute();
$patient = $patientStmt->get_result()->fetch_assoc();
$patientStmt->close();

if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

/*
 * This page is retained as a prescription-history view, but now follows the
 * current prescription structure: medicine_id + quantity + unit_price +
 * frequency (dosage/instructions). Legacy drug_name/dosage columns are no
 * longer assumed to exist.
 */
$hasInvoice = $conn->query("SHOW COLUMNS FROM prescriptions LIKE 'invoice_id'");
$hasVisit = $conn->query("SHOW COLUMNS FROM prescriptions LIKE 'visit_id'");

$selectInvoice = ($hasInvoice && $hasInvoice->num_rows) ? ", pr.invoice_id" : ", NULL AS invoice_id";
$selectVisit = ($hasVisit && $hasVisit->num_rows) ? ", pr.visit_id" : ", NULL AS visit_id";

$sql = "SELECT pr.id, pr.quantity, pr.unit_price, pr.frequency, pr.created_at,
               ps.drug_name, ps.selling_price AS current_selling_price
               {$selectInvoice}{$selectVisit}
        FROM prescriptions pr
        LEFT JOIN pharmacy_stock ps ON ps.id=pr.medicine_id
        WHERE pr.patient_id=?
        ORDER BY pr.created_at DESC, pr.id DESC";

$prescriptions = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('i', $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $prescriptions[] = $row;
    }
    $stmt->close();
}

$canCreate = can_module_action($conn, 'clinical', 'create');
$queueByPrescription = [];
$queueTable = $conn->query("SHOW TABLES LIKE 'pharmacy_queue'");
if ($queueTable && $queueTable->num_rows) {
    $queueStmt = $conn->prepare("SELECT prescription_id, status, completed_at
                                 FROM pharmacy_queue
                                 WHERE patient_id=? ORDER BY id DESC");
    if ($queueStmt) {
        $queueStmt->bind_param('i', $patientId);
        $queueStmt->execute();
        $queueResult = $queueStmt->get_result();
        while ($q = $queueResult->fetch_assoc()) {
            $pid = (int)$q['prescription_id'];
            if (!isset($queueByPrescription[$pid])) {
                $queueByPrescription[$pid] = $q;
            }
        }
        $queueStmt->close();
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.prescriptions-page{max-width:1400px;margin:28px auto;padding:0 22px}
.prescription-card{background:#fff;border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 5px 20px rgba(31,45,61,.06);overflow:hidden}
.prescription-head{padding:22px 24px;background:linear-gradient(135deg,#f8fbff,#fff);border-bottom:1px solid #e9eef5;display:flex;justify-content:space-between;gap:18px;align-items:center}
.patient-name{font-size:24px;font-weight:800;color:#172b4d;margin:0}.patient-no{margin:4px 0 0;color:#667085;font-size:13px}
.btn{display:inline-block;padding:10px 15px;border-radius:8px;text-decoration:none;font-weight:700;border:0}
.btn-primary{background:#075b9d;color:#fff}.btn-secondary{background:#eef2f6;color:#344054}
.table-wrap{overflow:auto}.rx-table{width:100%;border-collapse:collapse}.rx-table th{background:#f8fafc;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.4px;padding:13px;text-align:left}.rx-table td{padding:14px 13px;border-top:1px solid #edf0f5;vertical-align:top}.medicine{font-weight:800;color:#172b4d}.instructions{max-width:330px;white-space:pre-wrap;color:#344054}.muted{color:#98a2b3}.badge{display:inline-block;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:800}.pending{background:#fff7ed;color:#9a3412}.completed{background:#ecfdf3;color:#166534}.no-records{text-align:center;padding:45px;color:#667085}
@media(max-width:800px){.prescription-head{align-items:flex-start;flex-direction:column}.prescriptions-page{padding:0 12px}}
</style>

<div class="prescriptions-page">
    <div class="prescription-card">
        <div class="prescription-head">
            <div>
                <h1 class="patient-name"><?= htmlspecialchars((string)$patient['full_name']) ?></h1>
                <p class="patient-no"><?= htmlspecialchars((string)$patient['patient_number']) ?></p>
            </div>
            <div>
                <?php if ($canCreate): ?>
                    <a class="btn btn-primary" href="/hospital_system/clinical/orders.php?patient_id=<?= $patientId ?>">+ New Prescription</a>
                <?php endif; ?>
                <a class="btn btn-secondary" href="/hospital_system/patients/patient_dashboard.php?id=<?= $patientId ?>&tab=clinical">Patient Dashboard</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="rx-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Medicine</th>
                        <th>Quantity</th>
                        <th>Dosage / Instructions</th>
                        <th>Unit Price</th>
                        <th>Billing</th>
                        <th>Dispensing</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($prescriptions): ?>
                    <?php foreach ($prescriptions as $rx): ?>
                        <?php
                        $queue = $queueByPrescription[(int)$rx['id']] ?? null;
                        $queueStatus = strtolower((string)($queue['status'] ?? ''));
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$rx['created_at']) ?></td>
                            <td>
                                <div class="medicine"><?= htmlspecialchars((string)($rx['drug_name'] ?? 'Medicine no longer in stock')) ?></div>
                            </td>
                            <td><?= number_format((float)($rx['quantity'] ?? 0), 0) ?></td>
                            <td class="instructions">
                                <?= !empty(trim((string)($rx['frequency'] ?? '')))
                                    ? htmlspecialchars((string)$rx['frequency'])
                                    : '<span class="muted">No dosage instruction recorded</span>' ?>
                            </td>
                            <td>KES <?= number_format((float)($rx['unit_price'] ?? $rx['current_selling_price'] ?? 0), 2) ?></td>
                            <td>
                                <?php if ((int)($rx['invoice_id'] ?? 0) > 0): ?>
                                    <a href="/hospital_system/billing/view_invoice.php?id=<?= (int)$rx['invoice_id'] ?>">Invoice #<?= (int)$rx['invoice_id'] ?></a>
                                <?php else: ?>
                                    <span class="muted">Not linked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($queueStatus === 'completed'): ?>
                                    <span class="badge completed">Dispensed</span>
                                <?php elseif ($queueStatus): ?>
                                    <span class="badge pending"><?= htmlspecialchars(ucfirst($queueStatus)) ?></span>
                                <?php else: ?>
                                    <span class="muted">Not queued</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="no-records">No prescriptions have been recorded for this patient.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>