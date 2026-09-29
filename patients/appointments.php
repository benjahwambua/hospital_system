<style>
.appointments-page{background:#f5f7fb;min-height:calc(100vh - 72px);padding:28px 24px 40px}.appointments-shell{max-width:1500px;margin:0 auto}.appointments-hero{background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;border-radius:18px;padding:24px 28px;display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:18px}.appointments-kicker{font-size:10px;text-transform:uppercase;letter-spacing:1.6px;font-weight:800;color:#bfe8ff}.appointments-hero h1{margin:4px 0 5px;color:#fff;font-size:26px;font-weight:800}.appointments-hero p{margin:0;color:rgba(255,255,255,.8);font-size:13px}.hero-btn{background:#fff;color:#063b73;border-radius:9px;padding:10px 15px;text-decoration:none;font-weight:800;white-space:nowrap}.appointments-toolbar{background:#fff;border:1px solid #e5eaf1;border-radius:14px;padding:16px 18px;margin-bottom:16px;box-shadow:0 4px 16px rgba(31,45,61,.05);display:flex;gap:10px;align-items:center}.appointment-search{display:flex;flex:1;gap:8px}.appointment-search input{height:42px;flex:1;border:1px solid #d7dee8;border-radius:8px;padding:0 13px;font-size:13px;outline:none}.appointment-search input:focus{border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}.appointment-search button{height:42px;border:0;border-radius:8px;background:#075b9d;color:#fff;padding:0 18px;font-weight:800}.clear-btn{height:42px;display:inline-flex;align-items:center;padding:0 15px;border-radius:8px;background:#f1f4f8;color:#475467;text-decoration:none;font-size:12px;font-weight:800}.appointments-card{background:#fff;border:1px solid #e5eaf1;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(31,45,61,.05)}.appointment-table-wrap{overflow:auto}.appointment-table{width:100%;min-width:1050px;border-collapse:separate;border-spacing:0}.appointment-table th{background:#f8fafc;color:#667085;padding:12px 15px;border-bottom:1px solid #e5eaf1;font-size:10px;text-transform:uppercase;letter-spacing:.45px;text-align:left;white-space:nowrap}.appointment-table td{padding:14px 15px;border-bottom:1px solid #edf0f5;color:#344054;font-size:12px;vertical-align:middle}.appointment-table tbody tr:hover td{background:#f9fbfd}.appt-name{font-weight:800;color:#25324a}.appt-number{font-size:10px;color:#075b9d;font-weight:800;margin-top:3px}.appt-time{font-weight:800;color:#25324a}.appt-date{font-size:10px;color:#667085;margin-top:3px}.appt-today{color:#027a48;font-weight:800}.reason-cell{max-width:230px}.status-badge{display:inline-flex;padding:5px 9px;border-radius:999px;font-size:9px;font-weight:800;white-space:nowrap}.status-closed{background:#ecfdf3;color:#027a48}.status-appeared{background:#eef4ff;color:#2457a6}.status-waiting{background:#fff7e6;color:#9a6700}.appt-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.appt-actions a,.appt-actions button{border:0;border-radius:7px;padding:7px 10px;font-size:10px;font-weight:800;text-decoration:none;cursor:pointer;white-space:nowrap}.open-patient{background:#075b9d;color:#fff}.close-appt{background:#f2f4f7;color:#475467}.closed-note{font-size:10px;color:#98a2b3;font-weight:700}.notice{padding:10px 13px;border-radius:8px;margin-bottom:12px;font-size:12px}.notice-success{background:#ecfdf3;color:#027a48;border:1px solid #ccefdc}.notice-error{background:#fef3f2;color:#b42318;border:1px solid #fecdca}.empty-appointments{padding:55px 20px;text-align:center;color:#667085}.empty-appointments i{display:block;font-size:28px;color:#075b9d;margin-bottom:12px}.empty-appointments strong{display:block;color:#25324a;font-size:15px;margin-bottom:5px}.empty-appointments span{font-size:12px}@media(max-width:700px){.appointments-page{padding:18px 12px}.appointments-hero{padding:20px;align-items:flex-start;flex-direction:column}.appointments-toolbar{align-items:stretch;flex-direction:column}.appointment-search{width:100%}.appointment-search button{padding:0 13px}}
</style><?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_module_access($conn, 'clinical', 'view');

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token'];
$notice=''; $error='';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_appointment'])) {
    require_module_access($conn, 'clinical', 'edit');
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $error='Invalid security token.';
    } else {
        $appointmentId=(int)($_POST['appointment_id'] ?? 0);
        $stmt=$conn->prepare("SELECT id,status,visit_id FROM appointments WHERE id=? LIMIT 1");
        $stmt->bind_param('i',$appointmentId);
        $stmt->execute();
        $appointment=$stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$appointment) {
            $error='Appointment not found.';
        } elseif (strtolower((string)$appointment['status'])==='closed') {
            $notice='Appointment is already closed.';
        } else {
            $stmt=$conn->prepare("UPDATE appointments SET status='Closed' WHERE id=? AND status NOT IN ('Closed','Cancelled','Completed')");
            $stmt->bind_param('i',$appointmentId);
            if ($stmt->execute() && $stmt->affected_rows>0) $notice='Appointment closed successfully.';
            else $error='Unable to close the appointment. Please confirm that the patient has appeared.';
            $stmt->close();
        }
    }
}

// Search Logic: Filter the list by Patient Name or Number
$search = $_GET['q'] ?? '';

// Fetch the Appointment List (The "Heartbeat" Register)
// Shows today's and future appointments
$query = "
    SELECT a.*, p.full_name as p_name, p.patient_number, u.full_name as d_name, v.id AS linked_visit_id 
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON a.doctor_id = u.id
    LEFT JOIN visits v ON v.id = a.visit_id
    WHERE a.appointment_date >= CURDATE()
";

if ($search) {
    $safe_search = $conn->real_escape_string($search);
    $query .= " AND (p.full_name LIKE '%$safe_search%' OR p.patient_number LIKE '%$safe_search%')";
}

$query .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
$register = $conn->query($query);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="content-wrapper" style="padding: 30px; background: #f8fafc; min-height: 100vh;">
    <div style="max-width: 1200px; margin: auto;">
        
        <div style="display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:22px;background:#fff;padding:22px 24px;border-radius:14px;border:1px solid #e7edf4;box-shadow:0 5px 18px rgba(31,45,61,.06);">
            <div>
                <h2 style="margin:0; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 24px;">📋</span> Daily Appointment Register
                </h2>
                <p style="margin:5px 0 0;color:#64748b;font-size:14px;">Appointments can be closed from Reception when the patient has appeared or when the appointment is being marked as closed/no-show.</p><div style="margin-top:12px;display:flex;gap:8px;"><a href="../appointments/appointments.php<?=isset($_GET["id"])?"?patient_id=".(int)$_GET["id"]:""?>" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Schedule Appointment</a></div>            <?php if ($notice): ?><div style="margin-top:10px;color:#166534;"><?= htmlspecialchars($notice) ?></div><?php endif; ?>            <?php if ($error): ?><div style="margin-top:10px;color:#991b1b;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            </div>

            <form method="GET" style="display:flex;gap:0;width:400px;">
                <input type="text" name="q" placeholder="Search name or patient ID..." value="<?= htmlspecialchars($search) ?>" 
                       style="flex: 1; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 8px 0 0 8px; outline: none; font-size: 14px;">
                <button type="submit" style="padding: 12px 20px; background: #2563eb; color: white; border: none; border-radius: 0 8px 8px 0; cursor: pointer; font-weight: 600;">
                    SEARCH
                </button>
            </form>
        </div>

        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f1f5f9; text-align: left;">
                        <th style="padding: 18px 20px; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Patient Identity</th>
                        <th style="padding: 18px 20px; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Appointment Slot</th>
                        <th style="padding: 18px 20px; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Assigned Clinician</th>
                        <th style="padding: 18px 20px; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Visit Reason</th>
                        <th style="padding: 18px 20px; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; text-align: center;">Status</th>                        <th style="padding: 18px 20px; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($register && $register->num_rows > 0): ?>
                        <?php while($row = $register->fetch_assoc()): 
                            $is_today = ($row['appointment_date'] == date('Y-m-d'));
                            $is_closed = strtolower((string)($row['status'] ?? '')) === 'closed';
                            $has_appeared = (int)($row['linked_visit_id'] ?? 0) > 0;
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s; cursor: default;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                <td style="padding: 15px 20px;">
                                    <div style="font-weight: 700; color: #1e293b; font-size: 15px;"><?= htmlspecialchars($row['p_name']) ?></div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">ID: <?= $row['patient_number'] ?></div>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <div style="color: #334155; font-weight: 600; font-size: 14px;">
                                        <?= date('h:i A', strtotime($row['appointment_time'])) ?>
                                    </div>
                                    <div style="font-size: 12px; color: <?= $is_today ? '#059669' : '#64748b' ?>; font-weight: <?= $is_today ? '700' : 'normal' ?>;">
                                        <?= $is_today ? 'TODAY' : date('d M Y', strtotime($row['appointment_date'])) ?>
                                    </div>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <div style="color: #475569; font-size: 14px;">Dr. <?= htmlspecialchars($row['d_name']) ?></div>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <div style="font-size: 13px; color: #64748b; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($row['reason'] ?: 'Routine Checkup') ?>
                                    </div>
                                </td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <?php if ($is_closed): ?>
                                        <span style="padding:6px 10px;border-radius:999px;background:#dcfce7;color:#166534;font-size:11px;font-weight:700;">CLOSED</span>
                                    <?php elseif ($has_appeared): ?>
                                        <span style="padding:6px 10px;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-size:11px;font-weight:700;">PATIENT APPEARED</span>
                                    <?php else: ?>
                                        <span style="padding:6px 10px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;">NOT ARRIVED</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <?php if (!$is_closed): ?>
                                        <?php if ($has_appeared): ?>
                                            <a href="patient_dashboard.php?id=<?= $row['patient_id'] ?>&appointment_id=<?= (int)$row['id'] ?>"
                                               style="display:inline-block;padding:10px 16px;background:#059669;color:white;border-radius:8px;text-decoration:none;font-size:13px;font-weight:700;">
                                               OPEN PATIENT
                                            </a>
                                        <?php endif; ?>
                                        <?php if (can_edit($conn, 'clinical')): ?>
                                            <form method="post" style="display:inline-block;margin:0 0 0 5px;" onsubmit="return confirm('Change this appointment status to Closed?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="appointment_id" value="<?= (int)$row['id'] ?>">
                                                <button type="submit" name="close_appointment" style="padding:10px 16px;background:#475569;color:white;border:none;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;">
                                                    <?= $has_appeared ? 'CHANGE STATUS TO CLOSED' : 'CLOSE / NO-SHOW' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#64748b;font-size:12px;font-weight:600;">Appointment closed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="padding: 60px; text-align: center;">
                                <div style="font-size: 40px; margin-bottom: 10px;">Empty</div>
                                <div style="color: #94a3b8; font-size: 16px;">No appointments found in the register.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>