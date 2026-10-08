<?php
declare(strict_types=1);

/**
 * User - Item Details (read-only)
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('user');

$db = getDB();

// Validate and fetch item
$item_id = validate_id($_GET['id'] ?? 0);
if ($item_id === null) {
    set_flash('Invalid item ID.', 'error');
    redirect('/user/items.php');
}

$stmt = $db->prepare('SELECT * FROM items WHERE id = ?');
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    set_flash('Item not found.', 'error');
    redirect('/user/items.php');
}

// Recent stock movements for this item
$mov_stmt = $db->prepare("
    SELECT sm.*, u.full_name AS user_name
    FROM stock_movements sm
    JOIN users u ON sm.created_by = u.id
    WHERE sm.item_id = ?
    ORDER BY sm.movement_date DESC
    LIMIT 20
");
$mov_stmt->execute([$item_id]);
$movements = $mov_stmt->fetchAll();

$is_low = $item['quantity'] <= $item['reorder_level'];

$page_title = e($item['item_code']) . ' - ' . e($item['item_name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📦 Item Details</h3>
        <a href="<?= url('/user/items.php') ?>" class="btn btn-sm btn-secondary">← Back to Items</a>
    </div>

    <div class="details-grid">
        <div class="detail-item">
            <div class="label">Item Code</div>
            <div class="value"><?= e($item['item_code']) ?></div>
        </div>
        <div class="detail-item">
            <div class="label">Item Name</div>
            <div class="value"><?= e($item['item_name']) ?></div>
        </div>
        <div class="detail-item">
            <div class="label">Category</div>
            <div class="value"><?= e($item['category']) ?></div>
        </div>
        <div class="detail-item">
            <div class="label">Location</div>
            <div class="value"><?= e($item['location']) ?></div>
        </div>
        <div class="detail-item">
            <div class="label">Current Quantity</div>
            <div class="value">
                <?php if ($is_low): ?>
                    <span class="badge badge-low-stock"><?= number_format($item['quantity']) ?> <?= e($item['unit']) ?></span>
                <?php else: ?>
                    <strong><?= number_format($item['quantity']) ?></strong> <?= e($item['unit']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="detail-item">
            <div class="label">Reorder Level</div>
            <div class="value"><?= number_format($item['reorder_level']) ?> <?= e($item['unit']) ?></div>
        </div>
        <div class="detail-item">
            <div class="label">Created</div>
            <div class="value"><?= e(format_date($item['created_at'])) ?></div>
        </div>
        <div class="detail-item">
            <div class="label">Last Updated</div>
            <div class="value"><?= e(format_date($item['updated_at'])) ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">🔄 Stock Movement History</h3>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Reference</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="5" style="text-align:center; color:var(--gray-500);">No movement history.</td></tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td><?= e(format_date_short($m['movement_date'])) ?></td>
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
