<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'doctor', 'nurse', 'receptionist']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// 1. Hospital Configuration
$hospital_wards = [
    'General Ward (Male)'   => 6,
    'General Ward (Female)' => 6,
    'Maternity Ward'        => 6,
    'Pediatric Ward'        => 6,
    'ICU'                   => 6
];

// 2. Fetch Active Admissions
$sql = "SELECT a.id, a.patient_id, a.ward_name, a.bed_number, p.full_name AS patient_name, 
               a.admit_date, a.attending_doctor, a.reason 
        FROM admissions a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        WHERE a.status = 'Admitted'";

$result = $conn->query($sql);

$occupied_beds = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $w_name = trim($row['ward_name']);
        $b_num  = (int)$row['bed_number'];
        
        $occupied_beds[$w_name][$b_num] = [
            'id'           => $row['id'], // This is the Admission ID for discharging
            'patient_id'   => $row['patient_id'],
            'name'         => $row['patient_name'] ?? 'Unknown Patient',
            'date'         => $row['admit_date'],
            'doctor'       => $row['attending_doctor'] ?? 'Not Assigned',
            'reason'       => $row['reason'] ?? 'N/A'
        ];
    }
}
?>

<style>
    /* Professional Spaced Grid */
    .bed-grid {
        display: grid;
        /* auto-fit makes them fill the space; minmax(120px) makes them much larger */
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); 
        gap: 20px;
        justify-content: center; /* Centers the grid if there are few beds */
        max-width: 1100px; /* Prevents them from getting absurdly wide on huge monitors */
        margin: 0 auto; 
    }

    .bed-box {
        aspect-ratio: 1.1 / 1; /* Slightly wider than tall */
        border-radius: 12px; 
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        justify-content: center;
        text-decoration: none !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .bed-box:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 12px 20px rgba(0,0,0,0.15);
    }

    /* Vibrant Gradients for high visibility */
    .bed-occupied { 
        background: linear-gradient(135deg, #f35a4a 0%, #d92d1c 100%); 
        color: white; 
        border: none; 
    }
    .bed-available { 
        background: linear-gradient(135deg, #24dca0 0%, #17a673 100%); 
        color: white; 
        border: none; 
    }
    
    .bed-icon { font-size: 2rem; margin-bottom: 5px; opacity: 0.9; }
    .bed-number { font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    /* Popover Styling */
    .popover { 
        border: none; 
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.3); 
        min-width: 280px; 
    }
    .popover-header { 
        background-color: #4e73df; 
        color: white; 
        font-weight: bold; 
        padding: 12px;
        text-align: center; 
    }
    .popover-body { padding: 15px; font-size: 0.9rem; line-height: 1.6; }
    .pop-btn-group { 
        margin-top: 15px; 
        padding-top: 12px; 
        border-top: 1px solid #eee; 
    }
</style>

<div class="main-content">
    <div class="container-fluid pt-4">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Clinical Ward Management</h1>
            <div class="d-flex align-items-center">
                <a href="discharge_patient.php" class="btn btn-outline-danger shadow-sm mr-2">
                    <i class="fas fa-sign-out-alt mr-2"></i>Discharge Patient
                </a>
                <a href="admit_patient.php" class="btn btn-primary shadow-sm">
                    <i class="fas fa-plus mr-2"></i>New Admission
                </a>
            </div>
        </div>

        <?php foreach ($hospital_wards as $ward_name => $total_beds): ?>
            <?php 
                $current_ward_occupied = $occupied_beds[$ward_name] ?? [];
                $occupied_count = count($current_ward_occupied);
                $available_count = $total_beds - $occupied_count;
            ?>
            <div class="card shadow mb-5 border-0">
                <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold text-dark">
                        <i class="fas fa-hospital-symbol text-primary mr-2"></i><?= htmlspecialchars($ward_name) ?>
                    </h5>
                    <div class="d-none d-sm-block">
                        <span class="badge badge-pill badge-success px-3 py-2 mr-2"><?= $available_count ?> Free</span>
                        <span class="badge badge-pill badge-danger px-3 py-2"><?= $occupied_count ?> Occupied</span>
                    </div>
                </div>
                <div class="card-body bg-light py-4">
                    <div class="bed-grid">
                        <?php for($i = 1; $i <= $total_beds; $i++): ?>
                            <?php 
                                $is_occupied = isset($current_ward_occupied[$i]);
                                $bed_class = $is_occupied ? 'bed-occupied' : 'bed-available';
                                
                                if ($is_occupied) {
                                    $p = $current_ward_occupied[$i];
                                    $link = "../patients/patient_dashboard.php?id=" . $p['patient_id'];
                                    
                                    // RESTORED DISCHARGE LINK
                                    $pop_title = "Bed " . $i . ": " . htmlspecialchars($p['name']);
                                    $pop_content = "<div><b>Doctor:</b> ".htmlspecialchars($p['doctor'])."</div>";
                                    $pop_content .= "<div><b>Since:</b> ".date('d M Y, H:i', strtotime($p['date']))."</div>";
                                    $pop_content .= "<div><b>Reason:</b> ".htmlspecialchars($p['reason'])."</div>";
                                    
                                    $pop_content .= "<div class='pop-btn-group d-flex justify-content-between'>";
                                    $pop_content .= "<a href='discharge_patient.php?id=".$p['id']."' class='btn btn-sm btn-danger'><i class='fas fa-sign-out-alt mr-1'></i> Discharge</a>";
                                    $pop_content .= "<span class='small text-muted align-self-center'>Click bed for Dashboard</span>";
                                    $pop_content .= "</div>";
                                    
                                    $attr = 'data-toggle="popover" data-trigger="hover" data-html="true" title="'.$pop_title.'" data-content="'.$pop_content.'"';
                                } else {
                                    $link = "admit_patient.php?ward=".urlencode($ward_name)."&bed=".$i;
                                    $attr = 'data-toggle="tooltip" title="Assign Patient to Bed '.$i.'"';
                                }
                            ?>
                            
                            <a href="<?= $link ?>" class="bed-box <?= $bed_class ?>" <?= $attr ?>>
                                <i class="fas fa-bed bed-icon"></i>
                                <span class="bed-number">BED <?= $i ?></span>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Init Tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Init Popovers (Hover Details)
        $('[data-toggle="popover"]').popover({
            placement: 'top',
            boundary: 'viewport',
            sanitize: false,
            delay: { "show": 100, "hide": 400 } // Smooth delay for better UX
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?><?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'doctor', 'nurse', 'receptionist']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// 1. Hospital Configuration
$hospital_wards = [
    'General Ward (Male)'   => 6,
    'General Ward (Female)' => 6,
    'Maternity Ward'        => 6,
    'Pediatric Ward'        => 6,
    'ICU'                   => 6
];

// 2. Fetch Active Admissions
$sql = "SELECT a.id, a.patient_id, a.ward_name, a.bed_number, p.full_name AS patient_name, 
               a.admit_date, a.attending_doctor, a.reason 
        FROM admissions a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        WHERE a.status = 'Admitted'";

$result = $conn->query($sql);

$occupied_beds = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $w_name = trim($row['ward_name']);
        $b_num  = (int)$row['bed_number'];
        
        $occupied_beds[$w_name][$b_num] = [
            'id'           => $row['id'], // Admission ID
            'patient_id'   => $row['patient_id'],
            'name'         => $row['patient_name'] ?? 'Unknown Patient',
            'date'         => $row['admit_date'],
            'doctor'       => $row['attending_doctor'] ?? 'Not Assigned',
            'reason'       => $row['reason'] ?? 'N/A'
        ];
    }
}
?>

<style>
    .bed-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); 
        gap: 20px;
        justify-content: center; 
        max-width: 1100px; 
        margin: 0 auto; 
    }

    .bed-box {
        aspect-ratio: 1.1 / 1; 
        border-radius: 12px; 
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        justify-content: center;
        text-decoration: none !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .bed-box:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 12px 20px rgba(0,0,0,0.15);
    }

    .bed-occupied { 
        background: linear-gradient(135deg, #f35a4a 0%, #d92d1c 100%); 
        color: white; 
        border: none; 
    }
    .bed-available { 
        background: linear-gradient(135deg, #24dca0 0%, #17a673 100%); 
        color: white; 
        border: none; 
    }
    
    .bed-icon { font-size: 2rem; margin-bottom: 5px; opacity: 0.9; }
    .bed-number { font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .popover { 
        border: none; 
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.3); 
        min-width: 280px; 
    }
    .popover-header { 
        background-color: #4e73df; 
        color: white; 
        font-weight: bold; 
        padding: 12px;
        text-align: center; 
    }
    .popover-body { padding: 15px; font-size: 0.9rem; line-height: 1.6; }
    .pop-btn-group { 
        margin-top: 15px; 
        padding-top: 12px; 
        border-top: 1px solid #eee; 
    }
</style>

<div class="main-content">
    <div class="container-fluid pt-4">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Clinical Ward Management</h1>
            <div class="d-flex align-items-center">
                <a href="discharge_patient.php" class="btn btn-outline-danger shadow-sm mr-2">
                    <i class="fas fa-sign-out-alt mr-2"></i>Discharge Patient
                </a>
                <a href="admit_patient.php" class="btn btn-primary shadow-sm">
                    <i class="fas fa-plus mr-2"></i>New Admission
                </a>
            </div>
        </div>

        <?php foreach ($hospital_wards as $ward_name => $total_beds): ?>
            <?php 
                $current_ward_occupied = $occupied_beds[$ward_name] ?? [];
                $occupied_count = count($current_ward_occupied);
                $available_count = $total_beds - $occupied_count;
            ?>
            <div class="card shadow mb-5 border-0">
                <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold text-dark">
                        <i class="fas fa-hospital-symbol text-primary mr-2"></i><?= htmlspecialchars($ward_name) ?>
                    </h5>
                    <div class="d-none d-sm-block">
                        <span class="badge badge-pill badge-success px-3 py-2 mr-2"><?= $available_count ?> Free</span>
                        <span class="badge badge-pill badge-danger px-3 py-2"><?= $occupied_count ?> Occupied</span>
                    </div>
                </div>
                <div class="card-body bg-light py-4">
                    <div class="bed-grid">
                        <?php for($i = 1; $i <= $total_beds; $i++): ?>
                            <?php 
                                $is_occupied = isset($current_ward_occupied[$i]);
                                $bed_class = $is_occupied ? 'bed-occupied' : 'bed-available';
                                
                                if ($is_occupied) {
                                    $p = $current_ward_occupied[$i];
                                    $link = "../patients/patient_dashboard.php?id=" . $p['patient_id'];
                                    
                                    $pop_title = "Bed " . $i . ": " . htmlspecialchars($p['name']);
                                    $pop_content = "<div><b>Doctor:</b> ".htmlspecialchars($p['doctor'])."</div>";
                                    $pop_content .= "<div><b>Since:</b> ".date('d M Y, H:i', strtotime($p['date']))."</div>";
                                    $pop_content .= "<div><b>Reason:</b> ".htmlspecialchars($p['reason'])."</div>";
                                    
                                    $pop_content .= "<div class='pop-btn-group d-flex justify-content-between'>";
                                    // Linked specific admission ID to discharge page
                                    $pop_content .= "<a href='discharge_patient.php?id=".$p['id']."' class='btn btn-sm btn-danger'><i class='fas fa-sign-out-alt mr-1'></i> Discharge</a>";
                                    $pop_content .= "<span class='small text-muted align-self-center'>Click bed for Dashboard</span>";
                                    $pop_content .= "</div>";
                                    
                                    $attr = 'data-toggle="popover" data-trigger="hover" data-html="true" title="'.$pop_title.'" data-content="'.$pop_content.'"';
                                } else {
                                    $link = "admit_patient.php?ward=".urlencode($ward_name)."&bed=".$i;
                                    $attr = 'data-toggle="tooltip" title="Assign Patient to Bed '.$i.'"';
                                }
                            ?>
                            
                            <a href="<?= $link ?>" class="bed-box <?= $bed_class ?>" <?= $attr ?>>
                                <i class="fas fa-bed bed-icon"></i>
                                <span class="bed-number">BED <?= $i ?></span>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();
        
        $('[data-toggle="popover"]').popover({
            placement: 'top',
            boundary: 'viewport',
            sanitize: false,
            delay: { "show": 100, "hide": 400 }
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>