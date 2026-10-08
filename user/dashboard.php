<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('user');
$db = getDB();
$stmt = $db->query('SELECT COUNT(*) AS total FROM items');
$total_items = (int) $stmt->fetch()['total'];
$stmt = $db->query('SELECT SUM(quantity) AS total FROM items');
$row = $stmt->fetch();
$total_quantity = (int) ($row['total'] ?? 0);
$stmt = $db->query("SELECT COUNT(*) AS total FROM items WHERE quantity <= reorder_level");
$low_stock = (int) $stmt->fetch()['total'];
$sql = "SELECT sm.*, i.item_code, i.item_name FROM stock_movements sm JOIN items i ON sm.item_id = i.id ORDER BY sm.movement_date DESC LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute();
$recentMovements = $stmt->fetchAll();
$page_title = 'User Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">i</div>
        <div class="stat-info"><h3><?= $total_items ?></h3><p>Total Items</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan">s</div>
        <div class="stat-info"><h3><?= number_format($total_quantity) ?></h3><p>Total Stock</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow">!</div>
        <div class="stat-info"><h3><?= $low_stock ?></h3><p>Low Stock Items</p></div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Stock Movements</h3>
        <a href="<?= url('/user/items.php') ?>" class="btn btn-sm btn-secondary">View All Items</a>
    </div>
    <div class="table-container">
        <table>
            <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Qty</th><th>Note</th></tr></thead>
            <tbody>
                <?php if (empty($recentMovements)): ?>
                    <tr><td colspan="5" style="text-align:center; color:var(--gray-500);">No movements recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentMovements as $m): ?>
                        <tr>
                            <td><?= e(format_date_short($m['movement_date'])) ?></td>
                            <td><strong><?= e($m['item_code']) ?></strong><br><small style="color:var(--gray-500)"><?= e($m['item_name']) ?></small></td>
                            <td><span class="badge <?= $m['movement_type'] === 'IN' ? 'badge-success' : 'badge-danger' ?>"><?= e($m['movement_type']) ?></span></td>
                            <td><strong><?= number_format($m['quantity']) ?></strong></td>
                            <td><?= e($m['reference_note'] ?? '---') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
