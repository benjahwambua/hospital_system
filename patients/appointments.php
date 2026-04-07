<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

// Search Logic: Filter the list by Patient Name or Number
$search = $_GET['q'] ?? '';

// Fetch the Appointment List (The "Heartbeat" Register)
// Shows today's and future appointments
$query = "
    SELECT a.*, p.full_name as p_name, p.patient_number, u.full_name as d_name 
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON a.doctor_id = u.id
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
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div>
                <h2 style="margin:0; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 24px;">📋</span> Daily Appointment Register
                </h2>
                <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">Managing patient flow from Registration to Consultation.</p>
            </div>

            <form method="GET" style="display: flex; gap: 0; width: 400px;">
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
                        <th style="padding: 18px 20px; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($register && $register->num_rows > 0): ?>
                        <?php while($row = $register->fetch_assoc()): 
                            $is_today = ($row['appointment_date'] == date('Y-m-d'));
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
                                    <a href="patient_dashboard.php?id=<?= $row['patient_id'] ?>" 
                                       style="display: inline-block; padding: 10px 20px; background: #059669; color: white; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 700; transition: transform 0.1s;"
                                       onmousedown="this.style.transform='scale(0.95)'" onmouseup="this.style.transform='scale(1)'">
                                       SEE PATIENT
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 60px; text-align: center;">
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