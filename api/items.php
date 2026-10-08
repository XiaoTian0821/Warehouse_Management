<?php
declare(strict_types=1);

/**
 * API - Item Search (authenticated)
 *
 * GET /api/items.php?search=xxx&category=xxx
 *
 * Returns JSON array of items matching the search criteria.
 * Requires valid session authentication.
 */

header('Content-Type: application/json; charset=utf-8');

// Require login
session_name('warehouse_session');
session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
    exit;
}

// Only allow authenticated users
$role = $_SESSION['role'];
if (!in_array($role, ['admin', 'user'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden.']);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

// Get search parameters
$search  = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$limit   = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
$page    = max(1, (int) ($_GET['page'] ?? 1));
$offset  = ($page - 1) * $limit;

// Build query
$sql = 'SELECT id, item_code, item_name, category, location, quantity, unit, reorder_level
        FROM items WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (item_code LIKE ? OR item_name LIKE ? OR location LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($category !== '') {
    $sql .= ' AND category = ?';
    $params[] = $category;
}

$sql .= ' ORDER BY item_code ASC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => $items,
        'count'   => count($items),
        'page'    => $page,
        'limit'   => $limit,
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $ex) {
    error_log('API items error: ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error.']);
}
