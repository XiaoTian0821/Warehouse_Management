<?php
declare(strict_types=1);

/**
 * Admin - Item Management
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$db = getDB();
$error = '';
$success = '';

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!verify_csrf_token()) {
        $error = 'Invalid security token. Please try again.';
    } elseif (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $item_code   = trim($_POST['item_code'] ?? '');
                $item_name   = trim($_POST['item_name'] ?? '');
                $category    = trim($_POST['category'] ?? '');
                $location    = trim($_POST['location'] ?? '');
                $quantity    = validate_non_negative_int($_POST['quantity'] ?? 0);
                $unit        = trim($_POST['unit'] ?? 'pcs');
                $reorder     = validate_non_negative_int($_POST['reorder_level'] ?? 0);

                if ($item_code === '' || $item_name === '' || $category === '' || $location === '') {
                    $error = 'Please fill in all required fields.';
                } elseif ($quantity === null) {
                    $error = 'Quantity must be a valid number (0 or more).';
                } elseif ($reorder === null) {
                    $error = 'Reorder level must be a valid number (0 or more).';
                } else {
                    // Check uniqueness of item_code
                    $stmt = $db->prepare('SELECT id FROM items WHERE item_code = ?');
                    $stmt->execute([$item_code]);
                    if ($stmt->fetch()) {
                        $error = 'Item code "' . htmlspecialchars($item_code) . '" already exists.';
                    } else {
                        $stmt = $db->prepare('INSERT INTO items (item_code, item_name, category, location, quantity, unit, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$item_code, $item_name, $category, $location, $quantity, $unit, $reorder]);
                        set_flash('Item "' . htmlspecialchars($item_code) . '" added successfully.', 'success');
                        redirect('/admin/items.php');
                    }
                }
                break;

            case 'edit':
                $id          = validate_id($_POST['id'] ?? 0);
                $item_code   = trim($_POST['item_code'] ?? '');
                $item_name   = trim($_POST['item_name'] ?? '');
                $category    = trim($_POST['category'] ?? '');
                $location    = trim($_POST['location'] ?? '');
                $unit        = trim($_POST['unit'] ?? 'pcs');
                $reorder     = validate_non_negative_int($_POST['reorder_level'] ?? 0);

                if ($id === null) {
                    $error = 'Invalid item ID.';
                } elseif ($item_code === '' || $item_name === '' || $category === '' || $location === '') {
                    $error = 'Please fill in all required fields.';
                } elseif ($reorder === null) {
                    $error = 'Reorder level must be a valid number (0 or more).';
                } else {
                    // Check uniqueness of item_code (excluding current item)
                    $stmt = $db->prepare('SELECT id FROM items WHERE item_code = ? AND id != ?');
                    $stmt->execute([$item_code, $id]);
                    if ($stmt->fetch()) {
                        $error = 'Item code "' . htmlspecialchars($item_code) . '" already exists.';
                    } else {
                        $stmt = $db->prepare('UPDATE items SET item_code=?, item_name=?, category=?, location=?, unit=?, reorder_level=? WHERE id=?');
                        $stmt->execute([$item_code, $item_name, $category, $location, $unit, $reorder, $id]);
                        set_flash('Item updated successfully.', 'success');
                        redirect('/admin/items.php');
                    }
                }
                break;

            case 'delete':
                $id = validate_id($_POST['id'] ?? 0);
                if ($id === null) {
                    $error = 'Invalid item ID.';
                } else {
                    // Check if item has stock movements
                    $stmt = $db->prepare('SELECT COUNT(*) AS cnt FROM stock_movements WHERE item_id = ?');
                    $stmt->execute([$id]);
                    $cnt = (int) $stmt->fetch()['cnt'];

                    if ($cnt > 0) {
                        $error = 'Cannot delete this item. It has ' . $cnt . ' stock movement record(s). Delete movements first or use a different approach.';
                    } else {
                        $stmt = $db->prepare('DELETE FROM items WHERE id = ?');
                        $stmt->execute([$id]);
                        set_flash('Item deleted successfully.', 'success');
                        redirect('/admin/items.php');
                    }
                }
                break;
        }
    }
}

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

// Count total
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

// Get all categories for filter dropdown
$cat_stmt = $db->query('SELECT DISTINCT category FROM items ORDER BY category');
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

// Item for editing (if edit mode)
$edit_item = null;
if (isset($_GET['edit']) && validate_id($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM items WHERE id = ?');
    $stmt->execute([validate_id($_GET['edit'])]);
    $edit_item = $stmt->fetch();
}

$page_title = 'Manage Items';
include __DIR__ . '/../includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📦 Items (<?= $total ?>)</h3>
        <button class="btn btn-primary" onclick="openAddModal()">+ Add Item</button>
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
                    <option value="<?= e($cat) ?>" <?= $category_filter === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
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
                        <tr class="<?= $is_low ? 'low-stock-row' : '' ?>">
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
                                <div class="action-links">
                                    <a href="<?= url('/user/item-details.php?id=' . $item['id']) ?>">View</a>
                                    <a href="<?= url('/admin/items.php?edit=' . $item['id']) ?>">Edit</a>
                                    <a href="#" data-confirm="Are you sure you want to delete this item?" onclick="deleteItem(<?= $item['id'] ?>, '<?= e($item['item_code']) ?>')">Delete</a>
                                </div>
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

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="itemModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Add Item</h3>
            <button class="btn btn-sm btn-secondary" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body">
            <form id="itemForm" method="POST" action="<?= url('/admin/items.php') ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label>Item Code <span class="required">*</span></label>
                    <input type="text" name="item_code" id="formItemCode" class="form-control" required placeholder="e.g. ITM-001">
                </div>

                <div class="form-group">
                    <label>Item Name <span class="required">*</span></label>
                    <input type="text" name="item_name" id="formItemName" class="form-control" required placeholder="Item name">
                </div>

                <div class="form-group">
                    <label>Category <span class="required">*</span></label>
                    <input type="text" name="category" id="formCategory" class="form-control" required placeholder="e.g. Electronics">
                </div>

                <div class="form-group">
                    <label>Location <span class="required">*</span></label>
                    <input type="text" name="location" id="formLocation" class="form-control" required placeholder="e.g. Shelf A-1">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" id="formQuantity" class="form-control" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <input type="text" name="unit" id="formUnit" class="form-control" value="pcs">
                    </div>
                </div>

                <div class="form-group">
                    <label>Reorder Level</label>
                    <input type="number" name="reorder_level" id="formReorder" class="form-control" min="0" value="0">
                    <p class="form-text">Alert when stock falls to this level.</p>
                </div>

                <div class="btn-group" style="justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete confirmation form -->
<form id="deleteForm" method="POST" action="<?= url('/admin/items.php') ?>" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteItemId">
    <?= csrf_field() ?>
</form>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Item';
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('formItemCode').value = '';
    document.getElementById('formItemName').value = '';
    document.getElementById('formCategory').value = '';
    document.getElementById('formLocation').value = '';
    document.getElementById('formQuantity').value = '0';
    document.getElementById('formUnit').value = 'pcs';
    document.getElementById('formReorder').value = '0';
    document.getElementById('itemModal').classList.add('active');
}

function openEditModal(item) {
    document.getElementById('modalTitle').textContent = 'Edit Item';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = item.id;
    document.getElementById('formItemCode').value = item.item_code;
    document.getElementById('formItemName').value = item.item_name;
    document.getElementById('formCategory').value = item.category;
    document.getElementById('formLocation').value = item.location;
    document.getElementById('formQuantity').value = item.quantity;
    document.getElementById('formUnit').value = item.unit;
    document.getElementById('formReorder').value = item.reorder_level;
    document.getElementById('itemModal').classList.add('active');
}

function closeModal() {
    document.getElementById('itemModal').classList.remove('active');
}

function deleteItem(id, code) {
    if (confirm('Are you sure you want to delete "' + code + '"?')) {
        document.getElementById('deleteItemId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function applyFilters() {
    var search = document.getElementById('searchInput').value;
    var category = document.getElementById('categoryFilter').value;
    var params = [];
    if (search) params.push('search=' + encodeURIComponent(search));
    if (category) params.push('category=' + encodeURIComponent(category));
    window.location.href = '?<?= http_build_query([], '', '&') ?>' + params.join('&');
}

// Close modal on overlay click
document.getElementById('itemModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Keyboard escape to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
