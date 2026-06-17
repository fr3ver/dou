<?php

require_once __DIR__ . '/../admin/_club_helpers.php';
require_once __DIR__ . '/group_labels.php';

function club_member_status_label(string $status): string
{
    return match ($status) {
        'enrolled' => 'Записан',
        'rejected' => 'Отклонена',
        default    => 'На рассмотрении',
    };
}

function club_member_status_class(string $status): string
{
    return match ($status) {
        'enrolled' => 'bg-soft-green text-dark',
        'rejected' => 'bg-secondary',
        default    => 'bg-warning text-dark',
    };
}

function club_enrollment_child_belongs_to_parent(PDO $pdo, int $childId, int $parentId): bool
{
    $stmt = $pdo->prepare('SELECT id FROM children WHERE id = ? AND parent_id = ?');
    $stmt->execute([$childId, $parentId]);

    return (bool) $stmt->fetch();
}

function club_enrollment_row(PDO $pdo, int $clubId, int $childId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM club_members WHERE club_id = ? AND child_id = ?');
    $stmt->execute([$clubId, $childId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/** Подпись возраста ребёнка для заявок в кружки. */
function club_child_age_label(string $dateOfBirth, ?DateTimeInterface $reference = null): string
{
    $years = dou_child_age_years($dateOfBirth, $reference);
    $whole = (int) floor($years);
    $months = (int) round(($years - $whole) * 12);
    if ($months >= 12) {
        $whole++;
        $months = 0;
    }

    $yearWord = match (true) {
        $whole % 10 === 1 && $whole % 100 !== 11 => 'год',
        $whole % 10 >= 2 && $whole % 10 <= 4 && ($whole % 100 < 10 || $whole % 100 >= 20) => 'года',
        default => 'лет',
    };

    if ($months > 0) {
        return $whole . ' ' . $yearWord . ' ' . $months . ' мес.';
    }

    return $whole . ' ' . $yearWord;
}

/**
 * @return array{fits: bool, age_label: string, club_age: string, message: ?string}|null
 */
function club_enrollment_age_warning(?string $dateOfBirth, ?string $clubAgeCategory): ?array
{
    if ($dateOfBirth === null || trim($dateOfBirth) === '') {
        return null;
    }

    $clubAge = trim($clubAgeCategory ?? '');
    $ageLabel = club_child_age_label($dateOfBirth);
    $fits = $clubAge === '' || dou_child_age_fits_club($dateOfBirth, $clubAgeCategory);

    return [
        'fits'       => $fits,
        'age_label'  => $ageLabel,
        'club_age'   => $clubAge,
        'message'    => $fits ? null : 'Ребёнку ' . $ageLabel . ($clubAge !== '' ? ', кружок — ' . $clubAge . '.' : '.') . ' Возраст может не подходить.',
    ];
}

/** @return string|null */
function club_enrollment_validate_parent_apply(PDO $pdo, int $clubId, int $childId, int $parentId): ?string
{
    if (!club_enrollment_child_belongs_to_parent($pdo, $childId, $parentId)) {
        return 'Ребёнок не найден';
    }

    $club = admin_club_find($pdo, $clubId);
    if (!$club) {
        return 'Кружок не найден';
    }

    $row = club_enrollment_row($pdo, $clubId, $childId);
    if ($row) {
        if ($row['status'] === 'enrolled') {
            return 'Ребёнок уже записан в этот кружок';
        }
        if ($row['status'] === 'pending') {
            return 'Заявка уже отправлена и ожидает рассмотрения';
        }
    }

    $count = admin_club_member_count($pdo, $clubId);
    $max = $club['max_participants'] !== null ? (int) $club['max_participants'] : null;
    if ($max !== null && $count >= $max) {
        return 'В кружке нет свободных мест';
    }

    return null;
}

/** @return string|null */
function club_enrollment_validate_apply(PDO $pdo, int $clubId, int $childId, int $parentId): ?string
{
    return club_enrollment_validate_parent_apply($pdo, $clubId, $childId, $parentId);
}

function club_enrollment_apply(PDO $pdo, int $clubId, int $childId, int $parentId): ?string
{
    $error = club_enrollment_validate_parent_apply($pdo, $clubId, $childId, $parentId);
    if ($error !== null) {
        return $error;
    }

    $row = club_enrollment_row($pdo, $clubId, $childId);
    if ($row && $row['status'] === 'rejected') {
        $pdo->prepare("
            UPDATE club_members
            SET status = 'pending', created_at = NOW(), enrolled_at = NULL,
                moderated_at = NULL, moderated_by = NULL
            WHERE club_id = ? AND child_id = ?
        ")->execute([$clubId, $childId]);
    } else {
        $pdo->prepare("
            INSERT INTO club_members (club_id, child_id, status, created_at)
            VALUES (?, ?, 'pending', NOW())
        ")->execute([$clubId, $childId]);
    }

    return null;
}

function club_enrollment_approve(PDO $pdo, int $clubId, int $childId, int $moderatorUserId): ?string
{
    $row = club_enrollment_row($pdo, $clubId, $childId);
    if (!$row || $row['status'] !== 'pending') {
        return 'Заявка не найдена или уже обработана';
    }

    $club = admin_club_find($pdo, $clubId);
    if (!$club) {
        return 'Кружок не найден';
    }

    $count = admin_club_member_count($pdo, $clubId);
    $max = $club['max_participants'] !== null ? (int) $club['max_participants'] : null;
    if ($max !== null && $count >= $max) {
        return 'Достигнут лимит участников (' . $max . ')';
    }

    $stmt = $pdo->prepare("
        UPDATE club_members
        SET status = 'enrolled', enrolled_at = CURDATE(),
            moderated_at = NOW(), moderated_by = ?
        WHERE club_id = ? AND child_id = ? AND status = 'pending'
    ");
    $stmt->execute([$moderatorUserId, $clubId, $childId]);

    if ($stmt->rowCount() === 0) {
        return 'Заявка не найдена или уже обработана';
    }

    return null;
}

function club_enrollment_reject(PDO $pdo, int $clubId, int $childId, int $moderatorUserId): ?string
{
    $row = club_enrollment_row($pdo, $clubId, $childId);
    if (!$row || $row['status'] !== 'pending') {
        return 'Заявка не найдена или уже обработана';
    }

    $pdo->prepare("
        UPDATE club_members
        SET status = 'rejected', moderated_at = NOW(), moderated_by = ?
        WHERE club_id = ? AND child_id = ? AND status = 'pending'
    ")->execute([$moderatorUserId, $clubId, $childId]);

    return null;
}

function lk_parent_club_applications(PDO $pdo, int $parentId): array
{
    $stmt = $pdo->prepare("
        SELECT cm.club_id, cm.child_id, cm.status, cm.created_at,
               c.name AS club_name, c.schedule,
               ch.full_name AS child_name
        FROM club_members cm
        JOIN clubs c ON cm.club_id = c.id
        JOIN children ch ON cm.child_id = ch.id
        WHERE ch.parent_id = ?
          AND cm.status IN ('pending', 'rejected')
        ORDER BY FIELD(cm.status, 'pending', 'rejected'), cm.created_at DESC
    ");
    $stmt->execute([$parentId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lk_clubs_open_for_parent(PDO $pdo, int $parentId, array $children): array
{
    if ($children === []) {
        return [];
    }

    $clubs = $pdo->query("
        SELECT c.*, u.full_name AS teacher_name,
               (SELECT COUNT(*) FROM club_members cm
                WHERE cm.club_id = c.id AND cm.status = 'enrolled') AS member_count
        FROM clubs c
        LEFT JOIN employees e ON c.teacher_id = e.id
        LEFT JOIN users u ON e.user_id = u.id
        ORDER BY c.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $open = [];
    foreach ($clubs as $club) {
        $clubId = (int) $club['id'];
        $max = $club['max_participants'] !== null ? (int) $club['max_participants'] : null;
        $count = (int) $club['member_count'];
        if ($max !== null && $count >= $max) {
            continue;
        }

        $applicableChildren = [];
        foreach ($children as $child) {
            $childId = (int) $child['id'];
            if (club_enrollment_validate_parent_apply($pdo, $clubId, $childId, $parentId) === null) {
                $applicableChildren[] = $child;
            }
        }

        if ($applicableChildren !== []) {
            $club['eligible_children'] = $applicableChildren;
            $open[] = $club;
        }
    }

    return $open;
}

function admin_club_applications_list(PDO $pdo, string $filter = 'pending'): array
{
    $sql = "
        SELECT cm.club_id, cm.child_id, cm.status, cm.created_at,
               cm.moderated_at, cm.enrolled_at,
               c.name AS club_name, c.schedule, c.max_participants, c.age_category,
               ch.full_name AS child_name, ch.date_of_birth, g.name AS group_name,
               pu.full_name AS parent_name, pu.phone AS parent_phone,
               m.full_name AS moderator_name,
               (SELECT COUNT(*) FROM club_members cm2
                WHERE cm2.club_id = c.id AND cm2.status = 'enrolled') AS member_count
        FROM club_members cm
        JOIN clubs c ON cm.club_id = c.id
        JOIN children ch ON cm.child_id = ch.id
        LEFT JOIN `groups` g ON ch.group_id = g.id
        JOIN users pu ON ch.parent_id = pu.id
        LEFT JOIN users m ON cm.moderated_by = m.id
        WHERE cm.status IN ('pending', 'enrolled', 'rejected')
    ";

    if ($filter === 'pending') {
        $sql .= " AND cm.status = 'pending'";
    } elseif ($filter === 'approved') {
        $sql .= " AND cm.status = 'enrolled' AND cm.moderated_at IS NOT NULL";
    } elseif ($filter === 'rejected') {
        $sql .= " AND cm.status = 'rejected'";
    }

    $sql .= ' ORDER BY cm.created_at DESC';

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function admin_club_applications_pending_count(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM club_members WHERE status = 'pending'")->fetchColumn();
}

/** Заявки на рассмотрении, где возраст ребёнка может не подходить кружку. */
function admin_club_applications_age_mismatch_pending(PDO $pdo): array
{
    $mismatch = [];
    foreach (admin_club_applications_list($pdo, 'pending') as $app) {
        $warning = club_enrollment_age_warning($app['date_of_birth'] ?? null, $app['age_category'] ?? null);
        if ($warning && !$warning['fits']) {
            $app['age_warning'] = $warning;
            $mismatch[] = $app;
        }
    }

    return $mismatch;
}

// Совместимость со старыми именами в шаблонах
function club_application_status_label(string $status): string
{
    return club_member_status_label($status === 'approved' ? 'enrolled' : $status);
}

function club_application_status_class(string $status): string
{
    return club_member_status_class($status === 'approved' ? 'enrolled' : $status);
}
