<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$message = '';
$alert_type = 'alert-info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = trim($_POST['service_name']);
    $category = $_POST['category'];
    $price = floatval($_POST['price']);

    $allowed_categories = ['procedures','treatment','lab','radiology'];

    if ($service_name && in_array($category, $allowed_categories) && $price >= 0) {
        $stmt_check = $conn->prepare("SELECT id FROM services_master WHERE service_name=? AND category=?");
        $stmt_check->bind_param("ss", $service_name, $category);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $message = "Conflict: '$service_name' already exists in '$category'.";
            $alert_type = 'alert-danger';
        } else {
            $stmt = $conn->prepare("INSERT INTO services_master (service_name, category, price, active, created_at) VALUES (?,?,?,1,NOW())");
            $stmt->bind_param("ssd", $service_name, $category, $price);

            if ($stmt->execute()) {
                $message = "Success: New service authorized and added to the master list.";
                $alert_type = 'alert-success';
            } else {
                $message = "System Error: Could not save service.";
                $alert_type = 'alert-danger';
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
        $message = "Validation Failed: All fields are required.";
        $alert_type = 'alert-warning';
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .service-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
    .input-group-text {
        background: #f8f9fa;
        border-right: none;
        color: #004a99;
    }
    .form-control, .form-select {
        border-left: none;
        background: #f8f9fa;
    }
    .form-control:focus {
        background: #fff;
        box-shadow: none;
        border-color: #ced4da;
    }
    .category-badge {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin-bottom: 15px;
    }
    .btn-submit {
        background: linear-gradient(135deg, #004a99, #007bff);
        border: none;
        padding: 12px;
        font-weight: 600;
        transition: 0.3s;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,74,153,0.3);
    }
</style>

<div class="main-content" style="padding: 30px; background: #f0f2f5; min-height: 100vh;">
    <div class="container-fluid">
        
        <div class="mb-4">
            <h2 class="h3 font-weight-bold text-gray-800">Service Management</h2>
            <p class="text-muted">Register new medical procedures and billing rates for Emaqure Medical Centre.</p>
        </div>

        <div class="row">
            <div class="col-lg-5">
                <div class="card service-card">
                    <div class="card-body p-4">
                        <div class="category-badge bg-primary text-white">
                            <i class="fas fa-plus"></i>
                        </div>
                        <h4 class="font-weight-bold mb-4">Add New Service</h4>

                        <?php if($message): ?>
                            <div class="alert <?= $alert_type ?> border-0 small shadow-sm animate__animated animate__fadeIn">
                                <i class="fas fa-info-circle mr-2"></i> <?= $message ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="off">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold text-muted">SERVICE DESCRIPTION</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-notes-medical"></i></span>
                                    <input type="text" name="service_name" class="form-control" placeholder="e.g. Full Blood Count" required>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="small font-weight-bold text-muted">SERVICE CATEGORY</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    <select name="category" class="form-control" required>
                                        <option value="" selected disabled>Choose Category...</option>
                                        <option value="procedures">Procedures</option>
                                        <option value="treatment">Treatment</option>
                                        <option value="lab">Laboratory</option>
                                        <option value="radiology">Radiology / Imaging</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="small font-weight-bold text-muted">BILLING PRICE (KSH)</label>
                                <div class="input-group">
                                    <span class="input-group-text font-weight-bold">KES</span>
                                    <input type="number" name="price" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-submit shadow-sm">
                                <i class="fas fa-check-circle mr-2"></i> AUTHORIZE SERVICE
                            </button>
                            
                            <a href="view_services.php" class="btn btn-link btn-block btn-sm text-muted mt-2">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Service List
                            </a>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm" style="border-radius:15px;">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-microscope fa-2x text-info mb-3"></i>
                                <h6 class="font-weight-bold">Lab Services</h6>
                                <p class="small text-muted mb-0">Ensure lab services match the test names used in the Lab Requests module.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm" style="border-radius:15px;">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-file-invoice-dollar fa-2x text-success mb-3"></i>
                                <h6 class="font-weight-bold">Pricing Policy</h6>
                                <p class="small text-muted mb-0">Prices set here will automatically populate the Billing/Invoicing department.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm mt-2" style="border-radius:15px; background: linear-gradient(to right, #ffffff, #fdfdfd);">
                    <div class="card-body p-4">
                        <h5 class="font-weight-bold"><i class="fas fa-lightbulb text-warning mr-2"></i> Administrative Note</h5>
                        <p class="text-muted small">When adding services, use standard medical terminology. For Radiology, specify if the price includes the film or just the consultation. All entries are logged with a timestamp for audit purposes.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>