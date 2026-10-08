<?php
declare(strict_types=1);

/**
 * Authentication & Authorization Helpers
 */

/**
 * Check if the user is logged in.
 *
 * @return bool
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id'])
        && isset($_SESSION['username'])
        && isset($_SESSION['role'])
        && isset($_SESSION['full_name']);
}

/**
 * Require the user to be logged in. Redirects to login if not.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect(BASE_PATH . '/login.php');
    }
}

/**
 * Require the user to have a specific role.
 *
 * @param string $role 'admin' or 'user'
 */
function require_role(string $role): void
{
    require_login();

    if ($_SESSION['role'] !== $role) {
        redirect(BASE_PATH . '/user/dashboard.php');
    }
}

/**
 * Get the current user's data.
 *
 * @return array{ id: int, full_name: string, username: string, role: string }
 */
function get_user_data(): array
{
    return [
        'id'        => (int) ($_SESSION['user_id'] ?? 0),
        'full_name' => $_SESSION['full_name'] ?? '',
        'username'  => $_SESSION['username'] ?? '',
        'role'      => $_SESSION['role'] ?? 'user',
    ];
}

/**
 * Get the current user's ID.
 *
 * @return int
 */
function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}
