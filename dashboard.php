<?php
require_once '../includes/config.php';
require_once '../includes/roles.php';
require_once '../includes/qualification_helpers.php';
require_once '../includes/club_enrollment.php';

if (!isset($_SESSION['user_id']) || !role_is_admin_panel((int)$_SESSION['role_id'])) {
    header('Location: ../login.php');
    exit;
}

$total_groups = 0;
$total_children = 0;
$total_employees = 0;
$total_users = 0;
$db_error = null;
$qualification_reminders = [];
$club_pending_count = 0;
$club_age_mismatch = [];
$can_qualifications = role_can_access_qualifications((int)$_SESSION['role_id']);

try {
    $total_groups    = (int) $pdo->query("SELECT COUNT(*) FROM `groups`")->fetchColumn();
    $total_children  = (int) $pdo->query("SELECT COUNT(*) FROM children")->fetchColumn();
    $total_employees = (int) $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    $total_users     = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($can_qualifications) {
        $qualification_reminders = admin_qualification_reminders($pdo);
    }
    $club_pending_count = admin_club_applications_pending_count($pdo);
    $club_age_mismatch = admin_club_applications_age_mismatch_pending($pdo);
} catch (Exception $e) {
    $db_error = $e->getMessage();
}
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель — <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=9" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dou sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="../index.php">
            <span class="brand-icon"><i class="bi bi-balloon-heart-fill"></i></span>
            <span><?= SITE_NAME ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" aria-label="Меню">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item d-none d-lg-block">
                    <span class="nav-link text-muted">
                        <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['full_name']) ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a href="../index.php" class="nav-link"><i class="bi bi-house me-1"></i>На сайт</a>
                </li>
                <li class="nav-item">
                    <a href="../api/logout.php" class="btn btn-accent px-4">
                        <i class="bi bi-box-arrow-right me-1"></i>Выйти
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="admin-page-header">
    <div class="container">
        <span class="hero-badge mb-2 d-inline-block">Панель администратора</span>
        <h1 class="display-5 fw-bold">
            <i class="bi bi-shield-lock-fill me-2"></i>Управление детским садом
        </h1>
        <p class="lead mb-0">
            Добро пожаловать, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
        </p>
    </div>
</section>

<main class="pb-5">
    <div class="container">

        <!-- Быстрые действия -->
        <?php
        $quick_links = [
            ['href' => 'users.php',      'icon' => 'bi-people',          'label' => 'Пользователи', 'color' => 'green'],
            ['href' => 'employees.php',  'icon' => 'bi-person-badge',    'label' => 'Сотрудники',   'color' => 'orange'],
            ['href' => 'groups.php',     'icon' => 'bi-grid-3x3-gap',     'label' => 'Группы',       'color' => 'blue'],
            ['href' => 'children.php',   'icon' => 'bi-emoji-smile',     'label' => 'Дети',         'color' => 'yellow'],
            ['href' => 'documents.php',  'icon' => 'bi-file-earmark-medical', 'label' => 'Справки детей', 'color' => 'blue'],
            ['href' => 'svedeniya.php', 'icon' => 'bi-journal-text', 'label' => 'Сведения', 'color' => 'blue'],
            ['href' => 'allergies.php',  'icon' => 'bi-droplet',         'label' => 'Аллергены',    'color' => 'blue'],
            ['href' => 'events.php',     'icon' => 'bi-calendar-event',  'label' => 'Мероприятия',  'color' => 'green'],
            ['href' => 'clubs.php',      'icon' => 'bi-palette',         'label' => 'Кружки',       'color' => 'yellow'],
            ['href' => 'club_applications.php', 'icon' => 'bi-inbox',  'label' => 'Заявки в кружки', 'color' => 'orange'],
            ['href' => 'news.php',       'icon' => 'bi-newspaper',       'label' => 'Новости',      'color' => 'blue'],
            ['href' => '../chat/index.php', 'icon' => 'bi-chat-dots',   'label' => 'Сообщения',    'color' => 'green'],
            ['href' => 'menu.php',       'icon' => 'bi-egg-fried',       'label' => 'Меню',         'color' => 'orange'],
            ['href' => 'reviews.php',    'icon' => 'bi-chat-quote',      'label' => 'Отзывы',       'color' => 'yellow'],
            ['href' => 'qualifications.php', 'icon' => 'bi-mortarboard', 'label' => 'Повышение квалификации', 'color' => 'orange'],
            ['href' => 'reports.php',    'icon' => 'bi-bar-chart',       'label' => 'Отчёты',       'color' => 'green'],
        ];
        if (!role_can_access_reports((int)$_SESSION['role_id'])) {
            $quick_links = array_values(array_filter(
                $quick_links,
                static fn(array $link): bool => $link['href'] !== 'reports.php'
            ));
        }
        if (!$can_qualifications) {
            $quick_links = array_values(array_filter(
                $quick_links,
                static fn(array $link): bool => $link['href'] !== 'qualifications.php'
            ));
        }
        ?>
        <div class="mb-5">
            <span class="section-label">Навигация</span>
            <h2 class="section-title mb-4">Быстрые действия</h2>
            <div class="admin-quick-grid">
                <?php foreach ($quick_links as $link): ?>
                <a href="<?= htmlspecialchars($link['href']) ?>" class="admin-quick-tile">
                    <span class="admin-quick-tile-icon feature-icon-<?= $link['color'] ?>">
                        <i class="bi <?= htmlspecialchars($link['icon']) ?>"></i>
                    </span>
                    <span class="admin-quick-tile-label"><?= htmlspecialchars($link['label']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($club_pending_count > 0): ?>
        <div class="alert alert-<?= $club_age_mismatch !== [] ? 'warning' : 'info' ?> border-0 shadow-sm mb-4">
            <h2 class="h6 fw-bold mb-2"><i class="bi bi-inbox me-1"></i>Заявки в кружки</h2>
            <p class="mb-2 small mb-0">
                На рассмотрении: <strong><?= $club_pending_count ?></strong>
                <?php if ($club_age_mismatch !== []): ?>
                    · с возможным несоответствием возраста: <strong><?= count($club_age_mismatch) ?></strong>
                <?php endif; ?>
            </p>
            <?php if ($club_age_mismatch !== []): ?>
            <ul class="small mb-3 ps-3">
                <?php foreach (array_slice($club_age_mismatch, 0, 5) as $row): ?>
                <li>
                    <?= htmlspecialchars($row['child_name']) ?> → <?= htmlspecialchars($row['club_name']) ?>:
                    <?= htmlspecialchars($row['age_warning']['message']) ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <a href="club_applications.php?filter=pending" class="btn btn-sm btn-outline-dark">Открыть заявки</a>
        </div>
        <?php endif; ?>

        <?php if ($can_qualifications && $qualification_reminders !== []): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <h2 class="h6 fw-bold mb-2"><i class="bi bi-mortarboard me-1"></i>Повышение квалификации — напоминания</h2>
            <ul class="mb-2 ps-3">
                <?php foreach (array_slice($qualification_reminders, 0, 5) as $row): ?>
                <li>
                    <?= htmlspecialchars($row['full_name']) ?>
                    — до <?= admin_qualification_format_date($row['next_due_on']) ?>
                    <?= admin_qualification_status_badge($row['status']) ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <a href="qualifications.php" class="btn btn-sm btn-outline-dark">Открыть учёт курсов</a>
        </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="admin-stats-bar card border-0 shadow-sm mb-5">
            <div class="card-body py-4">
                <div class="row text-center g-3">
                    <div class="col-6 col-md-3">
                        <div class="admin-stat-item">
                            <i class="bi bi-collection fs-4" style="color: var(--dou-blue-dark);"></i>
                            <div class="admin-stat-value"><?= $total_groups ?></div>
                            <div class="admin-stat-label">Групп</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="admin-stat-item">
                            <i class="bi bi-emoji-smile fs-4" style="color: var(--dou-green-dark);"></i>
                            <div class="admin-stat-value"><?= $total_children ?></div>
                            <div class="admin-stat-label">Детей</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="admin-stat-item">
                            <i class="bi bi-person-badge fs-4" style="color: var(--dou-orange-dark);"></i>
                            <div class="admin-stat-value"><?= $total_employees ?></div>
                            <div class="admin-stat-label">Сотрудников</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="admin-stat-item">
                            <i class="bi bi-people fs-4" style="color: var(--dou-blue-dark);"></i>
                            <div class="admin-stat-value"><?= $total_users ?></div>
                            <div class="admin-stat-label">Пользователей</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($db_error): ?>
            <div class="alert alert-danger border-0 shadow-sm">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($db_error) ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<footer class="footer-dou py-4">
    <div class="container text-center">
        <p class="footer-copy small mb-0">&copy; 2026 <?= SITE_NAME ?> — Админ-панель</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
