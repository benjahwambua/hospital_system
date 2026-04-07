<?php
require_once __DIR__ . '/../config/config.php'; 
require_once __DIR__ . '/../includes/session.php'; 
require_login(); 
require_once __DIR__ . '/../includes/auth.php'; 
require_role(['admin','accountant']);

include __DIR__ . '/../includes/header.php'; 
include __DIR__ . '/../includes/sidebar.php';
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['setting'] as $key => $value) {
        $key = mysqli_real_escape_string($conn, $key);
        $value = mysqli_real_escape_string($conn, $value);
        $conn->query("UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'");
    }
    $message = "<div class='alert alert-success'>System settings updated successfully.</div>";
}

// Fetch settings
$settings = $conn->query("SELECT * FROM settings")->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="container-fluid pt-4">
        <div class="card shadow-sm border-0 col-lg-10 mx-auto">
            <div class="card-header bg-white py-3">
                <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-tools mr-2"></i>Global System Configuration</h5>
            </div>
            <div class="card-body p-4">
                <?= $message ?>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small font-weight-bold">Hospital Name</label>
                            <input type="text" name="setting[hospital_name]" class="form-control" value="St. Lukes Hospital">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small font-weight-bold">Tax / PIN Number</label>
                            <input type="text" name="setting[tax_id]" class="form-control" value="P051234567Z">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small font-weight-bold">Contact Phone</label>
                            <input type="text" name="setting[phone]" class="form-control" value="+254 700 000 000">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small font-weight-bold">Email Address</label>
                            <input type="email" name="setting[email]" class="form-control" value="info@hospital.com">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="small font-weight-bold">Physical Address</label>
                            <textarea name="setting[address]" class="form-control" rows="2">123 Health Ave, Nairobi, Kenya</textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small font-weight-bold">Currency Symbol</label>
                            <input type="text" name="setting[currency]" class="form-control" value="KSH">
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary px-5 font-weight-bold">Save Configuration</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>