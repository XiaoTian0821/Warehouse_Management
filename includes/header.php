<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
    <div class="wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>📦 WMS</h1>
                <p><?= e(APP_NAME) ?></p>
            </div>
            <nav class="sidebar-nav">
                <?php if (is_logged_in()): ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="<?= url('/admin/dashboard.php') ?>" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                            <span class="nav-icon">📊</span> Dashboard
                        </a>
                        <a href="<?= url('/admin/items.php') ?>" class="nav-item">
                            <span class="nav-icon">📦</span> Items
                        </a>
                        <a href="<?= url('/admin/movements.php') ?>" class="nav-item">
                            <span class="nav-icon">🔄</span> Stock Movements
                        </a>
                        <a href="<?= url('/admin/reports.php') ?>" class="nav-item">
                            <span class="nav-icon">📈</span> Reports
                        </a>
                        <a href="<?= url('/admin/users.php') ?>" class="nav-item">
                            <span class="nav-icon">👥</span> Users
                        </a>
                    <?php else: ?>
                        <a href="<?= url('/user/dashboard.php') ?>" class="nav-item">
                            <span class="nav-icon">📊</span> Dashboard
                        </a>
                        <a href="<?= url('/user/items.php') ?>" class="nav-item">
                            <span class="nav-icon">📦</span> Items
                        </a>
                    <?php endif; ?>
                    <div class="nav-divider"></div>
                    <a href="<?= url('/logout.php') ?>" class="nav-item logout">
                        <span class="nav-icon">🚪</span> Logout
                    </a>
                <?php else: ?>
                    <a href="<?= url('/login.php') ?>" class="nav-item">
                        <span class="nav-icon">🔑</span> Login
                    </a>
                <?php endif; ?>
            </nav>
            <?php if (is_logged_in()): ?>
                <div class="sidebar-user">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['full_name'][0] ?? 'U', 0, 1)) ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= e($_SESSION['full_name']) ?></div>
                        <div class="user-role"><?= e(ucfirst($_SESSION['role'])) ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>
                <h2><?= e($page_title ?? '') ?></h2>
                <?php if (is_logged_in()): ?>
                    <span class="welcome">Welcome, <strong><?= e($_SESSION['full_name']) ?></strong></span>
                <?php endif; ?>
            </header>
            <div class="content">
                <?php
                $flash = get_flash();
                if ($flash):
                ?>
                    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endif; ?>
