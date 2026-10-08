<?php
declare(strict_types=1);

/**
 * Admin - Reports
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$db = getDB();

$filter_item = (int) ($_GET['item'] ?? 0);
$filter_category = trim($_GET['category'] ?? '');
$filter_type = trim($_GET['type'] ?? '');
$filter_start = trim($_GET['start_date'] ?? '');
$filter_end = trim($_GET['end_date'] ?? '');

$is_print = isset($_GET['print']);

$sql = 'SELECT sm.*, i.item_code, i.item_name, i.category, u.full_name AS user_name
        FROM stock_movements sm
        JOIN items i ON sm.item_id = i.id
        JOIN users u ON sm.created_by = u.id
        WHERE 1=1';
$params = [];

if ($filter_item > 0) {
    $sql .= ' AND sm.item_id = ?';
    $params[] = $filter_item;
}
if ($filter_category !== '') {
    $sql .= ' AND i.category = ?';
    $params[] = $filter_category;
}
if ($filter_type !== '') {
    $sql .= ' AND sm.movement_type = ?';
    $params[] = $filter_type;
}
if ($filter_start !== '') {
    $sql .= ' AND sm.movement_date >= ?';
    $params[] = $filter_start . ' 00:00:00';
}
if ($filter_end !== '') {
    $sql .= ' AND sm.movement_date <= ?';
    $params[] = $filter_end . ' 23:59:59';
}

$sql .= ' ORDER BY sm.movement_date DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$movements = $stmt->fetchAll();

$total_in = 0;
$total_out = 0;
foreach ($movements as $m) {
    if ($m['movement_type'] === 'IN') {
        $total_in += (int) $m['quantity'];
    } else {
        $total_out += (int) $m['quantity'];
    }
}
$net_movement = $total_in - $total_out;

$items_stmt = $db->query('SELECT id, item_code, item_name, category FROM items ORDER BY item_code');
$all_items = $items_stmt->fetchAll();

$cat_stmt = $db->query('SELECT DISTINCT category FROM items ORDER BY category');
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Reports';
if ($is_print) {
    $page_title .= ' (Print View)';
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($is_print): ?>
<style>
@media print {
    .no-print { display: none !important; }
    .print-header { display: block !important; }
}
.print-header { display: none; }
</style>

<div class="print-header" style="text-align:center; margin-bottom:24px;">
    <h1><?= e(APP_NAME) ?></h1>
    <h2>Stock Movement Report</h2>
    <p style="color:var(--gray-500);">
        Generated: <?= date('M d, Y h:i A') ?>
        <?php if ($filter_start !== ''): ?> | Period: <?= e($filter_start) ?> to <?= e($filter_end !== '' ? $filter_end : 'present') ?><?php endif; ?>
    </p>
</div>

<div class="card no-print" style="margin-bottom:16px;">
    <div class="stats-grid" style="margin-bottom:0;">
        <div class="stat-card">
            <div class="stat-icon green">i</div>
            <div class="stat-info">
                <h3><?= number_format($total_in) ?></h3>
                <p>Total Stock IN</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">s</div>
            <div class="stat-info">
                <h3><?= number_format($total_out) ?></h3>
                <p>Total Stock OUT</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">j</div>
            <div class="stat-info">
                <h3><?= number_format($net_movement) ?></h3>
                <p>Net Movement</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>IN</th>
                    <th>OUT</th>
                    <th>Reference</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="8" style="text-align:center;">No records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td><?= e(format_date_short($m['movement_date'])) ?></td>
                            <td><strong><?= e($m['item_code']) ?></strong></td>
                            <td><?= e($m['item_name']) ?></td>
                            <td><?= e($m['category']) ?></td>
                            <td><?php if ($m['movement_type'] === 'IN'): ?><strong><?= number_format($m['quantity']) ?></strong><?php else: ?>---<?php endif; ?></td>
                            <td><?php if ($m['movement_type'] === 'OUT'): ?><strong><?= number_format($m['quantity']) ?></strong><?php else: ?>---<?php endif; ?></td>
                            <td><?= e($m['reference_note'] ?? '---') ?></td>
                            <td><?= e($m['user_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>window.onload = function() { window.print(); };</script>

<?php else: ?>
<div class="card no-print">
    <div class="card-header">
        <h3 class="card-title">Filter Report</h3>
    </div>
    <form method="GET" action="" id="reportForm">
        <div class="form-row">
            <div class="form-group">
                <label>Item</label>
                <select name="item" class="form-control">
                    <option value="">All Items</option>
                    <?php foreach ($all_items as $it): ?>
                        <option value="<?= $it['id'] ?>" <?= $filter_item == $it['id'] ? 'selected' : '' ?>>
                            <?= e($it['item_code']) . ' - ' . e($it['item_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $filter_category === $cat ? 'selected' : '' ?>>
                            <?= e($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Movement Type</label>
                <select name="type" class="form-control">
                    <option value="">All</option>
                    <option value="IN" <?= $filter_type === 'IN' ? 'selected' : '' ?>>Stock IN</option>
                    <option value="OUT" <?= $filter_type === 'OUT' ? 'selected' : '' ?>>Stock OUT</option>
                </select>
            </div>
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= e($filter_start) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= e($filter_end) ?>">
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end; gap:8px;">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
            </div>
        </div>
    </form>
</div>

<div class="card no-print">
    <div class="card-header">
        <h3 class="card-title">Report Summary</h3>
    </div>
    <div class="stats-grid" style="margin-bottom:0;">
        <div class="stat-card">
            <div class="stat-icon green">i</div>
            <div class="stat-info">
                <h3><?= number_format($total_in) ?></h3>
                <p>Total Stock IN</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">s</div>
            <div class="stat-info">
                <h3><?= number_format($total_out) ?></h3>
                <p>Total Stock OUT</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">j</div>
            <div class="stat-info">
                <h3><?= number_format($net_movement) ?></h3>
                <p>Net Movement (IN - OUT)</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Detailed Report (<?= count($movements) ?> records)</h3>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>IN</th>
                    <th>OUT</th>
                    <th>Reference</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="8" style="text-align:center; color:var(--gray-500);">No records match your filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td><?= e(format_date_short($m['movement_date'])) ?></td>
                            <td><strong><?= e($m['item_code']) ?></strong></td>
                            <td><?= e($m['item_name']) ?></td>
                            <td><?= e($m['category']) ?></td>
                            <td><?php if ($m['movement_type'] === 'IN'): ?><strong style="color:var(--success)"><?= number_format($m['quantity']) ?></strong><?php else: ?>---<?php endif; ?></td>
                            <td><?php if ($m['movement_type'] === 'OUT'): ?><strong style="color:var(--danger)"><?= number_format($m['quantity']) ?></strong><?php else: ?>---<?php endif; ?></td>
                            <td><?= e($m['reference_note'] ?? '---') ?></td>
                            <td><?= e($m['user_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
