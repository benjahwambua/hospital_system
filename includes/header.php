<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($SITE_NAME); ?> | HMS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $ASSETS_PATH ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --strong-blue: #004a99; 
            --emaqure-red: #ff3131; /* Vibrant brand red */
            --sidebar-width: 250px;
            --header-height: 75px;
        }

        body { font-family: 'Inter', sans-serif; margin: 0; background-color: #f0f4f8; }

        .top-header {
            height: var(--header-height);
            background: var(--strong-blue);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .header-left-group { display: flex; align-items: center; gap: 18px; }
        
        .logo-container {
            background: white;
            padding: 5px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .site-logo { height: 45px; width: auto; object-fit: contain; }

        .header-title-wrapper { line-height: 1.2; }
        
        /* THE BRAND COLOR: EMAQURE RED */
        .header-title { 
            color: var(--emaqure-red) !important; 
            font-weight: 800; 
            font-size: 1.4rem; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        /* THE SLOGAN: WHITE */
        .subtitle { 
            color: #ffffff !important; 
            font-size: 0.85rem; 
            font-weight: 500; 
            font-style: italic;
            letter-spacing: 0.3px;
            opacity: 0.9;
        }

        .header-right { display: flex; align-items: center; gap: 20px; }

        .user-pill {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 14px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        .user-avatar-circle {
            width: 26px;
            height: 26px;
            background: var(--emaqure-red);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .user-name { font-weight: 600; font-size: 0.85rem; margin-left: 10px; }

        .btn-logout {
            background: #ffffff;
            color: var(--emaqure-red);
            padding: 8px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.8rem;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid transparent;
        }

        .btn-logout:hover { 
            background: var(--emaqure-red);
            color: white;
            transform: translateY(-1px);
        }

        .layout { margin-top: var(--header-height); display: flex; }
        
        @media (max-width: 768px) {
            .header-title-wrapper { display: none; }
        }
    </style>
</head>
<body>

<header class="top-header">
    <div class="header-left-group">
        <div class="logo-container">
            <img src="<?php echo htmlspecialchars($SITE_LOGO); ?>" alt="Logo" class="site-logo">
        </div>
        <div class="header-title-wrapper">
            <h1 class="header-title">Emaqure Medical Centre</h1>
            <div class="subtitle">Compassion, next to home</div>
        </div>
    </div>

    <div class="header-right">
        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="user-pill">
                <div class="user-avatar-circle">
                    <i class="fas fa-user"></i>
                </div>
                <span class="user-name">Hi, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
            </div>
            <a class="btn-logout" href="/hospital_system/auth/logout.php">
                <i class="fas fa-power-off"></i> LOGOUT
            </a>
        <?php endif; ?>
    </div>
</header>

<div class="layout">