<?php
declare(strict_types=1);

/**
 * User - View Items (read-only)
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('user');

$db = getDB();

// --- Get filter parameters ---
$search = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');

// Build query
$sql = 'SELECT * FROM items WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (item_code LIKE ? OR item_name LIKE ? OR location LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($category_filter !== '') {
    $sql .= ' AND category = ?';
    $params[] = $category_filter;
}

$sql .= ' ORDER BY item_code ASC';

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = ITEMS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Count
$count_sql = 'SELECT COUNT(*) AS total FROM items WHERE 1=1';
$count_params = [];
if ($search !== '') {
    $count_sql .= ' AND (item_code LIKE ? OR item_name LIKE ? OR location LIKE ?)';
    $like = '%' . $search . '%';
    $count_params[] = $like;
    $count_params[] = $like;
    $count_params[] = $like;
}
if ($category_filter !== '') {
    $count_sql .= ' AND category = ?';
    $count_params[] = $category_filter;
}
$stmt = $db->prepare($count_sql);
$stmt->execute($count_params);
$total = (int) $stmt->fetch()['total'];
$total_pages = max(1, (int) ceil($total / $per_page));

$sql .= ' LIMIT ' . (int) $per_page . ' OFFSET ' . (int) $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Get categories
$cat_stmt = $db->query('SELECT DISTINCT category FROM items ORDER BY category');
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Items';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📦 Items (<?= $total ?>)</h3>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
        <div class="form-group">
            <label>Search</label>
            <input type="text" class="form-control" id="searchInput" placeholder="Code, name, location..." value="<?= e($search) ?>">
        </div>
        <div class="form-group" style="max-width:200px;">
            <label>Category</label>
            <select class="form-control" id="categoryFilter">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $category_filter === $cat ? 'selected' : '' ?>>
                        <?= e($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Reorder</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="9" style="text-align:center; color:var(--gray-500);">No items found.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item):
                        $is_low = $item['quantity'] <= $item['reorder_level'];
                    ?>
                        <tr>
                            <td><strong><?= e($item['item_code']) ?></strong></td>
                            <td><?= e($item['item_name']) ?></td>
                            <td><?= e($item['category']) ?></td>
                            <td><?= e($item['location']) ?></td>
                            <td>
                                <?php if ($is_low): ?>
                                    <span class="badge badge-low-stock"><?= number_format($item['quantity']) ?></span>
                                <?php else: ?>
                                    <?= number_format($item['quantity']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e($item['unit']) ?></td>
                            <td><?= number_format($item['reorder_level']) ?></td>
                            <td>
                                <?php if ($is_low): ?>
                                    <span class="badge badge-low-stock">Low</span>
                                <?php else: ?>
                                    <span class="badge badge-success">OK</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= url('/user/item-details.php?id=' . $item['id']) ?>" class="btn btn-sm btn-secondary">Details</a>
                            </td>
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
                    <a href="?page=<?= $p ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>"
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
    var search = document.getElementById('searchInput').value;
    var category = document.getElementById('categoryFilter').value;
    var params = [];
    if (search) params.push('search=' + encodeURIComponent(search));
    if (category) params.push('category=' + encodeURIComponent(category));
    window.location.href = '?' + params.join('&');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
