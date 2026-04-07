<?php
require_once __DIR__ . '/../config/config.php'; 
require_once __DIR__ . '/../includes/session.php'; 
require_login(); 
require_once __DIR__ . '/../includes/auth.php'; 
require_super(); // Strictly enforced for Super Users only

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Improved Sanitization
    $fullname = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING));
    $username = trim(strtolower(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING))); // Usernames usually lowercase
    $role = $_POST['role'];
    $is_super = isset($_POST['is_super']) ? 1 : 0;
    $raw_password = $_POST['password'];

    // --- NEW: Password Strength Validation ---
    if (strlen($raw_password) < 8) {
        $error = "Security Error: Password must be at least 8 characters long.";
    } else {
        $password = password_hash($raw_password, PASSWORD_DEFAULT);

        // Check for existing username
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Conflict: Username '$username' is already assigned to another staff member.";
        } else {
            // Prepared statement for insertion
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, is_super, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssi", $username, $password, $fullname, $role, $is_super);
            
            if ($stmt->execute()) {
                $msg = "Success: Official account for <strong>$fullname</strong> has been authorized.";
            } else {
                $error = "System Failure: Could not write to database. Please contact IT.";
            }
        }
    }
}

include __DIR__ . '/../includes/header.php'; 
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .form-control:focus { border-color: #007bff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.15); }
    .input-group-text { cursor: pointer; background: #f8f9fc; }
    .password-strength { height: 5px; margin-top: 5px; transition: all 0.3s; border-radius: 5px; }
</style>

<div class="main-content" style="padding: 25px; background: #f8f9fc; min-height: 100vh;">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow border-0" style="border-radius: 15px;">
                    <div class="card-header bg-white border-0 pt-4 text-center">
                        <div class="icon-circle bg-primary text-white mb-3" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h4 class="font-weight-bold text-gray-800">Create Official Staff Account</h4>
                        <p class="text-muted small">Authorization Level: <span class="badge badge-danger">SUPER USER</span></p>
                    </div>
                    
                    <div class="card-body px-4 pb-4">
                        <?php if($msg): ?>
                            <div class="alert alert-success border-0 small"><i class="fas fa-check-circle mr-2"></i> <?= $msg ?></div>
                        <?php endif; ?>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger border-0 small"><i class="fas fa-exclamation-triangle mr-2"></i> <?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST" id="addUserForm" onsubmit="return confirmSuperUser()">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold text-uppercase">Staff Full Name</label>
                                <input type="text" name="full_name" class="form-control bg-light border-0" placeholder="e.g. John Doe" required value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>">
                            </div>

                            <div class="form-group mb-3">
                                <label class="small font-weight-bold text-uppercase">System Username</label>
                                <input type="text" name="username" class="form-control bg-light border-0" placeholder="e.g. j.doe" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                            </div>

                            <div class="form-group mb-3">
                                <div class="d-flex justify-content-between">
                                    <label class="small font-weight-bold text-uppercase">Set Official Password</label>
                                    <a href="javascript:void(0)" onclick="generatePassword()" class="small text-primary font-weight-bold">Generate Secure</a>
                                </div>
                                <div class="input-group">
                                    <input type="password" name="password" id="pass_input" class="form-control bg-light border-0" placeholder="Min 8 characters" required onkeyup="checkStrength(this.value)">
                                    <div class="input-group-append">
                                        <span class="input-group-text border-0" onclick="togglePass()">
                                            <i class="fas fa-eye" id="eye_icon"></i>
                                        </span>
                                    </div>
                                </div>
                                <div id="strength-bar" class="password-strength"></div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="small font-weight-bold text-uppercase">Assign Professional Role</label>
                                <select name="role" class="form-control bg-light border-0" required>
                                    <option value="" disabled selected>Select Staff Role...</option>
                                    <option value="reception">Receptionist</option>
                                    <option value="doctor">Doctor / Consultant</option>
                                    <option value="pharmacist">Pharmacist</option>
                                    <option value="lab_tech">Lab Technician</option>
                                    <option value="accountant">Accountant</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>

                            <div class="custom-control custom-checkbox mb-4 p-3 bg-light rounded shadow-sm border-left border-danger">
                                <input type="checkbox" class="custom-control-input" id="isSuper" name="is_super">
                                <label class="custom-control-label small font-weight-bold text-danger" for="isSuper">
                                    Grant Full Administrative Control (Super User)
                                </label>
                                <p class="text-muted x-small mb-0 mt-1" style="font-size: 11px;">Enables access to financial ledgers, system logs, and user management.</p>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block shadow-sm font-weight-bold py-2">
                                <i class="fas fa-save mr-2"></i> Authorize & Create Account
                            </button>
                            <a href="view_users.php" class="btn btn-link btn-block btn-sm text-muted mt-2">Cancel and Return</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// --- Toggle Password Visibility ---
function togglePass() {
    var x = document.getElementById("pass_input");
    var icon = document.getElementById("eye_icon");
    if (x.type === "password") {
        x.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        x.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}

// --- NEW: Generate Random Password ---
function generatePassword() {
    const charset = "abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%^&*";
    let retVal = "";
    for (let i = 0, n = charset.length; i < 12; ++i) {
        retVal += charset.charAt(Math.floor(Math.random() * n));
    }
    const passInput = document.getElementById("pass_input");
    passInput.type = "text";
    passInput.value = retVal;
    document.getElementById("eye_icon").classList.replace("fa-eye", "fa-eye-slash");
    checkStrength(retVal);
}

// --- NEW: Password Strength Indicator ---
function checkStrength(password) {
    let strength = 0;
    if (password.length > 7) strength += 1;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
    if (password.match(/\d/)) strength += 1;
    if (password.match(/[^a-zA-Z\d]/)) strength += 1;

    let bar = document.getElementById("strength-bar");
    let colors = ['#e74a3b', '#f6c23e', '#36b9cc', '#1cc88a'];
    let widths = ['25%', '50%', '75%', '100%'];
    
    if(password.length === 0) {
        bar.style.width = '0';
    } else {
        bar.style.width = widths[strength-1] || '10%';
        bar.style.backgroundColor = colors[strength-1] || colors[0];
    }
}

// --- NEW: Double Confirmation for Super User ---
function confirmSuperUser() {
    var isSuper = document.getElementById("isSuper").checked;
    if (isSuper) {
        return confirm("ATTENTION: You are about to grant this user FULL system control. They will be able to delete data and manage other users. Are you absolutely sure?");
    }
    return true;
}
</script>