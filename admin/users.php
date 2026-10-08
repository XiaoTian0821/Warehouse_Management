<?php
declare(strict_types=1);

/**
 * Admin - User Management
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$db = getDB();
$current_user_id = current_user_id();
$error = '';
$success = '';

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Invalid security token. Please try again.';
    } elseif (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $full_name = trim($_POST['full_name'] ?? '');
                $username  = trim($_POST['username'] ?? '');
                $role      = trim($_POST['role'] ?? 'user');
                $password  = $_POST['password'] ?? '';

                if ($full_name === '' || $username === '' || $password === '') {
                    $error = 'Please fill in all required fields.';
                } elseif (!in_array($role, ['admin', 'user'])) {
                    $error = 'Invalid role.';
                } elseif (strlen($password) < 6) {
                    $error = 'Password must be at least 6 characters long.';
                } else {
                    // Check username uniqueness
                    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $error = 'Username "' . htmlspecialchars($username) . '" already exists.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare('INSERT INTO users (full_name, username, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)');
                        $stmt->execute([$full_name, $username, $hash, $role]);
                        set_flash('User "' . htmlspecialchars($full_name) . '" added successfully.', 'success');
                        redirect('/admin/users.php');
                    }
                }
                break;

            case 'edit':
                $id          = validate_id($_POST['id'] ?? 0);
                $full_name   = trim($_POST['full_name'] ?? '');
                $username    = trim($_POST['username'] ?? '');
                $role        = trim($_POST['role'] ?? 'user');
                $password    = $_POST['password'] ?? '';

                if ($id === null) {
                    $error = 'Invalid user ID.';
                } elseif ($id === $current_user_id) {
                    $error = 'You cannot edit your own account from this page.';
                } elseif ($full_name === '' || $username === '') {
                    $error = 'Please fill in all required fields.';
                } elseif (!in_array($role, ['admin', 'user'])) {
                    $error = 'Invalid role.';
                } else {
                    // Check username uniqueness (excluding current)
                    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
                    $stmt->execute([$username, $id]);
                    if ($stmt->fetch()) {
                        $error = 'Username "' . htmlspecialchars($username) . '" already exists.';
                    } else {
                        if ($password !== '') {
                            if (strlen($password) < 6) {
                                $error = 'Password must be at least 6 characters long.';
                            } else {
                                $hash = password_hash($password, PASSWORD_DEFAULT);
                                $stmt = $db->prepare('UPDATE users SET full_name=?, username=?, role=?, password_hash=? WHERE id=?');
                                $stmt->execute([$full_name, $username, $role, $hash, $id]);
                            }
                        } else {
                            $stmt = $db->prepare('UPDATE users SET full_name=?, username=?, role=? WHERE id=?');
                            $stmt->execute([$full_name, $username, $role, $id]);
                        }
                        if ($error === '') {
                            set_flash('User updated successfully.', 'success');
                            redirect('/admin/users.php');
                        }
                    }
                }
                break;

            case 'delete':
                $id = validate_id($_POST['id'] ?? 0);
                if ($id === null) {
                    $error = 'Invalid user ID.';
                } elseif ($id === $current_user_id) {
                    $error = 'You cannot delete your own account.';
                } else {
                    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->execute([$id]);
                    set_flash('User deleted successfully.', 'success');
                    redirect('/admin/users.php');
                }
                break;

            case 'toggle_active':
                $id = validate_id($_POST['id'] ?? 0);
                if ($id === null || $id === $current_user_id) {
                    $error = 'Invalid action.';
                } else {
                    $stmt = $db->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?');
                    $stmt->execute([$id]);
                    set_flash('User status updated.', 'success');
                    redirect('/admin/users.php');
                }
                break;
        }
    }
}

// --- Get all users ---
$stmt = $db->query('SELECT id, full_name, username, role, is_active, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();

// User for editing
$edit_user = null;
if (isset($_GET['edit']) && validate_id($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([validate_id($_GET['edit'])]);
    $edit_user = $stmt->fetch();
}

$page_title = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">👥 Users (<?= count($users) ?>)</h3>
        <button class="btn btn-primary" onclick="openAddModal()">+ Add User</button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong><?= e($u['full_name']) ?></strong></td>
                        <td><?= e($u['username']) ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'admin' ? 'badge-warning' : 'badge-info' ?>">
                                <?= e($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(format_date_short($u['created_at'])) ?></td>
                        <td>
                            <div class="action-links">
                                <a href="<?= url('/admin/users.php?edit=' . $u['id']) ?>">Edit</a>
                                <?php if ($u['id'] !== $current_user_id): ?>
                                    <a href="#" data-confirm="Delete user <?= e($u['username']) ?>?" onclick="deleteUser(<?= $u['id'] ?>, '<?= e($u['username']) ?>')">Delete</a>
                                    <a href="#" onclick="toggleActive(<?= $u['id'] ?>, <?= $u['is_active'] ? 1 : 0 ?>)">
                                        <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </a>
                                <?php else: ?>
                                    <span style="font-size:0.75rem; color:var(--gray-500);">Current</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal-overlay" id="userModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Add User</h3>
            <button class="btn btn-sm btn-secondary" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body">
            <form id="userForm" method="POST" action="<?= url('/admin/users.php') ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="full_name" id="formFullName" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" name="username" id="formUsername" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Role <span class="required">*</span></label>
                    <select name="role" id="formRole" class="form-control">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Password <span class="required">*</span>
                        <span id="passwordHint" style="font-weight:normal; color:var(--gray-500);"></span>
                    </label>
                    <input type="password" name="password" id="formPassword" class="form-control" minlength="6" placeholder="Min 6 characters">
                    <p class="form-text" id="passwordHelp">Enter a password (min 6 characters). Leave blank to keep current password when editing.</p>
                </div>

                <div class="btn-group" style="justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete confirmation form -->
<form id="deleteUserForm" method="POST" action="<?= url('/admin/users.php') ?>" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteUserId">
    <?= csrf_field() ?>
</form>

<!-- Toggle active form -->
<form id="toggleActiveForm" method="POST" action="<?= url('/admin/users.php') ?>" style="display:none;">
    <input type="hidden" name="action" value="toggle_active">
    <input type="hidden" name="id" id="toggleActiveId">
    <?= csrf_field() ?>
</form>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add User';
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('formFullName').value = '';
    document.getElementById('formUsername').value = '';
    document.getElementById('formRole').value = 'user';
    document.getElementById('formPassword').value = '';
    document.getElementById('formPassword').required = true;
    document.getElementById('passwordHint').textContent = '';
    document.getElementById('passwordHelp').textContent = 'Enter a password (min 6 characters).';
    document.getElementById('userModal').classList.add('active');
}

function openEditModal(user) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = user.id;
    document.getElementById('formFullName').value = user.full_name;
    document.getElementById('formUsername').value = user.username;
    document.getElementById('formRole').value = user.role;
    document.getElementById('formPassword').value = '';
    document.getElementById('formPassword').required = false;
    document.getElementById('passwordHint').textContent = '(leave blank to keep current)';
    document.getElementById('passwordHelp').textContent = 'Enter a new password to change it, or leave blank to keep the current one.';
    document.getElementById('userModal').classList.add('active');
}

function closeModal() {
    document.getElementById('userModal').classList.remove('active');
}

function deleteUser(id, username) {
    if (confirm('Are you sure you want to delete user "' + username + '"?')) {
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserForm').submit();
    }
}

function toggleActive(id, isActive) {
    var action = isActive ? 'deactivate' : 'activate';
    if (confirm('Are you sure you want to ' + action + ' this user?')) {
        document.getElementById('toggleActiveId').value = id;
        document.getElementById('toggleActiveForm').submit();
    }
}

function closeOnOverlayClick(e) {
    if (e.target === document.getElementById('userModal')) {
        closeModal();
    }
}
document.getElementById('userModal').addEventListener('click', closeOnOverlayClick);
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
