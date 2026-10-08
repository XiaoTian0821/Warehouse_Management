<?php
declare(strict_types=1);

/**
 * Admin - Stock Movements
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$db = getDB();
$error = '';
$success = '';

// --- Handle POST: Record stock movement ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Invalid security token. Please try again.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'record') {
        $item_id    = validate_id($_POST['item_id'] ?? 0);
        $movement_type = strtoupper(trim($_POST['movement_type'] ?? ''));
        $quantity   = validate_positive_int($_POST['quantity'] ?? 0);
        $note       = trim($_POST['reference_note'] ?? '');

        if ($item_id === null) {
            $error = 'Please select an item.';
        } elseif (!in_array($movement_type, ['IN', 'OUT'])) {
            $error = 'Invalid movement type.';
        } elseif ($quantity === null) {
            $error = 'Quantity must be a positive number.';
        } else {
            // Use transaction with row locking
            try {
                $db->beginTransaction();

                // Lock the item row
                $stmt = $db->prepare('SELECT id, item_code, item_name, quantity FROM items WHERE id = ? FOR UPDATE');
                $stmt->execute([$item_id]);
                $item = $stmt->fetch();

                if (!$item) {
                    throw new Exception('Item not found.');
                }

                if ($movement_type === 'OUT' && $quantity > $item['quantity']) {
                    $db->rollBack();
                    $error = 'Insufficient stock! Current stock: ' . number_format($item['quantity']) . ', requested OUT: ' . number_format($quantity);
                } else {
                    // Insert movement record
                    $movStmt = $db->prepare('
                        INSERT INTO stock_movements (item_id, movement_type, quantity, movement_date, reference_note, created_by)
                        VALUES (?, ?, ?, NOW(), ?, ?)
                    ');
                    $movStmt->execute([$item_id, $movement_type, $quantity, $note, current_user_id()]);

                    // Update item quantity
                    $newQty = $movement_type === 'IN'
                        ? $item['quantity'] + $quantity
                        : $item['quantity'] - $quantity;

                    $updStmt = $db->prepare('UPDATE items SET quantity = ?, updated_at = NOW() WHERE id = ?');
                    $updStmt->execute([$newQty, $item_id]);

                    $db->commit();

                    set_flash(
                        'Stock ' . $movement_type . ' recorded: ' . number_format($quantity) . ' ' . htmlspecialchars($item['item_code']) . '. New stock: ' . number_format($newQty) . '.',
                        'success'
                    );
                    redirect('/admin/movements.php');
                }
            } catch (Exception $ex) {
                $db->rollBack();
                $error = 'Transaction failed: ' . htmlspecialchars($ex->getMessage());
                error_log('Stock movement error: ' . $ex->getMessage());
            }
        }
    }
}

// --- Get all items for dropdown ---
$items_stmt = $db->query('SELECT id, item_code, item_name FROM items ORDER BY item_code');
$all_items = $items_stmt->fetchAll();

// --- Get filter parameters ---
$filter_item = (int) ($_GET['item'] ?? 0);
$filter_type = trim($_GET['type'] ?? '');
$filter_start = trim($_GET['start_date'] ?? '');
$filter_end = trim($_GET['end_date'] ?? '');

// Build query
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

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = MOVEMENTS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Count
$count_sql = 'SELECT COUNT(*) AS total FROM stock_movements sm WHERE 1=1';
$count_params = [];
if ($filter_item > 0) { $count_sql .= ' AND sm.item_id = ?'; $count_params[] = $filter_item; }
if ($filter_type !== '') { $count_sql .= ' AND sm.movement_type = ?'; $count_params[] = $filter_type; }
if ($filter_start !== '') { $count_sql .= ' AND sm.movement_date >= ?'; $count_params[] = $filter_start . ' 00:00:00'; }
if ($filter_end !== '') { $count_sql .= ' AND sm.movement_date <= ?'; $count_params[] = $filter_end . ' 23:59:59'; }
$stmt = $db->prepare($count_sql);
$stmt->execute($count_params);
$total = (int) $stmt->fetch()['total'];
$total_pages = max(1, (int) ceil($total / $per_page));

$sql .= ' LIMIT ' . (int) $per_page . ' OFFSET ' . (int) $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$movements = $stmt->fetchAll();

$page_title = 'Stock Movements';
include __DIR__ . '/../includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📥 Record Stock Movement</h3>
    </div>
    <form method="POST" action="<?= url('/admin/movements.php') ?>">
        <input type="hidden" name="action" value="record">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label>Item <span class="required">*</span></label>
                <select name="item_id" class="form-control" required>
                    <option value="">-- Select Item --</option>
                    <?php foreach ($all_items as $it): ?>
                        <option value="<?= $it['id'] ?>"><?= e($it['item_code']) . ' - ' . e($it['item_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Movement Type <span class="required">*</span></label>
                <select name="movement_type" class="form-control" required>
                    <option value="IN">📥 Stock IN</option>
                    <option value="OUT">📤 Stock OUT</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Quantity <span class="required">*</span></label>
                <input type="number" name="quantity" class="form-control" min="1" required placeholder="Enter quantity">
            </div>
            <div class="form-group">
                <label>Reference Note</label>
                <input type="text" name="reference_note" class="form-control" placeholder="Optional note">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Record Movement</button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📋 Movement History (<?= $total ?> records)</h3>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
        <div class="form-group">
            <label>Item</label>
            <select class="form-control" id="filterItem">
                <option value="">All Items</option>
                <?php foreach ($all_items as $it): ?>
                    <option value="<?= $it['id'] ?>" <?= $filter_item == $it['id'] ? 'selected' : '' ?>>
                        <?= e($it['item_code']) . ' - ' . e($it['item_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="max-width:150px;">
            <label>Type</label>
            <select class="form-control" id="filterType">
                <option value="">All</option>
                <option value="IN" <?= $filter_type === 'IN' ? 'selected' : '' ?>>IN</option>
                <option value="OUT" <?= $filter_type === 'OUT' ? 'selected' : '' ?>>OUT</option>
            </select>
        </div>
        <div class="form-group">
            <label>From</label>
            <input type="date" class="form-control" id="filterStart" value="<?= e($filter_start) ?>">
        </div>
        <div class="form-group">
            <label>To</label>
            <input type="date" class="form-control" id="filterEnd" value="<?= e($filter_end) ?>">
        </div>
        <div class="form-group" style="flex:0;">
            <label>&nbsp;</label>
            <button class="btn btn-secondary" onclick="applyFilters()">Filter</button>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>Reference</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="7" style="text-align:center; color:var(--gray-500);">No movements found.</td></tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td><?= e(format_date_short($m['movement_date'])) ?></td>
                            <td><strong><?= e($m['item_code']) ?></strong></td>
                            <td><?= e($m['item_name']) ?></td>
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

    <?php if ($total_pages > 1): ?>
        <div style="padding:16px 0; text-align:center;">
            <nav style="display:inline-flex; gap:4px;">
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <a href="?page=<?= $p ?><?php echo $filter_item ? '&item=' . $filter_item : ''; ?><?php echo $filter_type ? '&type=' . urlencode($filter_type) : ''; ?><?php echo $filter_start ? '&start_date=' . urlencode($filter_start) : ''; ?><?php echo $filter_end ? '&end_date=' . urlencode($filter_end) : ''; ?>"
                       class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
function applyFilters() {
    var item = document.getElementById('filterItem').value;
    var type = document.getElementById('filterType').value;
    var start = document.getElementById('filterStart').value;
    var end = document.getElementById('filterEnd').value;
    var params = [];
    if (item) params.push('item=' + item);
    if (type) params.push('type=' + encodeURIComponent(type));
    if (start) params.push('start_date=' + encodeURIComponent(start));
    if (end) params.push('end_date=' + encodeURIComponent(end));
    window.location.href = '?<?= http_build_query([], '', '&') ?>' + params.join('&');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
