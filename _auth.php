<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/_collapse.php';
require_once __DIR__ . '/_group_helpers.php';

if (!isset($_SESSION['user_id']) || !role_is_admin_panel((int)$_SESSION['role_id'])) {
    header('Location: ../login.php');
    exit;
}

function admin_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function admin_append_head(string $html): void
{
    $GLOBALS['admin_head_extra'] = ($GLOBALS['admin_head_extra'] ?? '') . $html;
}

function admin_append_footer(string $html): void
{
    $GLOBALS['admin_footer_extra'] = ($GLOBALS['admin_footer_extra'] ?? '') . $html;
}

function admin_render_flash(): void
{
    if (empty($_SESSION['admin_flash'])) {
        return;
    }
    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
    $class = match ($flash['type']) {
        'success' => 'alert-success',
        'warning' => 'alert-warning',
        default   => 'alert-danger',
    };
    echo '<div class="alert ' . $class . ' border-0 shadow-sm mb-4">';
    echo htmlspecialchars($flash['message']);
    echo '</div>';
}

function admin_page_start(string $title, string $subtitle = ''): void
{
    $isDashboard = basename($_SERVER['PHP_SELF']) === 'dashboard.php';
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> — Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=27" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.bootstrap5.min.css">
    <?php if (!empty($GLOBALS['admin_head_extra'])) {
        echo $GLOBALS['admin_head_extra'];
    } ?>
</head>
<body class="admin-panel">
<nav class="navbar navbar-dou sticky-top shadow-sm admin-topbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 mb-0" href="../index.php">
            <span class="brand-icon"><i class="bi bi-balloon-heart-fill"></i></span>
            <span class="d-none d-sm-inline"><?= SITE_NAME ?></span>
            <span class="d-inline d-sm-none">Админ</span>
        </a>
        <a href="../api/logout.php" class="btn btn-accent btn-sm admin-topbar-logout">
            <i class="bi bi-box-arrow-right"></i><span class="d-none d-md-inline ms-1">Выйти</span>
        </a>
    </div>
</nav>

<section class="admin-page-header">
    <div class="container">
        <?php if (!$isDashboard): ?>
        <a href="dashboard.php" class="admin-back-link">
            <i class="bi bi-arrow-left"></i> К быстрым действиям
        </a>
        <?php endif; ?>
        <h1 class="display-6 fw-bold mb-1"><?= htmlspecialchars($title) ?></h1>
        <?php if ($subtitle): ?><p class="lead mb-0"><?= htmlspecialchars($subtitle) ?></p><?php endif; ?>
    </div>
</section>
<main class="pb-5"><div class="container">
    <?php admin_render_flash(); ?>
    <?php
}

function admin_page_end(): void
{
    ?>
</div></main>
<footer class="footer-dou py-4">
    <div class="container text-center">
        <p class="footer-copy small mb-0">&copy; 2026 <?= SITE_NAME ?> — Админ-панель</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>
<script src="../assets/js/admin-enhanced-selects.js?v=6"></script>
<script src="../assets/js/admin-search.js?v=2"></script>
<?php if (!empty($GLOBALS['admin_footer_extra'])) {
    echo $GLOBALS['admin_footer_extra'];
} ?>
</body>
</html>
    <?php
}

function admin_assign_teacher(PDO $pdo, int $group_id, ?int $user_id): void
{
    $pdo->prepare("UPDATE employees SET group_id = NULL WHERE group_id = ? AND position = 'Воспитатель'")
        ->execute([$group_id]);

    if (!$user_id) {
        return;
    }

    $stmt = $pdo->prepare('SELECT id FROM employees WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($employee) {
        $pdo->prepare("UPDATE employees SET group_id = ?, position = 'Воспитатель' WHERE user_id = ?")
            ->execute([$group_id, $user_id]);
    } else {
        $pdo->prepare("INSERT INTO employees (user_id, position, group_id) VALUES (?, 'Воспитатель', ?)")
            ->execute([$user_id, $group_id]);
    }
}
