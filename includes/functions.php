<?php
declare(strict_types=1);

/**
 * Helper Functions
 */

/**
 * Escape output for safe HTML display.
 *
 * @param string $value
 * @return string
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL.
 *
 * @param string $url
 */
function redirect(string $url): void
{
    header('Location: ' . BASE_PATH . $url);
    exit;
}

/**
 * Set a success flash message.
 *
 * @param string $message
 */
function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Get and clear the flash message.
 *
 * @return array{ message: string, type: string }|null
 */
function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate a CSRF token and store it in the session.
 *
 * @return string
 */
function csrf_token(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Output a hidden CSRF token input field.
 *
 * @return string
 */
function csrf_field(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(csrf_token()) . '">';
}

/**
 * Verify the CSRF token from a POST request.
 *
 * @return bool
 */
function verify_csrf_token(): bool
{
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!is_string($token) || $token === '') {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

/**
 * Validate and sanitize an integer ID.
 *
 * @param mixed $id
 * @return int|null
 */
function validate_id(mixed $id): ?int
{
    $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : (int) $id;
}

/**
 * Validate a non-negative integer (for quantities, reorder levels, etc.).
 *
 * @param mixed $value
 * @return int|null
 */
function validate_non_negative_int(mixed $value): ?int
{
    $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    return $int === false ? null : (int) $int;
}

/**
 * Validate a positive integer (for movement quantities).
 *
 * @param mixed $value
 * @return int|null
 */
function validate_positive_int(mixed $value): ?int
{
    $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $int === false ? null : (int) $int;
}

/**
 * Get a relative URL from the BASE_PATH.
 *
 * @param string $path
 * @return string
 */
function url(string $path): string
{
    // Ensure path starts with /
    if ($path !== '' && $path[0] !== '/') {
        $path = '/' . $path;
    }
    return BASE_PATH . $path;
}

/**
 * Format a date for display.
 *
 * @param string $date
 * @return string
 */
function format_date(string $date): string
{
    $dt = new DateTime($date);
    return $dt->format('M d, Y h:i A');
}

/**
 * Format a short date for tables.
 *
 * @param string $date
 * @return string
 */
function format_date_short(string $date): string
{
    $dt = new DateTime($date);
    return $dt->format('Y-m-d H:i');
}
