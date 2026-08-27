<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// Handle search
$search = '';
$where = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string(trim($_GET['search']));
    $where = " WHERE (p.full_name LIKE '%{$search}%' OR p.patient_number LIKE '%{$search}%' OR p.phone LIKE '%{$search}%' OR p.next_of_kin_name LIKE '%{$search}%')";
}

// Fetch all patients
$patients = $conn->query("
    SELECT p.id, p.patient_number, p.full_name, p.gender, p.age, p.phone, p.next_of_kin_name, p.next_of_kin_phone, u.full_name AS doctor_name, p.appointment_date, p.created_at
    FROM patients p
    LEFT JOIN users u ON p.doctor_id = u.id
    {$where}
    ORDER BY p.created_at DESC
");
?>

<style>
.main-content {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}
.card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}
.card h3 {
    color: #007bff;
    margin-top: 0;
    margin-bottom: 20px;
}
.search-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.search-box {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.search-box input {
    flex: 1;
    min-width: 250px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}
.search-box input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0,123,255,0.3);
}
.search-box button {
    padding: 10px 25px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}
.search-box button:hover {
    background: #0056b3;
}
.clear-btn {
    padding: 10px 20px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
.clear-btn:hover {
    background: #5a6268;
}
.search-result {
    color: #666;
    font-size: 14px;
    margin-top: 10px;
    padding: 10px;
    background: #e7f3ff;
    border-left: 4px solid #007bff;
    border-radius: 4px;
}
.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 12px;
    margin-right: 5px;
}
.btn-primary {
    background: #007bff;
    color: white;
}
.btn-primary:hover {
    background: #0056b3;
}
.btn-warning {
    background: #ffc107;
    color: #000;
}
.btn-warning:hover {
    background: #e0a800;
}
.btn-success {
    background: #28a745;
    color: white;
}
.btn-success:hover {
    background: #218838;
}
.btn-info {
    background: #17a2b8;
    color: white;
}
.btn-info:hover {
    background: #138496;
}
.btn-sm {
    padding: 4px 8px;
    font-size: 11px;
}
.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    overflow-x: auto;
    display: block;
}
.table thead {
    background: #007bff;
    color: white;
    display: table;
    width: 100%;
}
.table th, .table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
.table tbody {
    display: block;
    max-height: 600px;
    overflow-y: auto;
}
.table tbody tr {
    display: table;
    width: 100%;
}
.table tbody tr:hover {
    background: #f5f5f5;
}
.add-btn {
    margin-bottom: 15px;
}
.add-btn a {
    background: #28a745;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    font-weight: 600;
}
.add-btn a:hover {
    background: #218838;
}
.appointment-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
}
.appointment-pending {
    background: #fff3cd;
    color: #856404;
}
.appointment-done {
    background: #d4edda;
    color: #155724;
}
.no-results {
    padding: 40px 20px;
    text-align: center;
    color: #666;
    font-size: 16px;
}
@media (max-width: 768px) {
    .search-box {
        flex-direction: column;
    }
    .search-box input {
        min-width: 100%;
    }
    .search-box button,
    .clear-btn {
        width: 100%;
    }
    .table {
        font-size: 12px;
    }
    .table th, .table td {
        padding: 8px;
    }
    .btn {
        padding: 4px 6px;
        font-size: 10px;
        margin-bottom: 5px;
    }
    .card {
        padding: 15px;
    }
}
</style>

<div class="main-content">
    <div class="card">
        <h3>Patient List</h3>
        
        <div class="add-btn">
            <a href="/hospital_system/patients/add_patient.php">+ Add New Patient</a>
        </div>

        <!-- Search Box -->
        <div class="search-section">
            <form method="get" class="search-box">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search by: Full Name, Patient Number, Phone, or Next of Kin..." 
                    value="<?= htmlspecialchars($search); ?>"
                    autocomplete="off"
                >
                <button type="submit">Search</button>
                <?php if($search): ?>
                <a href="/hospital_system/patients/patient_list.php" class="clear-btn">Clear Search</a>
                <?php endif; ?>
            </form>

            <?php if($search): ?>
            <div class="search-result">
                <strong>Search Results:</strong> Found <?= $patients->num_rows; ?> patient(s) matching "<strong><?= htmlspecialchars($search); ?></strong>"
            </div>
            <?php endif; ?>
        </div>

        <!-- Patients Table -->
        <?php if($patients->num_rows > 0): ?>
        <table class="table table-bordered">
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
                <?php $count = 1; while($p = $patients->fetch_assoc()): 
                    $appt_status = '';
                    $appt_display = 'N/A';
                    if ($p['appointment_date']) {
                        $appt_time = strtotime($p['appointment_date']);
                        $now = time();
                        if ($appt_time > $now) {
                            $appt_status = 'appointment-pending';
                            $appt_display = '<span class="appointment-badge appointment-pending">Scheduled<br>' . date('M d, H:i', $appt_time) . '</span>';
                        } else {
                            $appt_status = 'appointment-done';
                            $appt_display = '<span class="appointment-badge appointment-done">Completed<br>' . date('M d, H:i', $appt_time) . '</span>';
                        }
                    }
                ?>
                <tr>
                    <td><?= $count++; ?></td>
                    <td><strong><?= htmlspecialchars($p['patient_number']); ?></strong></td>
                    <td><?= htmlspecialchars($p['full_name']); ?></td>
                    <td><?= htmlspecialchars($p['gender']); ?></td>
                    <td><?= (int)$p['age']; ?></td>
                    <td><?= htmlspecialchars($p['phone']); ?></td>
                    <td><?= htmlspecialchars($p['next_of_kin_name'] ?? 'N/A'); ?></td>
                    <td><?= htmlspecialchars($p['next_of_kin_phone'] ?? 'N/A'); ?></td>
                    <td><?= htmlspecialchars($p['doctor_name'] ?? 'N/A'); ?></td>
                    <td><?= $appt_display; ?></td>
                    <td><?= date('M d, Y', strtotime($p['created_at'])); ?></td>
                    <td>
                        <a href="/hospital_system/patients/patient_dashboard.php?id=<?= $p['id']; ?>" class="btn btn-primary btn-sm">View</a>
                        <a href="/hospital_system/patients/edit_patient.php?id=<?= $p['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="/hospital_system/patients/appointments.php?id=<?= $p['id']; ?>" class="btn btn-info btn-sm">Schedule</a>
                        <a href="/hospital_system/reports/patient_medical_report.php?id=<?= $p['id']; ?>"target="_blank"class="btn btn-success btn-sm">Print Report</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="no-results">
                <p>
                    <?php if($search): ?>
                        No patients found matching "<strong><?= htmlspecialchars($search); ?></strong>"<br>
                        <a href="/hospital_system/patients/patient_list.php" style="color:#007bff;text-decoration:none;">View all patients</a>
                    <?php else: ?>
                        No patients registered yet.<br>
                        <a href="/hospital_system/patients/add_patient.php" style="color:#28a745;text-decoration:none;">Register a new patient</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>