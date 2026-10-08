<?php
declare(strict_types=1);

/**
 * Initialize the application: session, database, and helpers.
 * Include this file at the top of every PHP page.
 */

session_save_path(__DIR__ . '/../tmp/sessions');
@mkdir(__DIR__ . '/../tmp/sessions', 0700, true);

// session_name(SESSION_NAME);
session_start();

// Regenerate session ID periodically to prevent fixation
if (empty($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
