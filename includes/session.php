<?php
// includes/session.php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: /hospital_system/auth/login.php'); exit;
    }
}
