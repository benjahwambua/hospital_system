<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patient_id = intval($_GET['patient_id'] ?? 0);
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bp = $conn->real_escape_string($_POST['bp']);
    $temp = floatval($_POST['temperature']);
    $pulse = intval($_POST['pulse']);
    $resp = intval($_POST['respiration']);
    $weight = floatval($_POST['weight']);
    $encounter = $conn->real_escape_string($_POST['encounter_id']);

    $stmt = $conn->prepare("INSERT INTO vitals (patient_id,bp,temperature,pulse,respiration,weight,encounter_id) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("isiiids",$patient_id,$bp,$temp,$pulse,$resp,$weight,$encounter);
    if($stmt->execute()){
        header("Location: /hospital_system/patients/dashboard.php?id=$patient_id");
        exit;
    } else {
        $msg = "Failed to add vitals.";
    }
    $stmt->close();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <a href="/hospital_system/patients/dashboard.php?id=<?= $patient_id ?>" class="btn btn-outline-primary mb-3">Back to Dashboard</a>
    <h4>Add Vitals</h4>
    <?php if($msg) echo "<div class='alert alert-danger'>$msg</div>"; ?>
    <form method="post">
        <div class="mb-2"><input type="text" name="bp" class="form-control" placeholder="BP" required></div>
        <div class="mb-2"><input type="number" step="0.1" name="temperature" class="form-control" placeholder="Temperature" required></div>
        <div class="mb-2"><input type="number" name="pulse" class="form-control" placeholder="Pulse" required></div>
        <div class="mb-2"><input type="number" name="respiration" class="form-control" placeholder="Respiration" required></div>
        <div class="mb-2"><input type="number" step="0.1" name="weight" class="form-control" placeholder="Weight" required></div>
        <div class="mb-2"><input type="text" name="encounter_id" class="form-control" placeholder="Encounter ID" required></div>
        <button class="btn btn-success">Save</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
