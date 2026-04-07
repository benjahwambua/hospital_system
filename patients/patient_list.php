<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

function patient_list_parse_date(?string $value, string $fallback): string
{
    if (!$value) {
        return $fallback;
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);
    return ($date && $date->format('Y-m-d') === $value) ? $value : $fallback;
}

function patient_list_bind_params(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
}

$today = date('Y-m-d');
$search = trim((string)($_GET['search'] ?? ''));
$fromDate = patient_list_parse_date($_GET['from_date'] ?? null, '');
$toDate = patient_list_parse_date($_GET['to_date'] ?? null, '');
if ($fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
    [$fromDate, $toDate] = [$toDate, $fromDate];
}

$perPage = (int)($_GET['per_page'] ?? 50);
if (!in_array($perPage, [25, 50, 100, 250], true)) {
    $perPage = 50;
}
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
$types = '';

if ($fromDate !== '' && $toDate !== '') {
    $where[] = 'DATE(p.created_at) BETWEEN ? AND ?';
    array_push($params, $fromDate, $toDate);
    $types .= 'ss';
} elseif ($fromDate !== '') {
    $where[] = 'DATE(p.created_at) >= ?';
    $params[] = $fromDate;
    $types .= 's';
} elseif ($toDate !== '') {
    $where[] = 'DATE(p.created_at) <= ?';
    $params[] = $toDate;
    $types .= 's';
}

if ($search !== '') {
    $where[] = '(p.full_name LIKE ? OR p.patient_number LIKE ? OR p.phone LIKE ? OR p.next_of_kin_name LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}

$whereSql = implode(' AND ', $where);
if ($whereSql === '') {
    $whereSql = '1=1';
}

$countSql = "SELECT COUNT(*) AS total FROM patients p WHERE {$whereSql}";
$countStmt = $conn->prepare($countSql);
patient_list_bind_params($countStmt, $types, $params);
$countStmt->execute();
$totalPatients = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$totalPages = max(1, (int)ceil($totalPatients / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$listSql = "
    SELECT
        p.id,
        p.patient_number,
        p.full_name,
        p.gender,
        p.age,
        p.phone,
        p.next_of_kin_name,
        p.next_of_kin_phone,
        p.appointment_date,
        p.created_at,
        u.full_name AS doctor_name
    FROM patients p
    LEFT JOIN users u ON p.doctor_id = u.id
    WHERE {$whereSql}
    ORDER BY p.created_at DESC, p.id DESC
    LIMIT ? OFFSET ?
";
$listParams = $params;
$listParams[] = $perPage;
$listParams[] = $offset;
$listTypes = $types . 'ii';

$listStmt = $conn->prepare($listSql);
patient_list_bind_params($listStmt, $listTypes, $listParams);
$listStmt->execute();
$patients = $listStmt->get_result();

$summarySql = "
    SELECT
        COUNT(*) AS total_registered,
        COALESCE(SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END), 0) AS male_count,
        COALESCE(SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END), 0) AS female_count,
        COALESCE(SUM(CASE WHEN appointment_date IS NOT NULL AND appointment_date <> '' THEN 1 ELSE 0 END), 0) AS appointment_count
    FROM patients p
    WHERE {$whereSql}
";
$summaryStmt = $conn->prepare($summarySql);
patient_list_bind_params($summaryStmt, $types, $params);
$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc() ?: [
    'total_registered' => 0,
    'male_count' => 0,
    'female_count' => 0,
    'appointment_count' => 0,
];
$summaryStmt->close();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportSql = "
        SELECT
            p.patient_number,
            p.full_name,
            p.gender,
            p.age,
            p.phone,
            p.next_of_kin_name,
            p.next_of_kin_phone,
            u.full_name AS doctor_name,
            p.appointment_date,
            p.created_at
        FROM patients p
        LEFT JOIN users u ON p.doctor_id = u.id
        WHERE {$whereSql}
        ORDER BY p.created_at DESC, p.id DESC
    ";
    $exportStmt = $conn->prepare($exportSql);
    patient_list_bind_params($exportStmt, $types, $params);
    $exportStmt->execute();
    $exportRes = $exportStmt->get_result();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="patient_register_' . $fromDate . '_to_' . $toDate . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Patient Number', 'Full Name', 'Gender', 'Age', 'Phone', 'Next of Kin', 'NOK Phone', 'Doctor', 'Appointment Date', 'Registered At']);

    while ($row = $exportRes->fetch_assoc()) {
        fputcsv($output, [
            $row['patient_number'],
            $row['full_name'],
            $row['gender'],
            $row['age'],
            $row['phone'],
            $row['next_of_kin_name'],
            $row['next_of_kin_phone'],
            $row['doctor_name'],
            $row['appointment_date'],
            $row['created_at'],
        ]);
    }

    fclose($output);
    $exportStmt->close();
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.main-content { padding: 24px; max-width: 1500px; margin: 0 auto; }
.page-shell { display: flex; flex-direction: column; gap: 20px; }
.page-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05); }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
.page-header h3 { margin: 0; color: #1d4ed8; }
.page-header p { margin: 6px 0 0; color: #6b7280; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
.summary-card small { display: block; text-transform: uppercase; font-weight: 700; color: #6b7280; margin-bottom: 6px; }
.summary-card strong { font-size: 1.4rem; color: #111827; }
.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; align-items: end; }
.filter-field label { display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 6px; }
.filter-field input, .filter-field select { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px 12px; }
.filter-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn { border: none; border-radius: 10px; padding: 10px 14px; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; }
.btn-primary { background: #2563eb; color: #fff; }
.btn-secondary { background: #eef2ff; color: #3730a3; }
.btn-light { background: #f3f4f6; color: #111827; }
.btn-success { background: #16a34a; color: #fff; }
.table-wrap { overflow: auto; }
.table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.table th, .table td { padding: 12px 14px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
.table th { background: #f8fafc; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.table td { word-break: break-word; white-space: normal; }
.table tbody tr:hover { background: #f8fbff; }
.patient-no { font-weight: 700; color: #1e3a8a; }
.badge { display: inline-flex; padding: 4px 8px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-done { background: #dcfce7; color: #166534; }
.badge-na { background: #e5e7eb; color: #374151; }
.action-stack { display: flex; flex-wrap: wrap; gap: 6px; }
.no-results { text-align: center; padding: 48px 16px; color: #6b7280; }
.pagination { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
.pagination-links { display: flex; gap: 8px; flex-wrap: wrap; }
.pagination-links a, .pagination-links span { padding: 8px 12px; border-radius: 10px; text-decoration: none; background: #f3f4f6; color: #111827; }
.pagination-links .active { background: #2563eb; color: #fff; }
.result-note { margin-top: 12px; padding: 12px 14px; border-radius: 12px; background: #eff6ff; color: #1d4ed8; }
@media (max-width: 768px) {
    .main-content { padding: 16px; }
}
</style>

<div class="main-content">
    <div class="page-shell">
        <div class="page-card">
            <div class="page-header">
                <div>
                    <h3>Patient Register</h3>
                    <p>Shows all registered patients by default, with optional date-range filtering, CSV export, and paginated browsing for front-desk operations.</p>
                </div>
                <div class="filter-actions">
                    <a href="/hospital_system/patients/add_patient.php" class="btn btn-success">+ Add New Patient</a>
                    <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['from_date' => $fromDate, 'to_date' => $toDate, 'search' => $search, 'per_page' => $perPage, 'page' => 1, 'export' => 'csv']))) ?>" class="btn btn-light">Export CSV</a>
                </div>
            </div>
        </div>

        <div class="summary-grid">
            <div class="page-card summary-card">
                <small>Total Registered</small>
                <strong><?= number_format((int)$summary['total_registered']) ?></strong>
            </div>
            <div class="page-card summary-card">
                <small>Male Patients</small>
                <strong><?= number_format((int)$summary['male_count']) ?></strong>
            </div>
            <div class="page-card summary-card">
                <small>Female Patients</small>
                <strong><?= number_format((int)$summary['female_count']) ?></strong>
            </div>
            <div class="page-card summary-card">
                <small>With Appointments</small>
                <strong><?= number_format((int)$summary['appointment_count']) ?></strong>
            </div>
        </div>

        <div class="page-card">
            <form method="get">
                <input type="hidden" name="page" value="1">
                <div class="filter-grid">
                    <div class="filter-field">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name, patient no, phone, next of kin">
                    </div>
                    <div class="filter-field">
                        <label for="from_date">From date</label>
                        <input type="date" id="from_date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>">
                    </div>
                    <div class="filter-field">
                        <label for="to_date">To date</label>
                        <input type="date" id="to_date" name="to_date" value="<?= htmlspecialchars($toDate) ?>">
                    </div>
                    <div class="filter-field">
                        <label for="per_page">Rows per page</label>
                        <select id="per_page" name="per_page">
                            <?php foreach ([25, 50, 100, 250] as $size): ?>
                                <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="?" class="btn btn-secondary">Show All</a>
                    </div>
                </div>
            </form>

            <div class="result-note">
                <?php
                $dateScope = 'for all registration dates';
                if ($fromDate !== '' && $toDate !== '') {
                    $dateScope = 'from <strong>' . htmlspecialchars($fromDate) . '</strong> to <strong>' . htmlspecialchars($toDate) . '</strong>';
                } elseif ($fromDate !== '') {
                    $dateScope = 'from <strong>' . htmlspecialchars($fromDate) . '</strong> onwards';
                } elseif ($toDate !== '') {
                    $dateScope = 'up to <strong>' . htmlspecialchars($toDate) . '</strong>';
                }
                ?>
                Showing <strong><?= number_format($totalPatients) ?></strong> patient(s) <?= $dateScope ?><?= $search !== '' ? ' matching “' . htmlspecialchars($search) . '”' : '' ?>.
            </div>
        </div>

        <div class="page-card">
            <?php if ($patients->num_rows > 0): ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Patient Number</th>
                                <th>Full Name</th>
                                <th>Gender</th>
                                <th>Age</th>
                                <th>Phone</th>
                                <th>Next of Kin</th>
                                <th>NOK Phone</th>
                                <th>Doctor</th>
                                <th>Appointment</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rowNumber = $offset + 1; ?>
                            <?php while ($p = $patients->fetch_assoc()): ?>
                                <?php
                                $appointmentLabel = '<span class="badge badge-na">N/A</span>';
                                if (!empty($p['appointment_date'])) {
                                    $appointmentTime = strtotime($p['appointment_date']);
                                    if ($appointmentTime !== false) {
                                        if ($appointmentTime >= time()) {
                                            $appointmentLabel = '<span class="badge badge-pending">Scheduled ' . date('M d, H:i', $appointmentTime) . '</span>';
                                        } else {
                                            $appointmentLabel = '<span class="badge badge-done">Completed ' . date('M d, H:i', $appointmentTime) . '</span>';
                                        }
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= $rowNumber++ ?></td>
                                    <td class="patient-no"><?= htmlspecialchars($p['patient_number']) ?></td>
                                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                                    <td><?= htmlspecialchars($p['gender']) ?></td>
                                    <td><?= (int)$p['age'] ?></td>
                                    <td><?= htmlspecialchars($p['phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($p['next_of_kin_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($p['next_of_kin_phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($p['doctor_name'] ?? 'N/A') ?></td>
                                    <td><?= $appointmentLabel ?></td>
                                    <td><?= htmlspecialchars(date('M d, Y H:i', strtotime($p['created_at']))) ?></td>
                                    <td>
                                        <div class="action-stack">
                                            <a href="/hospital_system/patients/patient_dashboard.php?id=<?= (int)$p['id'] ?>" class="btn btn-primary">View</a>
                                            <a href="/hospital_system/patients/edit_patient.php?id=<?= (int)$p['id'] ?>" class="btn btn-light">Edit</a>
                                            <a href="/hospital_system/patients/appointments.php?id=<?= (int)$p['id'] ?>" class="btn btn-secondary">Schedule</a>
                                            <a href="/hospital_system/reports/patient_medical_report.php?id=<?= (int)$p['id'] ?>" target="_blank" rel="noopener" class="btn btn-success">Print</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination" style="margin-top: 16px;">
                    <div>Page <?= $page ?> of <?= $totalPages ?></div>
                    <div class="pagination-links">
                        <?php $baseQuery = ['search' => $search, 'from_date' => $fromDate, 'to_date' => $toDate, 'per_page' => $perPage]; ?>
                        <?php if ($page > 1): ?>
                            <a href="?<?= htmlspecialchars(http_build_query(array_merge($baseQuery, ['page' => $page - 1]))) ?>">Previous</a>
                        <?php endif; ?>
                        <span class="active"><?= $page ?></span>
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= htmlspecialchars(http_build_query(array_merge($baseQuery, ['page' => $page + 1]))) ?>">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p>No patients found for the selected date range and search filters.</p>
                    <p><a href="?" class="btn btn-secondary">Show All</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$listStmt->close();
include __DIR__ . '/../includes/footer.php';
?>
