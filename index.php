<?php
declare(strict_types=1);

/**
 * Root page - redirects to login
 */

require_once __DIR__ . '/includes/init.php';

// If logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    redirect($_SESSION['role'] === 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php');
}

// Otherwise redirect to login
redirect('/login.php');
