<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// Fetch patients with vitals recorded today who haven't been "completed"
$sql = "SELECT v.*, p.full_name, p.gender, p.age 
        FROM vitals v 
        JOIN patients p ON v.patient_id = p.id 
        WHERE DATE(v.created_at) = CURDATE() AND v.status = 'pending'
        ORDER BY v.created_at ASC";
$res = $conn->query($sql);
?>

<div class="main-content">
    <div class="container-fluid pt-4">
        <h4 class="font-weight-bold text-gray-800 mb-4">Doctor's Consultation Queue</h4>
        <div class="row">
            <?php if($res && $res->num_rows > 0): while($row = $res->fetch_assoc()): ?>
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card shadow-sm border-left-primary">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= $row['full_name'] ?> (<?= $row['gender'] ?>, <?= $row['age'] ?> yrs)</div>
                                <div class="small text-muted mb-2">BP: <?= $row['bp'] ?> | Temp: <?= $row['temp'] ?>°C</div>
                                <div class="mb-0 text-gray-800 small"><strong>Complaints:</strong> <?= substr($row['complaints'], 0, 60) ?>...</div>
                            </div>
                            <div class="col-auto">
                                <a href="examine_patient.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary px-3">Examine</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
                <div class="col-12 text-center py-5">
                    <img src="../assets/img/empty_queue.svg" style="width:150px; opacity:0.5">
                    <p class="mt-3 text-muted">No patients currently in queue.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>