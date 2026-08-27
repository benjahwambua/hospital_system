<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

// Load patients for the triage dropdown
$patients = $conn->query("SELECT id, full_name, gender, age FROM patients ORDER BY full_name ASC");

// Detect whether the vitals table supports a status column
$vitalsHasStatus = false;
$vitalsColumns = $conn->query("SHOW COLUMNS FROM vitals");
if ($vitalsColumns) {
    while ($col = $vitalsColumns->fetch_assoc()) {
        if (($col['Field'] ?? '') === 'status') {
            $vitalsHasStatus = true;
            break;
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $bp         = $_POST['bp'] ?? '';
    $temp       = $_POST['temp'] ?? '';
    $weight     = $_POST['weight'] ?? '';
    $pulse      = $_POST['pulse'] ?? '';
    $complaints = $_POST['complaints'] ?? '';
    $recorded_by = $_SESSION['user_id'];

    $sql = "INSERT INTO vitals (patient_id, bp, temp, weight, pulse, complaints, recorded_by";
    if ($vitalsHasStatus) {
        $sql .= ", status";
    }
    $sql .= ", created_at) VALUES (?, ?, ?, ?, ?, ?, ?";
    if ($vitalsHasStatus) {
        $sql .= ", ?";
    }
    $sql .= ", NOW())";

    $stmt = $conn->prepare($sql);
    if ($vitalsHasStatus) {
        $status = 'pending';
        $stmt->bind_param("isssssis", $patient_id, $bp, $temp, $weight, $pulse, $complaints, $recorded_by, $status);
    } else {
        $stmt->bind_param("isssssi", $patient_id, $bp, $temp, $weight, $pulse, $complaints, $recorded_by);
    }
    
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Vitals recorded. Patient moved to Doctor's Queue.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . htmlspecialchars($conn->error) . "</div>";
    }
    $stmt->close();
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
                                    <?php if ($patients && $patients->num_rows > 0): ?>
                                        <?php while ($patient = $patients->fetch_assoc()): ?>
                                            <option value="<?= (int)$patient['id'] ?>"><?= htmlspecialchars($patient['full_name']) ?> <?= htmlspecialchars($patient['gender']) ?>, <?= (int)$patient['age'] ?> yrs</option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
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