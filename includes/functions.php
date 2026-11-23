<?php
/**
 * functions.php
 * ------------------------------------------------------------
 * Shared helpers used by every page: session start, output
 * escaping, CSRF protection, flash messages, auth guards and
 * small formatting utilities.
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- Output / navigation ---------- */

/** Escape a value for safe HTML output (prevents XSS). */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Build an absolute app URL, e.g. url('patients/index.php'). */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Redirect to an app path and stop the script. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/* ---------- CSRF protection ---------- */

/** Get (or create) the CSRF token for the current session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input to drop inside every POST form. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/** Abort the request if the submitted CSRF token is missing/invalid. */
function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        exit('Invalid or expired form token. Please go back and try again.');
    }
}

/* ---------- Flash messages ---------- */

/** Store a one-time message shown on the next page load. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Print and clear all pending flash messages as Bootstrap alerts. */
function render_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $icons = ['success' => 'check-circle', 'danger' => 'exclamation-octagon',
              'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
    foreach ($_SESSION['flash'] as $f) {
        $icon = $icons[$f['type']] ?? 'info-circle';
        echo '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show d-flex align-items-center gap-2" role="alert">'
           . '<i class="bi bi-' . $icon . '"></i><div>' . e($f['message']) . '</div>'
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
}

/* ---------- Authentication ---------- */

/** Currently logged-in user (array) or null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Send guests to the login page. */
function require_login(): void
{
    if (!current_user()) {
        redirect('login.php');
    }
}

/** Only admins may continue; others are bounced to the dashboard. */
function require_admin(): void
{
    require_login();
    if (current_user()['role'] !== 'admin') {
        flash('danger', 'You do not have permission to access that page.');
        redirect('dashboard.php');
    }
}

/** True if the logged-in user is an admin. */
function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

/* ---------- Formatting helpers ---------- */

/** Format a date/datetime string, returns '—' when empty. */
function fmt_date(?string $date, string $format = 'M d, Y'): string
{
    return $date ? date($format, strtotime($date)) : '—';
}

/** Compute age in years from a birthdate. */
function age(?string $birthdate): string
{
    if (!$birthdate) {
        return '—';
    }
    return (string) (new DateTime($birthdate))->diff(new DateTime())->y;
}

/** Bootstrap badge colour for a visit disposition. */
function disposition_badge(string $disposition): string
{
    $map = [
        'Returned to class'    => 'success',
        'Observation'          => 'info',
        'Sent home'            => 'warning',
        'Referred to hospital' => 'danger',
    ];
    $color = $map[$disposition] ?? 'secondary';
    return '<span class="badge rounded-pill text-bg-' . $color . '">' . e($disposition) . '</span>';
}

/** Bootstrap badge colour for a patient type. */
function type_badge(string $type): string
{
    $map = ['Student' => 'primary', 'Faculty' => 'purple', 'Staff' => 'secondary'];
    return '<span class="badge badge-soft-' . ($map[$type] ?? 'secondary') . '">' . e($type) . '</span>';
}

/** Temperature display with a "fever" badge at 37.5 °C and above. */
function temp_display($temp): string
{
    if ($temp === null || $temp === '') {
        return '—';
    }
    $html = e(number_format((float) $temp, 1)) . ' &deg;C';
    if ((float) $temp >= 37.5) {
        $html .= ' <span class="badge text-bg-danger">Fever</span>';
    }
    return $html;
}

/**
 * Render Bootstrap pagination links.
 * Keeps the current GET filters (search, type, dates...) in each link.
 */
function render_pagination(int $page, int $totalPages): void
{
    if ($totalPages <= 1) {
        return;
    }
    $link = function (int $p) {
        $query = array_merge($_GET, ['page' => $p]);
        return '?' . http_build_query($query);
    };
    echo '<nav><ul class="pagination pagination-sm mb-0">';
    echo '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . e($link($page - 1)) . '">&laquo;</a></li>';
    for ($p = 1; $p <= $totalPages; $p++) {
        echo '<li class="page-item' . ($p === $page ? ' active' : '') . '"><a class="page-link" href="' . e($link($p)) . '">' . $p . '</a></li>';
    }
    echo '<li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '"><a class="page-link" href="' . e($link($page + 1)) . '">&raquo;</a></li>';
    echo '</ul></nav>';
}

/** Keep the posted value of a field (used to refill forms after errors). */
function old(array $data, string $key, $default = '')
{
    return $data[$key] ?? $default;
}

/** Return ' selected' when two values match (for <option>). */
function selected($a, $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}
