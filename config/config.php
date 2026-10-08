<?php
declare(strict_types=1);

/**
 * Application Configuration
 */

// Application identity
define('APP_NAME', 'Warehouse Management System');
define('APP_URL', 'http://localhost/Warehouse_Management');
define('BASE_PATH', '/Warehouse_Management');

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'warehouse_management');
define('DB_USER', 'root');
define('DB_PASS', '123456');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('SESSION_NAME', 'warehouse_session');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');

// Pagination
define('ITEMS_PER_PAGE', 20);
define('MOVEMENTS_PER_PAGE', 20);
