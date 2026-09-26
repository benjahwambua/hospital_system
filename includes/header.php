<?php
// includes/header.php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/config.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($SITE_NAME); ?> | HMS</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $ASSETS_PATH ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.mobile-nav-toggle{display:none;border:0;background:rgba(255,255,255,.12);color:#fff;width:38px;height:38px;border-radius:8px;font-size:16px;cursor:pointer}
@media(max-width:900px){.mobile-nav-toggle{display:inline-flex;align-items:center;justify-content:center}}
</style>
</head>
<body>
<header class="top-header">
  <div class="header-left-group"><button type="button" class="mobile-nav-toggle" aria-label="Open navigation"><i class="fas fa-bars"></i></button>
    <div class="logo-container"><img src="<?php echo htmlspecialchars($SITE_LOGO); ?>" alt="Logo" class="site-logo"></div>
    <div class="header-title-wrapper">
      <h1 class="header-title">Emaqure Medical Centre</h1>
      <span class="subtitle">Compassion, next to home</span>
    </div>
  </div>
  <div class="header-right">
    <?php if (!empty($_SESSION['user_id'])): ?>
      <div class="user-pill">
        <div class="user-avatar-circle"><i class="fas fa-user"></i></div>
        <span class="user-name">Hi, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
      </div>
      <a class="btn-logout" href="/hospital_system/auth/logout.php"><i class="fas fa-power-off"></i> Logout</a>
    <?php endif; ?>
  </div>
</header>
<div class="layout">