<?php
declare(strict_types=1);

/**
 * Logout Page
 */

require_once __DIR__ . '/includes/init.php';

// Destroy session
session_unset();
session_destroy();

set_flash('You have been logged out successfully.', 'success');
redirect('/login.php');
