<?php

require_once __DIR__ . '/../includes/group_labels.php';

function admin_club_teachers(PDO $pdo): array

{

    return $pdo->query("

        SELECT e.id, u.full_name, e.position

        FROM employees e

        JOIN users u ON e.user_id = u.id

        ORDER BY u.full_name

    ")->fetchAll(PDO::FETCH_ASSOC);

}



function admin_club_collect_post(): array

{

    $teacher_id = (int)($_POST['teacher_id'] ?? 0);

    $max = trim($_POST['max_participants'] ?? '');



    return [

        'name'             => trim($_POST['name'] ?? ''),

        'description'          => trim($_POST['description'] ?? ''),

        'activities_features'  => trim($_POST['activities_features'] ?? ''),

        'education_program'    => trim($_POST['education_program'] ?? ''),

        'age_category'         => trim($_POST['age_category'] ?? ''),

        'schedule'         => trim($_POST['schedule'] ?? ''),

        'max_participants' => $max !== '' ? (int)$max : null,

        'teacher_id'       => $teacher_id > 0 ? $teacher_id : null,

    ];

}



function admin_club_validate(PDO $pdo, array $data, ?int $excludeId = null): ?string

{

    if ($data['name'] === '') {

        return 'Укажите название кружка';

    }

    if (mb_strlen($data['name']) > 150) {

        return 'Название слишком длинное (макс. 150 символов)';

    }

    if ($data['age_category'] !== '' && mb_strlen($data['age_category']) > 50) {

        return 'Возрастная категория слишком длинная (макс. 50 символов)';

    }

    if ($data['schedule'] !== '' && mb_strlen($data['schedule']) > 255) {

        return 'Расписание слишком длинное (макс. 255 символов)';

    }

    if ($data['max_participants'] !== null && $data['max_participants'] < 1) {

        return 'Укажите корректное число участников';

    }

    if ($data['teacher_id'] !== null) {

        $stmt = $pdo->prepare('SELECT id FROM employees WHERE id = ?');

        $stmt->execute([$data['teacher_id']]);

        if (!$stmt->fetch()) {

            return 'Выбранный руководитель не найден';

        }

    }

    return null;

}



function admin_club_find(PDO $pdo, int $clubId): ?array

{

    if ($clubId <= 0) {

        return null;

    }

    $stmt = $pdo->prepare("

        SELECT c.*, u.full_name AS teacher_name

        FROM clubs c

        LEFT JOIN employees e ON c.teacher_id = e.id

        LEFT JOIN users u ON e.user_id = u.id

        WHERE c.id = ?

    ");

    $stmt->execute([$clubId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);



    return $row ?: null;

}



function admin_club_member_count(PDO $pdo, int $clubId): int

{

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE club_id = ? AND status = 'enrolled'");

    $stmt->execute([$clubId]);



    return (int) $stmt->fetchColumn();

}



/** @return list<array> */

function admin_club_members(PDO $pdo, int $clubId): array

{

    require_once __DIR__ . '/../includes/lk_helpers.php';



    return lk_club_members($pdo, $clubId);

}



/** Дети, ещё не записанные в этот кружок. */

function admin_club_children_available(PDO $pdo, int $clubId): array

{

    $stmt = $pdo->prepare("

        SELECT c.id, c.full_name, g.name AS group_name

        FROM children c

        JOIN `groups` g ON c.group_id = g.id

        WHERE c.id NOT IN (
            SELECT child_id FROM club_members
            WHERE club_id = ? AND status IN ('enrolled', 'pending')
        )

        ORDER BY g.name, c.full_name

    ");

    $stmt->execute([$clubId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);

}



function admin_club_add_member(PDO $pdo, int $clubId, int $childId): ?string

{

    $club = admin_club_find($pdo, $clubId);

    if (!$club) {

        return 'Кружок не найден';

    }



    $stmt = $pdo->prepare('SELECT id FROM children WHERE id = ?');

    $stmt->execute([$childId]);

    if (!$stmt->fetch()) {

        return 'Ребёнок не найден';

    }



    $check = $pdo->prepare('SELECT status FROM club_members WHERE club_id = ? AND child_id = ?');

    $check->execute([$clubId, $childId]);

    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {

        if ($existing['status'] === 'enrolled') {

            return 'Ребёнок уже записан в этот кружок';

        }

        if ($existing['status'] === 'pending') {

            return 'Есть заявка на рассмотрении — одобрите её в разделе «Заявки в кружки»';

        }

    }



    $count = admin_club_member_count($pdo, $clubId);

    $max = $club['max_participants'] !== null ? (int) $club['max_participants'] : null;

    if ($max !== null && $count >= $max) {

        return 'Достигнут лимит участников (' . $max . ')';

    }



    if ($existing && $existing['status'] === 'rejected') {

        $pdo->prepare("
            UPDATE club_members
            SET status = 'enrolled', enrolled_at = CURDATE(), created_at = NOW(),
                moderated_at = NULL, moderated_by = NULL
            WHERE club_id = ? AND child_id = ?
        ")->execute([$clubId, $childId]);

    } else {

        $pdo->prepare("
            INSERT INTO club_members (club_id, child_id, status, enrolled_at, created_at)
            VALUES (?, ?, 'enrolled', CURDATE(), NOW())
        ")->execute([$clubId, $childId]);

    }



    return null;

}



function admin_club_remove_member(PDO $pdo, int $clubId, int $childId): ?string

{

    $stmt = $pdo->prepare('DELETE FROM club_members WHERE club_id = ? AND child_id = ?');

    $stmt->execute([$clubId, $childId]);

    if ($stmt->rowCount() === 0) {

        return 'Запись не найдена';

    }



    return null;

}



/**
 * Кружки с участниками для отчёта (блоками по кружку).
 *
 * @return list<array{club: array, members: list<array>}>
 */
function admin_report_clubs_grouped(PDO $pdo): array
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
        SELECT c.full_name AS child_name, g.name AS group_name,
               pu.full_name AS parent_name, cm.enrolled_at
        FROM club_members cm
        JOIN children c ON cm.child_id = c.id
        JOIN `groups` g ON c.group_id = g.id
        JOIN users pu ON c.parent_id = pu.id
        WHERE cm.club_id = ? AND cm.status = 'enrolled'
        ORDER BY c.full_name
    ");

    $out = [];
    foreach ($clubs as $club) {
        $stmt->execute([(int) $club['id']]);
        $out[] = [
            'club' => $club,
            'members' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    return $out;
}

function admin_club_seats_label(?int $count, ?int $max): string

{

    $count = $count ?? 0;

    if ($max === null || $max <= 0) {

        return (string) $count;

    }



    return $count . ' / ' . $max;

}



function admin_render_club_child_options(array $children): void

{
    $currentGroup = null;

    foreach ($children as $child) {

        $group = $child['group_name'] ?? '';

        if ($group !== $currentGroup) {

            if ($currentGroup !== null) {

                echo '</optgroup>';

            }

            $currentGroup = $group;

            echo '<optgroup label="' . htmlspecialchars($currentGroup) . '">';

        }

        $id = (int) $child['id'];

        $label = htmlspecialchars($child['full_name']);

        echo '<option value="' . $id . '">' . $label . '</option>';

    }

    if ($currentGroup !== null) {

        echo '</optgroup>';

    }

}

