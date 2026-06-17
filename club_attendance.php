<?php

require_once __DIR__ . '/attendance_helpers.php';

/** Проверка: сотрудник — руководитель кружка. */
function club_attendance_teacher_owns_club(PDO $pdo, int $employeeId, int $clubId): bool
{
    if ($employeeId <= 0 || $clubId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT id FROM clubs WHERE id = ? AND teacher_id = ? LIMIT 1');
    $stmt->execute([$clubId, $employeeId]);

    return (bool)$stmt->fetchColumn();
}

/** Ребёнок записан в кружок (enrolled). */
function club_attendance_child_enrolled(PDO $pdo, int $clubId, int $childId): bool
{
    $stmt = $pdo->prepare("
        SELECT 1 FROM club_members
        WHERE club_id = ? AND child_id = ? AND status = 'enrolled'
        LIMIT 1
    ");
    $stmt->execute([$clubId, $childId]);

    return (bool)$stmt->fetchColumn();
}

/** Сохранить отметку посещаемости кружка на сегодня. */
function club_attendance_mark(PDO $pdo, int $employeeId, int $clubId, int $childId, string $status): void
{
    if (!in_array($status, ['present', 'absent'], true)) {
        throw new InvalidArgumentException('Некорректный статус');
    }
    if (!club_attendance_teacher_owns_club($pdo, $employeeId, $clubId)) {
        throw new RuntimeException('Кружок не назначен вам');
    }
    if (!club_attendance_child_enrolled($pdo, $clubId, $childId)) {
        throw new RuntimeException('Ребёнок не записан в кружок');
    }

    $stmt = $pdo->prepare('
        SELECT id FROM attendance
        WHERE club_id = ? AND child_id = ? AND `date` = CURDATE()
        LIMIT 1
    ');
    $stmt->execute([$clubId, $childId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $pdo->prepare('
            UPDATE attendance SET status = ?, created_by = ? WHERE id = ?
        ')->execute([$status, $employeeId, (int)$existing['id']]);
    } else {
        $pdo->prepare('
            INSERT INTO attendance (child_id, `date`, status, club_id, created_by)
            VALUES (?, CURDATE(), ?, ?, ?)
        ')->execute([$childId, $status, $clubId, $employeeId]);
    }
}

/** @return list<array<string, mixed>> */
function lk_club_attendance_summary(PDO $pdo, int $clubId, int $days = 5): array
{
    if ($clubId <= 0) {
        return [];
    }

    $days = max(1, min(14, $days));
    $stmt = $pdo->prepare("
        SELECT a.`date`,
               SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count,
               SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_count
        FROM attendance a
        WHERE a.club_id = ?
          AND a.`date` >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
          AND a.`date` <= CURDATE()
        GROUP BY a.`date`
        ORDER BY a.`date` DESC
    ");
    $stmt->execute([$clubId, $days - 1]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Кружки с участниками и посещаемостью за период (для отчёта).
 *
 * @return list<array{club: array, members: list<array>}>
 */
function admin_report_clubs_attendance_grouped(PDO $pdo, string $monthStart, string $monthEnd): array
{
    $clubs = $pdo->query("
        SELECT cl.id, cl.name, cl.schedule, cl.max_participants, cl.age_category,
               tu.full_name AS teacher_name
        FROM clubs cl
        LEFT JOIN employees e ON cl.teacher_id = e.id
        LEFT JOIN users tu ON e.user_id = tu.id
        ORDER BY cl.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT c.id AS child_id, c.full_name AS child_name, g.name AS group_name,
               pu.full_name AS parent_name, cm.enrolled_at,
               SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,
               SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
               COUNT(a.id) AS marked_days
        FROM club_members cm
        JOIN children c ON cm.child_id = c.id
        LEFT JOIN `groups` g ON c.group_id = g.id
        LEFT JOIN users pu ON c.parent_id = pu.id
        LEFT JOIN attendance a ON a.club_id = cm.club_id
            AND a.child_id = cm.child_id
            AND a.`date` BETWEEN ? AND ?
        WHERE cm.club_id = ? AND cm.status = 'enrolled'
        GROUP BY c.id, c.full_name, g.name, pu.full_name, cm.enrolled_at
        ORDER BY c.full_name
    ");

    $out = [];
    foreach ($clubs as $club) {
        $stmt->execute([$monthStart, $monthEnd, (int)$club['id']]);
        $out[] = [
            'club' => $club,
            'members' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    return $out;
}
