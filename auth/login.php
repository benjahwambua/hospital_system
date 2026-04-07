<?php
// auth/login.php
require_once __DIR__ . '/../config/config.php';
session_start();

if (!empty($_SESSION['user_id'])) { 
    header('Location: /hospital_system/dashboard.php'); 
    exit; 
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, full_name, role, is_super FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $u = $result->fetch_assoc();
        
        if (password_verify($password, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $u['id']; 
            $_SESSION['username'] = $u['username']; 
            $_SESSION['role'] = $u['role']; 
            $_SESSION['is_super'] = (int)$u['is_super'];
            $_SESSION['full_name'] = $u['full_name'];

            header('Location: /hospital_system/dashboard.php'); 
            exit;
        } else {
            $error = "Incorrect credentials. Please try again.";
        }
    } else {
        $error = "Incorrect credentials. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Emaqure Medical Centre</title>
    <link rel="stylesheet" href="/hospital_system/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --primary-color: #0056b3; 
            --accent-color: #00a8cc; 
            --dark-bg: #1a2a3a; /* New Deep Slate Outside Background */
            --soft-white: #fcfdfe; /* New Inner Form Background */
        }

        body { 
            background-color: var(--dark-bg); 
            background-image: radial-gradient(circle at 50% 50%, #2c3e50 0%, #1a2a3a 100%);
            font-family: 'Segoe UI', Roboto, sans-serif; 
            min-height: 100vh; 
            margin: 0; 
        }
        
        .login-container { 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }
        
        .login-box { 
            background: #fff; 
            display: flex; 
            width: 1050px; 
            max-width: 100%; 
            border-radius: 30px; 
            overflow: hidden; 
            /* Stronger shadow to lift it off the dark background */
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }

        /* Brand Side (Blue) */
        .login-brand { 
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); 
            width: 50%; 
            padding: 70px 40px; 
            color: white; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center;
            text-align: center;
        }

        /* Significantly larger Logo and Container */
        .logo-placeholder {
            background: white;
            padding: 35px; 
            border-radius: 50%;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            animation: fadeInDown 0.8s ease-out;
        }

        .logo-img { width: 140px; height: auto; }

        .brand-name { font-size: 3rem; letter-spacing: 3px; font-weight: 800; margin-bottom: 5px; }
        .brand-sub { font-size: 1.1rem; opacity: 0.9; font-weight: 300; }

        /* Form Side (Soft White) */
        .login-form-section { 
            width: 50%; 
            padding: 60px; 
            background-color: var(--soft-white); 
        }
        
        .form-control { 
            background: #ffffff; 
            border: 1px solid #e1e8ed; 
            padding: 16px 18px; 
            border-radius: 15px;
            transition: all 0.3s;
        }
        .form-control:focus { 
            background: #fff; 
            box-shadow: 0 0 0 4px rgba(0,86,179,0.15); 
            border-color: var(--primary-color); 
        }
        
        .btn-login { 
            background: var(--primary-color); 
            border: none; 
            border-radius: 15px; 
            padding: 18px; 
            font-weight: 700; 
            letter-spacing: 1px;
            transition: 0.3s;
            margin-top: 20px;
            box-shadow: 0 10px 20px rgba(0,86,179,0.2);
        }
        .btn-login:hover { 
            background: #004494; 
            transform: translateY(-2px); 
            box-shadow: 0 15px 25px rgba(0,86,179,0.3); 
        }

        .input-group-text { background: #ffffff; border: 1px solid #e1e8ed; border-radius: 15px 0 0 15px; }
        .pass-toggle { border-radius: 0 15px 15px 0 !important; cursor: pointer; }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 992px) {
            .login-box { flex-direction: column; width: 500px; }
            .login-brand, .login-form-section { width: 100%; }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-box">
        <div class="login-brand">
            <div class="logo-placeholder">
                <img src="/hospital_system/assets/img/logo.png" alt="Emaqure Logo" class="logo-img">
            </div>
            <h1 class="brand-name">EMAQURE</h1>
            <p class="brand-sub">Medical Centre Management System</p>
            <hr style="border-top: 2px solid rgba(255,255,255,0.2); width: 60%; margin: 25px 0;">
            <p style="font-size: 1.2rem; font-style: italic; font-weight: 300;">
                Compassion, <br> next to home.
            </p>
        </div>

        <div class="login-form-section">
            <div class="mb-5">
                <h2 class="font-weight-bold text-dark mb-2">System Login</h2>
                <p class="text-muted">Enter your credentials to access the portal.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 py-3 mb-4 shadow-sm" style="border-radius: 15px; border-left: 5px solid #dc3545 !important;">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <div class="form-group mb-4">
                    <label class="small font-weight-bold text-muted mb-2">STAFF USERNAME</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text border-right-0"><i class="fas fa-user-shield text-muted"></i></span>
                        </div>
                        <input name="username" class="form-control border-left-0" placeholder="e.g. b.wambua" required autofocus>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="small font-weight-bold text-muted mb-2">SECURE PASSWORD</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text border-right-0"><i class="fas fa-key text-muted"></i></span>
                        </div>
                        <input name="password" type="password" id="pass_input" class="form-control border-left-0 border-right-0" placeholder="••••••••" required>
                        <div class="input-group-append">
                            <span class="input-group-text pass-toggle bg-white" onclick="togglePass()">
                                <i class="fas fa-eye text-muted" id="eye_icon"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login btn-block">
                    <i class="fas fa-sign-in-alt mr-2"></i> ACCESS SYSTEM
                </button>
            </form>

            <div class="mt-5 text-center">
                <p class="text-muted" style="font-size: 0.85rem;">
                    &copy; 2026 Emaqure Medical Centre. <br> 
                    <span class="opacity-50">Secure ERP Environment</span>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
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
</script>

</body>
</html>