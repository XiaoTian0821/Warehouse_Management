<?php
declare(strict_types=1);

/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$db = getDB();

// Summary statistics
$stats = [];

$stmt = $db->query('SELECT COUNT(*) AS total FROM items');
$stats['total_items'] = (int) $stmt->fetch()['total'];

$stmt = $db->query('SELECT SUM(quantity) AS total FROM items');
$row = $stmt->fetch();
$stats['total_quantity'] = (int) ($row['total'] ?? 0);

$stmt = $db->query("SELECT COUNT(*) AS total FROM items WHERE quantity <= reorder_level");
$stats['low_stock'] = (int) $stmt->fetch()['total'];

// Today's movements
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(quantity),0) AS total FROM stock_movements WHERE movement_type = 'IN' AND DATE(movement_date) = ?");
$stmt->execute([$today]);
$todayIn = $stmt->fetch();
$stats['today_in'] = (int) $todayIn['total'];

$stmt = $db->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(quantity),0) AS total FROM stock_movements WHERE movement_type = 'OUT' AND DATE(movement_date) = ?");
$stmt->execute([$today]);
$todayOut = $stmt->fetch();
$stats['today_out'] = (int) $todayOut['total'];

// Low stock items
$stmt = $db->prepare("SELECT id, item_code, item_name, category, quantity, reorder_level, unit FROM items WHERE quantity <= reorder_level ORDER BY quantity ASC LIMIT 10");
$stmt->execute();
$lowStockItems = $stmt->fetchAll();

// Recent movements
$stmt = $db->prepare("
    SELECT sm.id, sm.movement_type, sm.quantity, sm.movement_date, sm.reference_note,
           i.item_code, i.item_name, u.full_name AS user_name
    FROM stock_movements sm
    JOIN items i ON sm.item_id = i.id
    JOIN users u ON sm.created_by = u.id
    ORDER BY sm.movement_date DESC
    LIMIT 10
");
$stmt->execute();
$recentMovements = $stmt->fetchAll();

$page_title = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">📦</div>
        <div class="stat-info">
            <h3><?= $stats['total_items'] ?></h3>
            <p>Total Items</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan">📊</div>
        <div class="stat-info">
            <h3><?= number_format($stats['total_quantity']) ?></h3>
            <p>Total Stock</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow">⚠️</div>
        <div class="stat-info">
            <h3><?= $stats['low_stock'] ?></h3>
            <p>Low Stock Items</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">📥</div>
        <div class="stat-info">
            <h3><?= number_format($stats['today_in']) ?></h3>
            <p>Today's Stock IN</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">📤</div>
        <div class="stat-info">
            <h3><?= number_format($stats['today_out']) ?></h3>
            <p>Today's Stock OUT</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">⚠️ Low Stock Items</h3>
        <a href="<?= url('/admin/items.php') ?>" class="btn btn-sm btn-secondary">View All Items</a>
    </div>
    <?php if (empty($lowStockItems)): ?>
        <p style="color:var(--gray-500); padding:12px 0;">All items are well stocked!</p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Current Qty</th>
                        <th>Reorder Level</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody class="low-stock-table">
                    <?php foreach ($lowStockItems as $item): ?>
                        <tr>
                            <td><strong><?= e($item['item_code']) ?></strong></td>
                            <td><?= e($item['item_name']) ?></td>
                            <td><?= e($item['category']) ?></td>
                            <td>
                                <span class="badge badge-low-stock"><?= number_format($item['quantity']) ?></span>
                            </td>
                            <td><?= number_format($item['reorder_level']) ?></td>
                            <td><?= e($item['unit']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">🔄 Recent Stock Movements</h3>
        <a href="<?= url('/admin/movements.php') ?>" class="btn btn-sm btn-secondary">View All</a>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>Note</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentMovements)): ?>
                    <tr><td colspan="6" style="text-align:center; color:var(--gray-500);">No movements yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentMovements as $m): ?>
                        <tr>
                            <td><?= e(format_date_short($m['movement_date'])) ?></td>
                            <td>
                                <strong><?= e($m['item_code']) ?></strong><br>
                                <small style="color:var(--gray-500)"><?= e($m['item_name']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $m['movement_type'] === 'IN' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= e($m['movement_type']) ?>
                                </span>
                            </td>
                            <td><strong><?= number_format($m['quantity']) ?></strong></td>
                            <td><?= e($m['reference_note'] ?? '—') ?></td>
                            <td><?= e($m['user_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
