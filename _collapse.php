<?php

function admin_collapse_start(string $id, string $title, bool $expanded = false, ?string $badge = null, ?string $subtitle = null, bool $collapsible = true): void
{
    if (!$collapsible) {
        ?>
<div class="admin-collapse card border-0 shadow-sm mb-4">
    <div class="admin-collapse-header">
        <div class="admin-collapse-btn admin-collapse-btn-static">
            <span class="admin-collapse-title"><?= htmlspecialchars($title) ?></span>
            <?php if ($badge !== null): ?>
                <span class="badge bg-soft-blue text-dark ms-1"><?= htmlspecialchars($badge) ?></span>
            <?php endif; ?>
            <?php if ($subtitle): ?>
                <span class="admin-collapse-subtitle"><?= htmlspecialchars($subtitle) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="admin-collapse-body">
        <?php
        return;
    }

    $showClass = $expanded ? ' show' : '';
    $expandedAttr = $expanded ? 'true' : 'false';
    $collapsedClass = $expanded ? '' : ' collapsed';
    ?>
<div class="admin-collapse card border-0 shadow-sm mb-4">
    <div class="admin-collapse-header">
        <button class="admin-collapse-btn<?= $collapsedClass ?>" type="button"
                data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($id) ?>"
                aria-expanded="<?= $expandedAttr ?>" aria-controls="<?= htmlspecialchars($id) ?>">
            <i class="bi bi-chevron-down admin-collapse-chevron" aria-hidden="true"></i>
            <span class="admin-collapse-title"><?= htmlspecialchars($title) ?></span>
            <?php if ($badge !== null): ?>
                <span class="badge bg-soft-blue text-dark ms-1"><?= htmlspecialchars($badge) ?></span>
            <?php endif; ?>
            <?php if ($subtitle): ?>
                <span class="admin-collapse-subtitle"><?= htmlspecialchars($subtitle) ?></span>
            <?php endif; ?>
        </button>
    </div>
    <div id="<?= htmlspecialchars($id) ?>" class="collapse<?= $showClass ?>">
        <div class="admin-collapse-body">
    <?php
}

function admin_collapse_end(bool $collapsible = true): void
{
    if (!$collapsible) {
        ?>
    </div>
</div>
        <?php
        return;
    }

    ?>
        </div>
    </div>
</div>
    <?php
}

function admin_collapse_toolbar(string $id, string $title, bool $expanded, ?string $badge, string $toolbarHtml, bool $collapsible = true): void
{
    if (!$collapsible) {
        ?>
<div class="admin-collapse card border-0 shadow-sm mb-4">
    <div class="admin-collapse-header admin-collapse-header-with-actions">
        <div class="admin-collapse-btn admin-collapse-btn-static">
            <span class="admin-collapse-title"><?= htmlspecialchars($title) ?></span>
            <?php if ($badge !== null): ?>
                <span class="badge bg-soft-blue text-dark ms-1"><?= htmlspecialchars($badge) ?></span>
            <?php endif; ?>
        </div>
        <div class="admin-collapse-actions"><?= $toolbarHtml ?></div>
    </div>
    <div class="admin-collapse-body p-0">
        <?php
        return;
    }

    $showClass = $expanded ? ' show' : '';
    $expandedAttr = $expanded ? 'true' : 'false';
    $collapsedClass = $expanded ? '' : ' collapsed';
    ?>
<div class="admin-collapse card border-0 shadow-sm mb-4">
    <div class="admin-collapse-header admin-collapse-header-with-actions">
        <button class="admin-collapse-btn<?= $collapsedClass ?>" type="button"
                data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($id) ?>"
                aria-expanded="<?= $expandedAttr ?>" aria-controls="<?= htmlspecialchars($id) ?>">
            <i class="bi bi-chevron-down admin-collapse-chevron" aria-hidden="true"></i>
            <span class="admin-collapse-title"><?= htmlspecialchars($title) ?></span>
            <?php if ($badge !== null): ?>
                <span class="badge bg-soft-blue text-dark ms-1"><?= htmlspecialchars($badge) ?></span>
            <?php endif; ?>
        </button>
        <div class="admin-collapse-actions"><?= $toolbarHtml ?></div>
    </div>
    <div id="<?= htmlspecialchars($id) ?>" class="collapse<?= $showClass ?>">
        <div class="admin-collapse-body p-0">
    <?php
}

function admin_collapse_toolbar_end(bool $collapsible = true): void
{
    if (!$collapsible) {
        ?>
    </div>
</div>
        <?php
        return;
    }

    admin_collapse_end($collapsible);
}

function admin_render_table_search(string $placeholder = 'Поиск...'): void
{
    ?>
<div class="admin-searchable">
    <div class="admin-search-wrap">
        <div class="input-group admin-search">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" class="form-control admin-search-input"
                   placeholder="<?= htmlspecialchars($placeholder) ?>"
                   autocomplete="off"
                   aria-label="<?= htmlspecialchars($placeholder) ?>">
        </div>
    </div>
    <?php
}

function admin_render_table_search_end(): void
{
    echo '</div>';
}

function admin_nav_groups(): array
{
    require_once __DIR__ . '/../includes/roles.php';

    $roleId = (int)($_SESSION['role_id'] ?? 0);
    $canReports = role_can_access_reports($roleId);
    $canQualifications = role_can_access_qualifications($roleId);

    $groups = [
        [
            'id'    => 'sidebar-mgmt',
            'label' => 'Управление',
            'items' => [
                ['href' => 'users.php',     'icon' => 'bi-people',         'label' => 'Пользователи', 'pages' => ['users.php', 'add_user.php', 'edit_user.php']],
                ['href' => 'employees.php', 'icon' => 'bi-person-badge',   'label' => 'Сотрудники',   'pages' => ['employees.php', 'add_employee.php', 'edit_employee.php']],
                ['href' => 'groups.php',    'icon' => 'bi-grid-3x3-gap',    'label' => 'Группы',       'pages' => ['groups.php', 'add_group.php', 'edit_group.php', 'group_children.php', 'save_group.php', 'update_group.php']],
                ['href' => 'children.php',  'icon' => 'bi-emoji-smile',    'label' => 'Дети',         'pages' => ['children.php', 'add_child.php', 'edit_child.php']],
                ['href' => 'documents.php', 'icon' => 'bi-file-earmark-medical', 'label' => 'Справки детей', 'pages' => ['documents.php']],
                ['href' => 'allergies.php', 'icon' => 'bi-droplet',        'label' => 'Аллергены',    'pages' => ['allergies.php']],
            ],
        ],
        [
            'id'    => 'sidebar-content',
            'label' => 'Контент',
            'items' => [
                ['href' => 'events.php', 'icon' => 'bi-calendar-event', 'label' => 'Мероприятия', 'pages' => ['events.php', 'add_event.php', 'edit_event.php']],
                ['href' => 'clubs.php',   'icon' => 'bi-palette',        'label' => 'Кружки',      'pages' => ['clubs.php', 'add_club.php', 'edit_club.php', 'club_members.php', 'club_applications.php']],
                ['href' => 'club_applications.php', 'icon' => 'bi-inbox', 'label' => 'Заявки в кружки', 'pages' => ['club_applications.php', 'approve_club_application.php', 'reject_club_application.php']],
                ['href' => 'news.php',   'icon' => 'bi-newspaper',      'label' => 'Новости',     'pages' => ['news.php', 'add_news.php', 'edit_news.php']],
                ['href' => 'svedeniya.php', 'icon' => 'bi-journal-text', 'label' => 'Сведения', 'pages' => ['svedeniya.php', 'edit_svedeniya_section.php', 'save_svedeniya_file.php', 'delete_svedeniya_file.php', 'org_documents.php', 'add_org_document.php', 'edit_org_document.php']],
                ['href' => 'staff_materials.php', 'icon' => 'bi-journal-richtext', 'label' => 'Педагогам и сотрудникам', 'pages' => ['staff_materials.php', 'save_staff_material.php', 'delete_staff_material.php']],
                ['href' => 'ktp.php', 'icon' => 'bi-calendar3', 'label' => 'КТП воспитателя', 'pages' => ['ktp.php', 'save_ktp.php', 'delete_ktp.php']],
                ['href' => 'menu.php',   'icon' => 'bi-egg-fried',      'label' => 'Меню',        'pages' => ['menu.php', 'add_menu.php', 'edit_menu.php']],
            ],
        ],
        [
            'id'    => 'sidebar-analytics',
            'label' => 'Аналитика',
            'items' => [
                ['href' => 'reviews.php', 'icon' => 'bi-chat-quote', 'label' => 'Отзывы', 'pages' => ['reviews.php']],
                ['href' => 'qualifications.php', 'icon' => 'bi-mortarboard', 'label' => 'Повышение квалификации', 'pages' => ['qualifications.php', 'employee_qualifications.php']],
                ['href' => 'reports.php', 'icon' => 'bi-bar-chart',  'label' => 'Отчёты', 'pages' => ['reports.php']],
            ],
        ],
    ];

    foreach ($groups as &$group) {
        $group['items'] = array_values(array_filter(
            $group['items'],
            static function (array $item) use ($canReports, $canQualifications): bool {
                if ($item['href'] === 'reports.php' && !$canReports) {
                    return false;
                }
                if ($item['href'] === 'qualifications.php' && !$canQualifications) {
                    return false;
                }
                return true;
            }
        ));
    }
    unset($group);
    $groups = array_values(array_filter($groups, static fn(array $g): bool => $g['items'] !== []));

    return $groups;
}

function admin_nav_group_is_active(array $group): bool
{
    foreach ($group['items'] as $item) {
        if (admin_nav_is_active($item)) {
            return true;
        }
    }
    return false;
}
