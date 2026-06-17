<?php

function lk_cabinet_type(): string
{
    return (int)($_SESSION['role_id'] ?? 0) === 1 ? 'parent' : 'employee';
}

/** @return list<array{id: string, href: string, icon: string, label: string}> */
function lk_nav_items(): array
{
    if (lk_cabinet_type() === 'parent') {
        return [
            ['id' => 'dashboard',  'href' => 'dashboard.php',           'icon' => 'house-heart',          'label' => 'Главная'],
            ['id' => 'children',   'href' => 'dashboard.php#children',  'icon' => 'emoji-smile',          'label' => 'Мои дети'],
            ['id' => 'documents',  'href' => 'documents.php',           'icon' => 'file-earmark-medical', 'label' => 'Справки'],
            ['id' => 'svedeniya',  'href' => 'svedeniya.php',           'icon' => 'journal-text',         'label' => 'Сведения'],
            ['id' => 'menu',       'href' => 'menu.php',                'icon' => 'cup-hot',              'label' => 'Меню'],
            ['id' => 'clubs',      'href' => 'dashboard.php#clubs',     'icon' => 'palette',              'label' => 'Кружки'],
            ['id' => 'chat',       'href' => '../chat/index.php',       'icon' => 'chat-dots',            'label' => 'Сообщения'],
            ['id' => 'password',   'href' => '../change_password.php',  'icon' => 'key',                  'label' => 'Пароль'],
        ];
    }

    return [
        ['id' => 'dashboard',  'href' => 'dashboard.php',                    'icon' => 'house-heart',     'label' => 'Главная'],
        ['id' => 'group',      'href' => 'dashboard.php#employee-group-info', 'icon' => 'collection',      'label' => 'Моя группа'],
        ['id' => 'attendance', 'href' => 'dashboard.php#employee-teacher',    'icon' => 'clipboard-check', 'label' => 'Посещаемость'],
        ['id' => 'materials',  'href' => 'materials.php',                     'icon' => 'journal-richtext','label' => 'Материалы'],
        ['id' => 'menu',       'href' => 'dashboard.php#menu',                'icon' => 'cup-hot',         'label' => 'Меню'],
        ['id' => 'chat',       'href' => '../chat/index.php',                 'icon' => 'chat-dots',       'label' => 'Сообщения'],
        ['id' => 'password',   'href' => '../change_password.php',            'icon' => 'key',             'label' => 'Пароль'],
    ];
}

function lk_render_sidebar_nav(string $activeNav, string $modifier = ''): void
{
    global $pdo;

    $items = lk_nav_items();
    if (lk_cabinet_type() === 'employee' && isset($pdo, $_SESSION['user_id'])) {
        $items = lk_employee_nav_items($pdo, (int)$_SESSION['user_id']);
    }

    $navClass = 'lk-sidebar-nav' . ($modifier !== '' ? ' lk-sidebar-nav' . $modifier : '');
    ?>
    <nav class="<?= $navClass ?>" aria-label="Разделы кабинета">
        <ul class="lk-sidebar-list">
            <?php foreach ($items as $item): ?>
            <li>
                <a href="<?= htmlspecialchars($item['href']) ?>"
                   class="lk-sidebar-link<?= $activeNav === $item['id'] ? ' is-active' : '' ?>">
                    <span class="lk-sidebar-link-icon"><i class="bi bi-<?= htmlspecialchars($item['icon']) ?>"></i></span>
                    <span class="lk-sidebar-link-label"><?= htmlspecialchars($item['label']) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="lk-sidebar-footer">
            <a href="../index.php" class="lk-sidebar-link lk-sidebar-link--muted">
                <span class="lk-sidebar-link-icon"><i class="bi bi-globe2"></i></span>
                <span class="lk-sidebar-link-label">На сайт</span>
            </a>
        </div>
    </nav>
    <?php
}

/**
 * @param list<array{icon: string, value: string|int, label: string, tone?: string}> $stats
 */
function lk_render_stat_strip(array $stats): void
{
    if ($stats === []) {
        return;
    }
    ?>
    <div class="lk-stat-strip">
        <?php foreach ($stats as $stat): ?>
        <div class="lk-stat-chip lk-stat-chip--<?= htmlspecialchars($stat['tone'] ?? 'blue') ?>">
            <span class="lk-stat-chip-icon" aria-hidden="true">
                <i class="bi bi-<?= htmlspecialchars($stat['icon']) ?>"></i>
            </span>
            <div class="lk-stat-chip-body">
                <div class="lk-stat-chip-value"><?= htmlspecialchars((string)$stat['value']) ?></div>
                <div class="lk-stat-chip-label"><?= htmlspecialchars($stat['label']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function lk_page_start(
    string $title,
    string $subtitle = '',
    array $badges = [],
    string $base = '..',
    string $cabinetLabel = 'Личный кабинет',
    string $activeNav = 'dashboard',
    array $heroStats = []
): void {
    $fullName = htmlspecialchars($_SESSION['full_name'] ?? '');
    $cabinetType = lk_cabinet_type();
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> — <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?= htmlspecialchars($base) ?>/assets/css/style.css?v=44" rel="stylesheet">
</head>
<body class="lk-cabinet lk-cabinet--<?= htmlspecialchars($cabinetType) ?>">

<header class="lk-topbar sticky-top">
    <div class="container">
        <div class="lk-topbar-inner">
            <a class="lk-topbar-brand" href="<?= htmlspecialchars($base) ?>/index.php">
                <span class="brand-icon"><i class="bi bi-balloon-heart-fill"></i></span>
                <span class="d-none d-sm-inline"><?= SITE_NAME ?></span>
            </a>
            <div class="lk-topbar-user d-none d-md-flex align-items-center gap-2">
                <?php foreach ($badges as $badge): ?>
                <span class="lk-topbar-badge"><?= htmlspecialchars($badge['text']) ?></span>
                <?php endforeach; ?>
                <span class="lk-topbar-name"><i class="bi bi-person-circle me-1"></i><?= $fullName ?></span>
            </div>
            <div class="lk-topbar-actions">
                <a href="<?= htmlspecialchars($base) ?>/index.php" class="lk-topbar-btn d-none d-sm-inline-flex" title="На сайт">
                    <i class="bi bi-house"></i>
                </a>
                <a href="<?= htmlspecialchars($base) ?>/api/logout.php" class="lk-topbar-btn lk-topbar-btn--logout" title="Выйти">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-lg-inline ms-1">Выйти</span>
                </a>
            </div>
        </div>
    </div>
</header>

<section class="lk-hero">
    <div class="container">
        <div class="lk-hero-card">
            <p class="lk-hero-label"><?= htmlspecialchars($cabinetLabel) ?></p>
            <h1 class="lk-hero-title"><?= htmlspecialchars($title) ?></h1>
            <?php if ($subtitle !== ''): ?>
                <p class="lk-hero-subtitle"><?= $subtitle ?></p>
            <?php endif; ?>
            <?php lk_render_stat_strip($heroStats); ?>
        </div>
    </div>
</section>

<main class="lk-main-wrap">
    <div class="container lk-shell">
        <div class="lk-sidebar-col d-none d-lg-block">
            <?php lk_render_sidebar_nav($activeNav); ?>
        </div>
        <div class="lk-content-col">
            <div class="lk-mobile-nav d-lg-none">
                <?php lk_render_sidebar_nav($activeNav, '--mobile'); ?>
            </div>
            <div class="lk-content">
    <?php
    require_once __DIR__ . '/auth_helpers.php';
    auth_render_flash();
}

function lk_page_end(bool $withMenuDayPicker = false, string $base = '..'): void
{
    ?>
            </div>
        </div>
    </div>
</main>

<footer class="lk-footer">
    <div class="container text-center">
        <p class="mb-0">&copy; 2026 <?= SITE_NAME ?></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($withMenuDayPicker): ?>
<script src="<?= htmlspecialchars($base) ?>/assets/js/lk-menu-day.js?v=1"></script>
<?php endif; ?>
</body>
</html>
    <?php
}

function lk_section_title(string $icon, string $title, string $subtitle = ''): void
{
    ?>
    <header class="lk-section-head">
        <h2 class="lk-section-title">
            <i class="bi bi-<?= htmlspecialchars($icon) ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($title) ?>
        </h2>
        <?php if ($subtitle !== ''): ?>
            <p class="lk-section-subtitle"><?= htmlspecialchars($subtitle) ?></p>
        <?php endif; ?>
    </header>
    <?php
}

function lk_panel_open(string $extraClass = ''): void
{
    $class = 'lk-panel' . ($extraClass !== '' ? ' ' . $extraClass : '');
    echo '<div class="' . htmlspecialchars($class) . '">';
}

function lk_panel_close(): void
{
    echo '</div>';
}
