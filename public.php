<?php

require_once __DIR__ . '/group_labels.php';
require_once __DIR__ . '/entity_groups.php';

/**
 * Ссылка на меню в ЛК (или на вход с возвратом к меню после авторизации).
 */
function public_menu_lk_href(bool $isLoggedIn, int $roleId): string
{
    if ($isLoggedIn) {
        return match ((int)$roleId) {
            3, 4    => 'admin/menu.php',
            2       => 'employee/dashboard.php#menu',
            default => 'parent/dashboard.php#menu',
        };
    }

    return 'login.php?intent=menu';
}

function public_excerpt(string $text, int $length = 120): string
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

function public_news_list(PDO $pdo, int $limit = 3): array
{
    $limit = max(1, (int)$limit);
    $publicNewsSql = entity_groups_public_news_sql('news');
    $stmt = $pdo->prepare("
        SELECT id, title, content, image_url, publish_date
        FROM news
        WHERE publish_date <= CURDATE()
          AND target_role = 'all'
          AND {$publicNewsSql}
        ORDER BY publish_date DESC, id DESC
        LIMIT $limit
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function public_news_by_id(PDO $pdo, int $id): ?array
{
    $publicNewsSql = entity_groups_public_news_sql('n');
    $stmt = $pdo->prepare("
        SELECT n.id, n.title, n.content, n.image_url, n.publish_date, u.full_name AS author_name
        FROM news n
        LEFT JOIN users u ON n.created_by = u.id
        WHERE n.id = ?
          AND n.publish_date <= CURDATE()
          AND n.target_role = 'all'
          AND {$publicNewsSql}
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function public_event_by_id(PDO $pdo, int $id): ?array
{
    $groupNamesSql = entity_groups_event_names_sql('e');
    $stmt = $pdo->prepare("
        SELECT e.*, {$groupNamesSql} AS group_names
        FROM events e
        WHERE e.id = ?
          AND e.status IN ('active', 'postponed')
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function public_upcoming_events(PDO $pdo, int $limit = 6): array
{
    $limit = max(1, (int)$limit);
    $groupNamesSql = entity_groups_event_names_sql('e');
    $stmt = $pdo->prepare("
        SELECT e.*, {$groupNamesSql} AS group_names
        FROM events e
        WHERE e.status IN ('active', 'postponed')
          AND (
            (e.status = 'active'
             AND e.event_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))
            OR (e.status = 'postponed'
                AND e.postponed_to IS NOT NULL
                AND e.postponed_to >= CURDATE())
          )
        ORDER BY COALESCE(e.postponed_to, e.event_date) ASC, e.event_time ASC
        LIMIT $limit
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function public_clubs(PDO $pdo): array
{
    return $pdo->query("
        SELECT c.*, u.full_name AS teacher_name
        FROM clubs c
        LEFT JOIN employees e ON c.teacher_id = e.id
        LEFT JOIN users u ON e.user_id = u.id
        ORDER BY c.name
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function public_club_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT c.*,
               u.full_name AS teacher_name,
               u.phone AS teacher_phone,
               u.photo_url AS teacher_photo,
               e.position AS teacher_position,
               (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id AND cm.status = 'enrolled') AS member_count
        FROM clubs c
        LEFT JOIN employees e ON c.teacher_id = e.id
        LEFT JOIN users u ON e.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function public_club_icon(int $clubId): string
{
    $icons = public_club_icons();

    return $icons[$clubId % count($icons)];
}

function public_event_display_date(array $event): string
{
    $date = ($event['status'] === 'postponed' && !empty($event['postponed_to']))
        ? $event['postponed_to']
        : $event['event_date'];
    return date('d.m.Y', strtotime($date));
}

function public_event_day_month(array $event): array
{
    $date = ($event['status'] === 'postponed' && !empty($event['postponed_to']))
        ? $event['postponed_to']
        : $event['event_date'];
    $ts = strtotime($date);
    $months = ['', 'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
    return [
        'day'   => date('d', $ts),
        'month' => $months[(int)date('n', $ts)],
    ];
}

function public_club_icons(): array
{
    return ['bi-palette', 'bi-music-note-beamed', 'bi-translate', 'bi-brush', 'bi-balloon', 'bi-star'];
}

function public_groups_sql(): string
{
    return "
        SELECT g.*,
               (SELECT COUNT(*) FROM children c WHERE c.group_id = g.id) AS children_count,
               (SELECT GROUP_CONCAT(u.full_name ORDER BY FIELD(e.position, 'Воспитатель', 'Логопед'), u.full_name SEPARATOR ', ')
                FROM employees e
                JOIN users u ON u.id = e.user_id
                WHERE e.group_id = g.id AND e.position IN ('Воспитатель', 'Логопед')
               ) AS teachers_names,
               (SELECT u.full_name FROM employees e
                JOIN users u ON u.id = e.user_id
                WHERE e.group_id = g.id AND e.position IN ('Воспитатель', 'Логопед')
                ORDER BY FIELD(e.position, 'Воспитатель', 'Логопед'), u.full_name
                LIMIT 1
               ) AS teacher_name,
               (SELECT e.position FROM employees e
                WHERE e.group_id = g.id AND e.position IN ('Воспитатель', 'Логопед')
                ORDER BY FIELD(e.position, 'Воспитатель', 'Логопед'), e.id
                LIMIT 1
               ) AS teacher_position,
               (SELECT u.photo_url FROM employees e
                JOIN users u ON u.id = e.user_id
                WHERE e.group_id = g.id AND e.position IN ('Воспитатель', 'Логопед')
                ORDER BY FIELD(e.position, 'Воспитатель', 'Логопед'), u.full_name
                LIMIT 1
               ) AS teacher_photo,
               (SELECT u.phone FROM employees e
                JOIN users u ON u.id = e.user_id
                WHERE e.group_id = g.id AND e.position IN ('Воспитатель', 'Логопед')
                ORDER BY FIELD(e.position, 'Воспитатель', 'Логопед'), u.full_name
                LIMIT 1
               ) AS teacher_phone
        FROM `groups` g
    ";
}

/** @return list<array> */
function public_group_staff(PDO $pdo, int $groupId): array
{
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.photo_url, u.phone, e.position
        FROM employees e
        JOIN users u ON u.id = e.user_id
        WHERE e.group_id = ? AND e.position IN ('Воспитатель', 'Логопед')
        ORDER BY FIELD(e.position, 'Воспитатель', 'Логопед'), u.full_name
    ");
    $stmt->execute([$groupId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function public_groups(PDO $pdo): array
{
    return $pdo->query(public_groups_sql() . ' ORDER BY g.age_category ASC, g.name ASC')
        ->fetchAll(PDO::FETCH_ASSOC);
}

function public_group_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(public_groups_sql() . ' WHERE g.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function public_group_header_class(array $group): string
{
    if (dou_age_band($group['age_category'] ?? '') === 'speech') {
        return 'bg-group-special';
    }
    $band = dou_age_band($group['age_category'] ?? '');
    if (in_array($band, ['nursery', 'junior'], true)) {
        return 'bg-group-young';
    }
    if ($band === 'middle') {
        return 'bg-group-middle';
    }
    return 'bg-group-senior';
}

function public_group_icon(array $group): string
{
    if (dou_age_band($group['age_category'] ?? '') === 'speech') {
        return 'bi-mic';
    }
    return dou_age_band_meta_by_category($group['age_category'] ?? '')['icon'];
}

function public_age_band(?string $ageCategory): string
{
    return dou_age_band($ageCategory);
}

/** @return list<string> */
function public_age_band_order(): array
{
    return dou_age_band_order();
}

function public_age_band_meta(string $band): array
{
    return dou_age_band_meta($band);
}

/** @return array<string, list<array>> */
function public_groups_by_age_band(array $groups): array
{
    return dou_groups_by_age_band($groups);
}

function public_age_display(?string $ageCategory): string
{
    return dou_age_display($ageCategory);
}

function public_teacher_initials(?string $fullName): string
{
    if (!$fullName) {
        return '?';
    }
    $parts = preg_split('/\s+/u', trim($fullName));
    $initials = '';
    if (!empty($parts[1])) {
        $initials .= mb_substr($parts[1], 0, 1);
    }
    if (!empty($parts[2])) {
        $initials .= mb_substr($parts[2], 0, 1);
    }
    return mb_strtoupper($initials ?: mb_substr($parts[0], 0, 2));
}

function public_staff_list(PDO $pdo): array
{
    require_once __DIR__ . '/public_team_leadership.php';

    try {
        $staff = $pdo->query("
            SELECT e.id, e.position, e.education, e.retraining, e.qualification_upgrades,
                   e.experience_total_years, e.experience_pedagogical_years, e.experience_specialty_note,
                   u.full_name, u.photo_url, g.name AS group_name
            FROM employees e
            JOIN users u ON e.user_id = u.id
            LEFT JOIN `groups` g ON e.group_id = g.id
            WHERE e.show_on_public = 1
            ORDER BY FIELD(e.position, 'Старший воспитатель', 'Воспитатель', 'Логопед'),
                     u.full_name
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $staff = [];
    }

    $director = public_team_director($pdo);
    if ($director) {
        array_unshift($staff, $director);
    }

    return $staff;
}

function public_staff_experience(array $staff): string
{
    $parts = [];
    if (!empty($staff['experience_total_years'])) {
        $parts[] = 'общий — ' . (int)$staff['experience_total_years'] . ' ' . public_plural_years((int)$staff['experience_total_years']);
    }
    if (!empty($staff['experience_pedagogical_years'])) {
        $y = (int)$staff['experience_pedagogical_years'];
        $parts[] = 'педагогический — ' . $y . ' ' . public_plural_years($y);
    }
    if (!empty($staff['experience_specialty_note'])) {
        $parts[] = 'по специальности — ' . $staff['experience_specialty_note'];
    }
    return $parts !== [] ? implode('; ', $parts) : '';
}

function public_plural_years(int $n): string
{
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) {
        return 'лет';
    }
    if ($n1 > 1 && $n1 < 5) {
        return 'года';
    }
    if ($n1 === 1) {
        return 'год';
    }
    return 'лет';
}

function public_staff_photo_url(?string $photoUrl): ?string
{
    if (!$photoUrl) {
        return null;
    }
    return str_starts_with($photoUrl, 'http') ? $photoUrl : $photoUrl;
}

function public_approved_reviews(PDO $pdo, int $limit = 6): array
{
    $limit = max(1, min(20, $limit));

    try {
        $stmt = $pdo->query("
            SELECT r.id, r.rating, r.text, r.created_at, u.full_name AS parent_name
            FROM reviews r
            JOIN users u ON r.parent_id = u.id
            WHERE r.status = 'approved'
            ORDER BY r.created_at DESC
            LIMIT {$limit}
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function public_review_stars(int $rating): string
{
    $rating = max(1, min(5, $rating));

    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
}
