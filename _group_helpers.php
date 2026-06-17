<?php

require_once __DIR__ . '/../includes/group_labels.php';

function admin_group_age_categories(): array
{
    return dou_age_categories();
}

function admin_group_age_band(?string $ageCategory): string
{
    return dou_age_band($ageCategory);
}

function admin_group_age_band_order(): array
{
    return dou_age_band_order();
}

function admin_groups_sorted_by_band(PDO $pdo): array
{
    return dou_groups_sorted_by_band($pdo);
}

function admin_render_group_select_options(PDO $pdo, int $selectedId = 0, bool|string $emptyOption = false): void
{
    dou_render_group_select_options($pdo, $selectedId, $emptyOption);
}

function admin_render_group_age_options(?string $selected = null): void
{
    dou_render_group_age_options($selected);
}

function admin_group_age_display(?string $ageCategory): string
{
    return dou_age_display($ageCategory);
}

function admin_group_find(PDO $pdo, int $groupId): ?array
{
    if ($groupId <= 0) {
        return null;
    }
    $stmt = $pdo->prepare("
        SELECT g.*,
               (SELECT GROUP_CONCAT(
                    CONCAT(u.full_name, ' (', e.position, ')')
                    ORDER BY FIELD(e.position, 'Воспитатель', 'Логопед'), u.full_name
                    SEPARATOR ', '
                )
                FROM employees e
                JOIN users u ON u.id = e.user_id
                WHERE e.group_id = g.id AND e.position IN ('Воспитатель', 'Логопед')
               ) AS staff_names,
               (SELECT u.full_name FROM employees e
                JOIN users u ON u.id = e.user_id
                WHERE e.group_id = g.id AND e.position IN ('Воспитатель', 'Логопед')
                ORDER BY FIELD(e.position, 'Воспитатель', 'Логопед'), u.full_name
                LIMIT 1
               ) AS teacher_name
        FROM `groups` g
        WHERE g.id = ?
    ");
    $stmt->execute([$groupId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/** @return list<array> */
function admin_group_children(PDO $pdo, int $groupId): array
{
    require_once __DIR__ . '/_allergies.php';

    $stmt = $pdo->prepare("
        SELECT c.id, c.full_name, c.date_of_birth, c.has_tnr,
               p.full_name AS parent_name, p.phone AS parent_phone,
               " . admin_child_allergies_sql('c') . " AS allergy_names
        FROM children c
        JOIN users p ON c.parent_id = p.id
        WHERE c.group_id = ?
        ORDER BY c.full_name
    ");
    $stmt->execute([$groupId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** @return list<int> user_id педагогов, привязанных к группе */
function admin_group_staff_user_ids(PDO $pdo, int $groupId): array
{
    $stmt = $pdo->prepare("
        SELECT user_id FROM employees
        WHERE group_id = ? AND position IN ('Воспитатель', 'Логопед')
        ORDER BY FIELD(position, 'Воспитатель', 'Логопед'), id
    ");
    $stmt->execute([$groupId]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/** Назначить в группу одного или нескольких воспитателей / логопедов. */
function admin_sync_group_staff(PDO $pdo, int $groupId, array $userIds): void
{
    $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
    $placeholders = $userIds !== [] ? implode(',', array_fill(0, count($userIds), '?')) : '0';

    $stmt = $pdo->prepare("
        UPDATE employees SET group_id = NULL
        WHERE group_id = ? AND position IN ('Воспитатель', 'Логопед')
          AND user_id NOT IN ({$placeholders})
    ");
    $stmt->execute(array_merge([$groupId], $userIds ?: [0]));

    foreach ($userIds as $userId) {
        $check = $pdo->prepare('SELECT id, position FROM employees WHERE user_id = ?');
        $check->execute([$userId]);
        $emp = $check->fetch(PDO::FETCH_ASSOC);

        if ($emp) {
            $pos = in_array($emp['position'], ['Воспитатель', 'Логопед'], true)
                ? $emp['position']
                : 'Воспитатель';
            $pdo->prepare('UPDATE employees SET group_id = ?, position = ? WHERE user_id = ?')
                ->execute([$groupId, $pos, $userId]);
        } else {
            $pdo->prepare("INSERT INTO employees (user_id, position, group_id) VALUES (?, 'Воспитатель', ?)")
                ->execute([$userId, $groupId]);
        }
    }
}

function admin_group_children_label(int $count, int $capacity): string
{
    if ($capacity > 0) {
        return $count . ' / ' . $capacity;
    }

    return (string) $count;
}

/**
 * Строки отчёта, сгруппированные по группам (порядок — возрастные категории).
 *
 * @param list<array<string, mixed>> $rows
 * @return list<array{group: array, rows: list<array>}>
 */
function admin_report_grouped_by_group(PDO $pdo, array $rows, string $groupIdKey = 'group_id'): array
{
    $byGroupId = [];
    foreach ($rows as $row) {
        $gid = (int) ($row[$groupIdKey] ?? 0);
        if ($gid <= 0) {
            continue;
        }
        $byGroupId[$gid][] = $row;
    }

    $out = [];
    foreach (admin_groups_sorted_by_band($pdo) as $group) {
        $gid = (int) $group['id'];
        if (empty($byGroupId[$gid])) {
            continue;
        }
        $out[] = ['group' => $group, 'rows' => $byGroupId[$gid]];
    }

    return $out;
}
