<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'doctor', 'nurse', 'receptionist']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$message = "";

// Capture Ward and Bed from URL
$pre_ward = $_GET['ward'] ?? '';
$pre_bed  = $_GET['bed'] ?? '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id      = intval($_POST['patient_id']);
    $ward_name       = mysqli_real_escape_string($conn, $_POST['ward_name']);
    $bed_number      = intval($_POST['bed_number']);
    $admit_date      = mysqli_real_escape_string($conn, $_POST['admit_date']);
    $reason          = mysqli_real_escape_string($conn, $_POST['reason']);
    $attending_doc   = mysqli_real_escape_string($conn, $_POST['attending_doctor']);
    
    $current_user_id = $_SESSION['user_id'] ?? 0;
    $status          = 'Admitted';

    $check_stmt = $conn->prepare("SELECT id FROM admissions WHERE ward_name = ? AND bed_number = ? AND status = 'Admitted'");
    $check_stmt->bind_param("si", $ward_name, $bed_number);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $message = "<div class='alert alert-danger border-0 shadow-sm mb-4'>
                        <div class='d-flex align-items-center'>
                            <i class='fas fa-exclamation-circle fa-2x mr-3'></i>
                            <div><strong>Bed Conflict:</strong> Bed $bed_number in $ward_name is currently occupied.</div>
                        </div>
                    </div>";
    } else {
        $sql = "INSERT INTO admissions (patient_id, ward_name, bed_number, admit_date, reason, admitted_by, attending_doctor, created_by, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isisisiss", 
            $patient_id, $ward_name, $bed_number, $admit_date, $reason, 
            $current_user_id, $attending_doc, $current_user_id, $status
        );
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success border-0 shadow-sm mb-4'>
                            <div class='d-flex align-items-center'>
                                <i class='fas fa-check-double fa-2x mr-3'></i>
                                <div><strong>Success!</strong> Patient has been successfully admitted. <a href='ward_management.php' class='alert-link ml-2'>Return to Ward Map</a></div>
                            </div>
                        </div>";
        }
    }
}

$patients_query = $conn->query("SELECT id, full_name, phone FROM patients ORDER BY full_name ASC");
?>

<style>
    .main-content { background-color: #f0f2f5; min-height: 100vh; }
    
    /* Expanded Card Styling */
    .card-admission { 
        border-radius: 12px; 
        border: none; 
        background: #ffffff;
    }

    .section-title { 
        font-size: 0.85rem; 
        font-weight: 800; 
        color: #4e73df; 
        text-transform: uppercase; 
        letter-spacing: 1.5px; 
        display: flex; 
        align-items: center; 
        margin: 30px 0 20px 0;
    }
    .section-title:first-child { margin-top: 0; }
    .section-title::after { content: ""; flex: 1; height: 1px; background: #eaecf4; margin-left: 20px; }
    
    .form-control { 
        border-radius: 6px; 
        border: 1px solid #d1d3e2; 
        padding: 0.75rem 1rem; 
        height: auto;
    }
    .form-control:focus { 
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.1); 
        border-color: #4e73df; 
    }
    
    .input-group-text { 
        background: #f8f9fc; 
        color: #858796; 
        border-right: none; 
        padding-left: 20px;
        padding-right: 20px;
    }
    .form-with-icon .form-control { border-left: none; }
    
    .btn-register { 
        border-radius: 8px; 
        padding: 15px 40px; 
        font-weight: 700; 
        font-size: 1rem;
        background: #4e73df;
        border: none;
        transition: all 0.2s;
    }
    .btn-register:hover { 
        background: #2e59d9;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(78, 115, 223, 0.2);
    }

    /* Select2 Overrides for wide screens */
    .select2-container--bootstrap4 .select2-selection--single {
        height: calc(1.5 em + 1.5 rem + 2px) !important;
    }
</style>

<div class="main-content">
    <div class="container-fluid py-4">
        
        <div class="row mb-4">
            <div class="col-12 d-sm-flex align-items-center justify-content-between">
                <div>
                    <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Clinical Admission Portal</h1>
                    <p class="text-muted mb-0">Full-width interface for inpatient registration and bed allocation.</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    <a href="ward_management.php" class="btn btn-white shadow-sm border px-4">
                        <i class="fas fa-arrow-left mr-2 text-primary"></i> Back to Wards
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?= $message ?>
                
                <div class="card card-admission shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <form method="POST" autocomplete="off" class="form-with-icon">
                            
                            <div class="section-title">1. Patient Identification</div>
                            <div class="row">
                                <div class="col-lg-8 mb-4">
                                    <label class="small font-weight-bold text-dark">Search Patient Database</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                                        <select name="patient_id" class="form-control select2" required>
                                            <option value="" disabled selected>Start typing patient name, ID, or phone number...</option>
                                            <?php while($p = $patients_query->fetch_assoc()): ?>
                                                <option value="<?= $p['id'] ?>">
                                                    <?= htmlspecialchars($p['full_name']) ?> — (ID: <?= $p['id'] ?> | Tel: <?= $p['phone'] ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 mb-4">
                                    <label class="small font-weight-bold text-dark">Attending Doctor</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user-md"></i></span></div>
                                        <input type="text" name="attending_doctor" class="form-control" placeholder="Physician Name" required>
                                    </div>
                                </div>
                            </div>

                            <div class="section-title">2. Ward & Bed Assignment</div>
                            <div class="row">
                                <div class="col-md-5 mb-4">
                                    <label class="small font-weight-bold text-dark">Department / Ward</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-hospital-alt"></i></span></div>
                                        <select name="ward_name" class="form-control" required>
                                            <option value="General Ward (Male)" <?= ($pre_ward == 'General Ward (Male)') ? 'selected' : '' ?>>General Ward (Male)</option>
                                            <option value="General Ward (Female)" <?= ($pre_ward == 'General Ward (Female)') ? 'selected' : '' ?>>General Ward (Female)</option>
                                            <option value="Maternity Ward" <?= ($pre_ward == 'Maternity Ward') ? 'selected' : '' ?>>Maternity Ward</option>
                                            <option value="Pediatric Ward" <?= ($pre_ward == 'Pediatric Ward') ? 'selected' : '' ?>>Pediatric Ward</option>
                                            <option value="ICU" <?= ($pre_ward == 'ICU') ? 'selected' : '' ?>>Intensive Care Unit (ICU)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label class="small font-weight-bold text-dark">Bed Number</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-tag"></i></span></div>
                                        <input type="number" name="bed_number" class="form-control" value="<?= htmlspecialchars($pre_bed) ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label class="small font-weight-bold text-dark">Admission Date & Time</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-check"></i></span></div>
                                        <input type="datetime-local" name="admit_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="section-title">3. Clinical Notes</div>
                            <div class="form-group mb-5">
                                <label class="small font-weight-bold text-dark">Reason for Admission / Initial Diagnosis</label>
                                <textarea name="reason" class="form-control" rows="5" required placeholder="Enter detailed clinical reasons for this admission..."></textarea>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div>
                                    <p class="mb-0 text-muted small">Logged-in User:</p>
                                    <span class="badge badge-light border text-dark font-weight-normal py-2 px-3">
                                        <i class="fas fa-id-badge mr-2 text-primary"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'System') ?>
                                    </span>
                                </div>
                                <button type="submit" class="btn btn-primary btn-register shadow">
                                    Finalize Patient Admission <i class="fas fa-chevron-right ml-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>