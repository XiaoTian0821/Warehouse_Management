<?php
declare(strict_types=1);

/**
 * Login Page
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect($_SESSION['role'] === 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php');
}

$error = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare('SELECT id, full_name, username, password_hash, role, is_active FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['is_active'] == 1 && password_verify($password, $user['password_hash'])) {
            // Regenerate session
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_login'] = time();

            set_flash('Welcome back, ' . htmlspecialchars($user['full_name']) . '!', 'success');
            redirect($user['role'] === 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$page_title = 'Login';
ob_start();
?>

<div class="login-container">
    <div class="login-box">
        <h1>📦 WMS</h1>
        <p class="subtitle">Warehouse Management System</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/login.php') ?>" novalidate>
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    placeholder="Enter your username"
                    autofocus
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>

        <p style="text-align:center; margin-top:20px; font-size:0.8125rem; color:var(--gray-500);">
            Development accounts: <strong>admin</strong> / <strong>demo</strong>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
    <?= $content ?>
</body>
</html>
