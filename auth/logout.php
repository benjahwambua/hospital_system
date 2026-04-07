<?php
// auth/logout.php
session_start();

// 1. Unset all session variables
$_SESSION = [];

// 2. Delete the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Destroy the session on the server
session_destroy();

// 4. Redirect with a clear path
// Using a relative path or a constant from your config is safer than hardcoding
header('Location: /hospital_system/auth/login.php?logout=success');
exit;