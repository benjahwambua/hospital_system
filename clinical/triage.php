<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $bp         = $_POST['bp'];
    $temp       = $_POST['temp'];
    $weight     = $_POST['weight'];
    $pulse      = $_POST['pulse'];
    $complaints = mysqli_real_escape_string($conn, $_POST['complaints']);
    $recorded_by = $_SESSION['user_id'];

    $sql = "INSERT INTO vitals (patient_id, bp, temp, weight, pulse, complaints, recorded_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssi", $patient_id, $bp, $temp, $weight, $pulse, $complaints, $recorded_by);
    
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Vitals recorded. Patient moved to Doctor's Queue.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}
?>

<div class="main-content">
    <div class="container-fluid pt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-heartbeat mr-2"></i>Patient Triage & Vitals</h5>
                    </div>
                    <div class="card-body p-4">
                        <?= $message ?>
                        <form method="POST">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold">SELECT PATIENT</label>
                                <select name="patient_id" class="form-control select2" required>
                                    <option value="">-- Search Patient --</option>
                                    </select>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="small font-weight-bold">BP (mmHg)</label>
                                    <input type="text" name="bp" class="form-control" placeholder="120/80">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="small font-weight-bold">TEMP (°C)</label>
                                    <input type="text" name="temp" class="form-control" placeholder="36.5">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="small font-weight-bold">WEIGHT (KG)</label>
                                    <input type="text" name="weight" class="form-control" placeholder="70">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="small font-weight-bold">PULSE (bpm)</label>
                                    <input type="text" name="pulse" class="form-control" placeholder="72">
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label class="small font-weight-bold">CHIEF COMPLAINTS</label>
                                <textarea name="complaints" class="form-control" rows="3" placeholder="Describe symptoms..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2">Submit to Consultation</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>