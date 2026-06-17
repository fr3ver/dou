<?php
require_once __DIR__ . '/config.php';

function auth_require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php?next=change_password.php');
        exit;
    }
}

function auth_dashboard_url(int $roleId): string
{
    return match ($roleId) {
        3, 4    => 'admin/dashboard.php',
        2       => 'employee/dashboard.php',
        default => 'parent/dashboard.php',
    };
}

function auth_set_flash(string $type, string $message): void
{
    $_SESSION['auth_flash'] = ['type' => $type, 'message' => $message];
}

function auth_render_flash(): void
{
    if (empty($_SESSION['auth_flash'])) {
        return;
    }
    $flash = $_SESSION['auth_flash'];
    unset($_SESSION['auth_flash']);
    $class = $flash['type'] === 'success' ? 'alert-success' : 'alert-danger';
    echo '<div class="alert ' . $class . ' border-0 mb-4">' . htmlspecialchars($flash['message']) . '</div>';
}
