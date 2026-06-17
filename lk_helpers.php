<?php

require_once __DIR__ . '/allergies.php';
require_once __DIR__ . '/group_labels.php';
require_once __DIR__ . '/attendance_helpers.php';

function lk_require_role(int ...$roleIds): void
{
    if (!isset($_SESSION['user_id']) || !in_array((int)$_SESSION['role_id'], $roleIds, true)) {
        header('Location: ../login.php');
        exit;
    }
}

function lk_attendance_badge(?string $status): string
{
    if ($status === 'present') {
        return '<span class="badge bg-soft-green text-dark">Присутствует</span>';
    }
    if ($status === 'absent') {
        return '<span class="badge bg-soft-yellow text-dark">Отсутствует</span>';
    }
    return '<span class="text-muted">Не отмечено</span>';
}

function lk_format_time(?string $time): string
{
    if ($time === null || $time === '') {
        return '—';
    }

    return substr($time, 0, 5);
}

function lk_attendance_times_html(?string $arrival, ?string $departure, bool $inline = true): string
{
    if (($arrival === null || $arrival === '') && ($departure === null || $departure === '')) {
        return '';
    }

    $class = $inline ? 'small text-muted' : 'small text-muted mb-0';
    $arrivalLabel = lk_format_time($arrival);
    $departureLabel = lk_format_time($departure);

    return '<p class="' . $class . '">'
        . '<i class="bi bi-box-arrow-in-right me-1"></i>' . htmlspecialchars($arrivalLabel)
        . ' <span class="mx-1">→</span> '
        . '<i class="bi bi-box-arrow-right me-1"></i>' . htmlspecialchars($departureLabel)
        . '</p>';
}

function lk_review_status_label(string $status): string
{
    return match ($status) {
        'approved' => 'Одобрен',
        'rejected' => 'Отклонён',
        default    => 'На модерации',
    };
}

function lk_review_status_class(string $status): string
{
    return match ($status) {
        'approved' => 'bg-soft-green text-dark',
        'rejected' => 'bg-soft-yellow text-dark',
        default    => 'badge-news',
    };
}

function lk_employee_by_user(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("
        SELECT e.id AS employee_id, e.user_id, e.position, e.group_id,
               u.full_name, g.name AS group_name, g.age_category
        FROM employees e
        JOIN users u ON e.user_id = u.id
        LEFT JOIN `groups` g ON e.group_id = g.id
        WHERE e.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function lk_employee_clubs(PDO $pdo, int $employeeId): array
{
    $stmt = $pdo->prepare("
        SELECT c.*,
               (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id AND cm.status = 'enrolled') AS member_count
        FROM clubs c
        WHERE c.teacher_id = ?
        ORDER BY c.name
    ");
    $stmt->execute([$employeeId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lk_club_members(PDO $pdo, int $clubId): array
{
    $stmt = $pdo->prepare("
        SELECT ch.id, ch.full_name, ch.date_of_birth, ch.has_tnr, ch.parent_id,
               g.name AS group_name, g.id AS group_id,
               u.full_name AS parent_name, u.phone AS parent_phone,
               cm.enrolled_at,
               " . child_allergies_sql('ch') . " AS allergy_names,
               (SELECT a.status FROM attendance a
                WHERE a.club_id = cm.club_id AND a.child_id = ch.id
                  AND a.`date` = CURDATE() LIMIT 1) AS today_status
        FROM club_members cm
        JOIN children ch ON cm.child_id = ch.id
        LEFT JOIN `groups` g ON ch.group_id = g.id
        LEFT JOIN users u ON ch.parent_id = u.id
        WHERE cm.club_id = ? AND cm.status = 'enrolled'
        ORDER BY ch.full_name
    ");
    $stmt->execute([$clubId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lk_group_staff(PDO $pdo, int $groupId): array
{
    if ($groupId <= 0) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT e.id AS employee_id, e.position,
               u.full_name, u.phone, u.photo_url
        FROM employees e
        JOIN users u ON e.user_id = u.id
        WHERE e.group_id = ?
          AND e.position IN ('Воспитатель', 'Логопед')
        ORDER BY FIELD(e.position, 'Воспитатель', 'Логопед'), u.full_name
    ");
    $stmt->execute([$groupId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lk_teacher_group_children(PDO $pdo, int $groupId): array
{
    $stmt = $pdo->prepare("
        SELECT c.id, c.full_name, c.date_of_birth, c.has_tnr, c.parent_id, c.group_id,
               " . child_allergies_sql('c') . " AS allergy_names,
               p.full_name AS parent_name, p.phone AS parent_phone,
               (SELECT a.status FROM attendance a
                WHERE a.child_id = c.id AND a.club_id = 0 AND a.`date` = CURDATE() LIMIT 1) AS today_status,
               (SELECT a.arrival_time FROM attendance a
                WHERE a.child_id = c.id AND a.club_id = 0 AND a.`date` = CURDATE() LIMIT 1) AS today_arrival,
               (SELECT a.departure_time FROM attendance a
                WHERE a.child_id = c.id AND a.club_id = 0 AND a.`date` = CURDATE() LIMIT 1) AS today_departure
        FROM children c
        LEFT JOIN users p ON c.parent_id = p.id
        WHERE c.group_id = ?
        ORDER BY c.full_name
    ");
    $stmt->execute([$groupId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lk_parent_children(PDO $pdo, int $parentId): array
{
    $stmt = $pdo->prepare("
        SELECT c.id, c.full_name, c.date_of_birth, c.has_tnr, c.group_id,
               g.name AS group_name, g.age_category,
               " . child_allergies_sql('c') . " AS allergy_names,
               tu.full_name AS teacher_name, tu.phone AS teacher_phone,
               (SELECT a.status FROM attendance a
                WHERE a.child_id = c.id AND a.club_id = 0 AND a.`date` = CURDATE() LIMIT 1) AS today_status,
               (SELECT a.arrival_time FROM attendance a
                WHERE a.child_id = c.id AND a.club_id = 0 AND a.`date` = CURDATE() LIMIT 1) AS today_arrival,
               (SELECT a.departure_time FROM attendance a
                WHERE a.child_id = c.id AND a.club_id = 0 AND a.`date` = CURDATE() LIMIT 1) AS today_departure
        FROM children c
        LEFT JOIN `groups` g ON c.group_id = g.id
        LEFT JOIN employees e ON e.group_id = g.id AND e.position IN ('Воспитатель', 'Логопед')
        LEFT JOIN users tu ON e.user_id = tu.id
        WHERE c.parent_id = ?
        ORDER BY c.full_name
    ");
    $stmt->execute([$parentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lk_parent_group_ids(array $children): array
{
    $ids = [];
    foreach ($children as $child) {
        if (!empty($child['group_id'])) {
            $ids[(int)$child['group_id']] = (int)$child['group_id'];
        }
    }
    return array_values($ids);
}

function lk_news_for_audience(PDO $pdo, array $targetRoles, array $groupIds, int $limit = 10, bool $onlyAssignedGroups = false): array
{
    require_once __DIR__ . '/entity_groups.php';

    $limit = max(1, (int)$limit);

    if ($onlyAssignedGroups && $groupIds === []) {
        return [];
    }

    $rolePlaceholders = implode(',', array_fill(0, count($targetRoles), '?'));
    [$groupSql, $groupParams] = entity_groups_news_visibility_sql($groupIds, $onlyAssignedGroups);
    $params = array_merge($targetRoles, $groupParams);
    $groupNamesSql = entity_groups_news_names_sql('n');

    $stmt = $pdo->prepare("
        SELECT n.id, n.title, n.content, n.image_url, n.publish_date, n.target_role,
               {$groupNamesSql} AS group_names
        FROM news n
        WHERE n.publish_date <= CURDATE()
          AND n.target_role IN ($rolePlaceholders)
          AND {$groupSql}
        ORDER BY n.publish_date DESC, n.id DESC
        LIMIT $limit
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lk_events_for_groups(PDO $pdo, array $groupIds, int $limit = 8, bool $onlyAssignedGroups = false): array
{
    require_once __DIR__ . '/entity_groups.php';

    $limit = max(1, (int)$limit);

    if ($onlyAssignedGroups && $groupIds === []) {
        return [];
    }

    [$groupSql, $params] = entity_groups_event_visibility_sql($groupIds, $onlyAssignedGroups);
    $groupNamesSql = entity_groups_event_names_sql('e');

    $stmt = $pdo->prepare("
        SELECT e.*, {$groupNamesSql} AS group_names
        FROM events e
        WHERE e.status IN ('active', 'postponed')
          AND (
            (e.status = 'active' AND e.event_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))
            OR (e.status = 'postponed' AND e.postponed_to IS NOT NULL AND e.postponed_to >= CURDATE())
          )
          AND {$groupSql}
        ORDER BY COALESCE(e.postponed_to, e.event_date) ASC, e.event_time ASC
        LIMIT $limit
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lk_menu_for_range(PDO $pdo, string $from, string $to, array $groupIds, bool $onlyAssignedGroups = false): array
{
    require_once __DIR__ . '/menu_weekly.php';

    return menu_weekly_for_date_range($pdo, $from, $to, $groupIds, $onlyAssignedGroups);
}

function lk_menu_group_label(array $row): string
{
    if (empty($row['group_name'])) {
        return '—';
    }
    $label = $row['group_name'];
    if (!empty($row['group_age'])) {
        $label .= ' · ' . dou_age_display($row['group_age']);
    }
    return $label;
}

/** Пн–пт в диапазоне недели: Y-m-d => «Понедельник, 26.05» */
function lk_menu_weekday_date_options(string $from, string $to): array
{
    require_once __DIR__ . '/menu_weekly.php';

    $out = [];
    $start = new DateTime($from);
    $end = new DateTime($to);
    for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
        $wd = (int) $d->format('N');
        if ($wd > 5) {
            continue;
        }
        $key = $d->format('Y-m-d');
        $out[$key] = menu_weekly_weekday_label($wd) . ', ' . $d->format('d.m');
    }

    return $out;
}

/** Дата по умолчанию: сегодня (если будний и есть меню), иначе первый день недели с блюдами */
function lk_menu_default_date(array $groupMenu, array $dateOptions): string
{
    $datesInMenu = [];
    foreach ($groupMenu as $row) {
        if (!empty($row['date'])) {
            $datesInMenu[$row['date']] = true;
        }
    }

    $today = date('Y-m-d');
    if (isset($datesInMenu[$today])) {
        return $today;
    }

    foreach (array_keys($dateOptions) as $d) {
        if (isset($datesInMenu[$d])) {
            return $d;
        }
    }

    return array_key_first($dateOptions) ?: $today;
}

function lk_parent_clubs(PDO $pdo, int $parentId): array
{
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.schedule, c.age_category,
               ch.full_name AS child_name,
               tu.full_name AS teacher_name,
               tu.phone AS teacher_phone
        FROM club_members cm
        JOIN clubs c ON cm.club_id = c.id
        JOIN children ch ON cm.child_id = ch.id
        LEFT JOIN employees e ON c.teacher_id = e.id
        LEFT JOIN users tu ON e.user_id = tu.id
        WHERE ch.parent_id = ? AND cm.status = 'enrolled'
        ORDER BY c.name, ch.full_name
    ");
    $stmt->execute([$parentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lk_parent_reviews(PDO $pdo, int $parentId): array
{
    $stmt = $pdo->prepare('
        SELECT id, text, rating, status, created_at
        FROM reviews
        WHERE parent_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ');
    $stmt->execute([$parentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lk_week_range(): array
{
    $today = new DateTime('today');
    $day = (int)$today->format('N');
    $monday = (clone $today)->modify('-' . ($day - 1) . ' days');
    $sunday = (clone $monday)->modify('+6 days');
    return [$monday->format('Y-m-d'), $sunday->format('Y-m-d')];
}

function lk_excerpt(string $text, int $length = 140): string
{
    $plain = trim(strip_tags($text));
    if ($plain === '') {
        return '';
    }
    if (mb_strlen($plain) <= $length) {
        return $plain;
    }
    return mb_substr($plain, 0, $length) . '…';
}

function lk_group_info(PDO $pdo, int $groupId): ?array
{
    $stmt = $pdo->prepare('
        SELECT g.*,
               (SELECT COUNT(*) FROM children c WHERE c.group_id = g.id) AS children_count
        FROM `groups` g
        WHERE g.id = ?
    ');
    $stmt->execute([$groupId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function lk_group_attendance_summary(PDO $pdo, int $groupId, int $days = 5): array
{
    $days = max(1, min(14, $days));
    $stmt = $pdo->prepare("
        SELECT a.`date`,
               SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count,
               SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_count
        FROM attendance a
        JOIN children c ON c.id = a.child_id
        WHERE c.group_id = ?
          AND a.club_id = 0
          AND a.`date` >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
          AND a.`date` <= CURDATE()
        GROUP BY a.`date`
        ORDER BY a.`date` DESC
    ");
    $stmt->execute([$groupId, $days - 1]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lk_children_attendance_map(PDO $pdo, array $childIds, int $days = 5): array
{
    if ($childIds === []) {
        return [];
    }

    $days = max(1, min(14, $days));
    $placeholders = implode(',', array_fill(0, count($childIds), '?'));
    $params = array_merge($childIds, [$days - 1]);

    $stmt = $pdo->prepare("
        SELECT child_id, `date`, status, arrival_time, departure_time
        FROM attendance
        WHERE child_id IN ($placeholders)
          AND club_id = 0
          AND `date` >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
          AND `date` <= CURDATE()
        ORDER BY child_id, `date` DESC
    ");
    $stmt->execute($params);

    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[(int)$row['child_id']][] = $row;
    }
    return $map;
}

/** @return array{has_group: bool, has_clubs: bool, club_teacher_only: bool} */
function lk_employee_nav_context(PDO $pdo, int $userId): array
{
    $employee = lk_employee_by_user($pdo, $userId);
    if (!$employee) {
        return ['has_group' => false, 'has_clubs' => false, 'club_teacher_only' => false];
    }

    $clubs = lk_employee_clubs($pdo, (int)$employee['employee_id']);
    $hasGroup = !empty($employee['group_id']);
    $hasClubs = $clubs !== [];

    return [
        'has_group' => $hasGroup,
        'has_clubs' => $hasClubs,
        'club_teacher_only' => $hasClubs && !$hasGroup,
    ];
}

/** @return list<array{id: string, href: string, icon: string, label: string}> */
function lk_employee_nav_items(PDO $pdo, int $userId): array
{
    $ctx = lk_employee_nav_context($pdo, $userId);

    $items = [
        ['id' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'house-heart', 'label' => 'Главная'],
    ];

    if ($ctx['has_group']) {
        $items[] = ['id' => 'group', 'href' => 'dashboard.php#employee-group-info', 'icon' => 'collection', 'label' => 'Моя группа'];
        $items[] = ['id' => 'attendance', 'href' => 'dashboard.php#employee-teacher', 'icon' => 'clipboard-check', 'label' => 'Посещаемость'];
    } elseif ($ctx['has_clubs']) {
        $items[] = ['id' => 'attendance', 'href' => 'dashboard.php#employee-clubs', 'icon' => 'clipboard-check', 'label' => 'Посещаемость'];
    }

    $items[] = ['id' => 'materials', 'href' => 'materials.php', 'icon' => 'journal-richtext', 'label' => 'Материалы'];

    if ($ctx['has_group']) {
        $items[] = ['id' => 'menu', 'href' => 'dashboard.php#menu', 'icon' => 'cup-hot', 'label' => 'Меню'];
    }

    $items[] = ['id' => 'chat', 'href' => '../chat/index.php', 'icon' => 'chat-dots', 'label' => 'Сообщения'];
    $items[] = ['id' => 'password', 'href' => '../change_password.php', 'icon' => 'key', 'label' => 'Пароль'];

    return $items;
}

function lk_parent_chat_url(string $convType, int $parentUserId, int $contextId, string $base = '..'): ?string
{
    if ($contextId <= 0) {
        return null;
    }
    if (!in_array($convType, ['group_broadcast', 'club_broadcast'], true) && $parentUserId <= 0) {
        return null;
    }

    require_once __DIR__ . '/chat_helpers.php';

    return chat_url(chat_conv_parts($convType, $parentUserId, $contextId), $base);
}

function lk_broadcast_chat_url(string $convType, int $contextId, string $base = '..'): ?string
{
    return lk_parent_chat_url($convType, 0, $contextId, $base);
}

function lk_render_broadcast_chat_link(
    string $convType,
    int $contextId,
    string $base = '..',
    string $label = 'Общий чат'
): void {
    $url = lk_broadcast_chat_url($convType, $contextId, $base);
    if ($url === null) {
        return;
    }
    ?>
    <a href="<?= htmlspecialchars($url) ?>" class="btn btn-sm btn-outline-secondary lk-broadcast-chat-btn">
        <i class="bi bi-people-fill" aria-hidden="true"></i>
        <span class="ms-1"><?= htmlspecialchars($label) ?></span>
    </a>
    <?php
}

function lk_phone_tel_href(?string $phone): ?string
{
    $digits = preg_replace('/\D+/', '', (string)$phone);
    if ($digits === '') {
        return null;
    }
    if (strlen($digits) === 10) {
        $digits = '7' . $digits;
    } elseif (strlen($digits) === 11 && ($digits[0] === '8' || $digits[0] === '7')) {
        if ($digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }
    }

    return 'tel:+' . $digits;
}

/**
 * Кнопка «Связаться»: чат и/или звонок.
 */
function lk_render_contact_menu(
    ?string $chatUrl,
    ?string $phone,
    bool $compact = false,
    string $chatLabel = 'Написать',
    string $phoneLabel = 'Позвонить'
): void {
    $tel = lk_phone_tel_href($phone);
    $hasChat = $chatUrl !== null && $chatUrl !== '';
    $hasPhone = $tel !== null;

    if (!$hasChat && !$hasPhone) {
        return;
    }

    if ($hasChat && !$hasPhone) {
        ?>
        <a href="<?= htmlspecialchars($chatUrl) ?>" class="btn btn-sm btn-outline-primary lk-contact-btn"
           title="<?= htmlspecialchars($chatLabel) ?>">
            <i class="bi bi-chat-dots" aria-hidden="true"></i><?php if (!$compact): ?>
            <span class="d-none d-xl-inline ms-1"><?= htmlspecialchars($chatLabel) ?></span><?php endif; ?>
        </a>
        <?php
        return;
    }

    if (!$hasChat && $hasPhone) {
        ?>
        <a href="<?= htmlspecialchars($tel) ?>" class="btn btn-sm btn-outline-primary lk-contact-btn"
           title="<?= htmlspecialchars($phoneLabel) ?>">
            <i class="bi bi-telephone" aria-hidden="true"></i><?php if (!$compact): ?>
            <span class="d-none d-xl-inline ms-1"><?= htmlspecialchars($phoneLabel) ?></span><?php endif; ?>
        </a>
        <?php
        return;
    }
    ?>
    <div class="btn-group lk-contact-menu">
        <button type="button"
                class="btn btn-sm btn-outline-primary dropdown-toggle lk-contact-btn"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                title="Связаться">
            <i class="bi bi-person-lines-fill" aria-hidden="true"></i><?php if (!$compact): ?>
            <span class="d-none d-xl-inline ms-1">Связаться</span><?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li>
                <a class="dropdown-item" href="<?= htmlspecialchars($chatUrl) ?>">
                    <i class="bi bi-chat-dots me-2 text-primary"></i><?= htmlspecialchars($chatLabel) ?>
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="<?= htmlspecialchars($tel) ?>">
                    <i class="bi bi-telephone me-2 text-primary"></i>
                    <?= htmlspecialchars($phoneLabel) ?>
                    <span class="text-muted small d-block ps-4"><?= htmlspecialchars((string)$phone) ?></span>
                </a>
            </li>
        </ul>
    </div>
    <?php
}

function lk_render_parent_contact_menu(
    string $convType,
    int $parentUserId,
    int $contextId,
    ?string $phone,
    string $base = '..',
    bool $compact = false
): void {
    $chatUrl = lk_parent_chat_url($convType, $parentUserId, $contextId, $base);
    lk_render_contact_menu($chatUrl, $phone, $compact, 'Написать', 'Позвонить');
}

/** @deprecated Используйте lk_render_parent_contact_menu */
function lk_render_parent_chat_link(
    string $convType,
    int $parentUserId,
    int $contextId,
    string $base = '..',
    bool $iconOnly = false
): void {
    lk_render_parent_contact_menu($convType, $parentUserId, $contextId, null, $base, $iconOnly);
}
