<?php

/** Условие: строки недельного шаблона (повторяются каждую неделю). */
function menu_weekly_template_sql(string $alias = 'm'): string
{
    return "$alias.date IS NULL AND $alias.weekday BETWEEN 1 AND 5";
}

function menu_weekly_disclaimer_text(): string
{
    return 'На сайте опубликовано типовое меню на каждый день недели (понедельник — пятница): '
        . 'в личном кабинете в соответствующий день показывается меню на этот день недели. '
        . 'Состав блюд может быть изменён — например, из‑за поставок продуктов, сезонности или замены по медицинским и организационным причинам. '
        . 'Актуальная версия всегда отображается здесь.';
}

function menu_weekly_weekdays(): array
{
    return [
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
    ];
}

function menu_weekly_meal_types(): array
{
    return ['Завтрак', 'Второй завтрак', 'Обед', 'Полдник'];
}

function menu_weekly_weekday_label(int $weekday): string
{
    return menu_weekly_weekdays()[$weekday] ?? '—';
}

function menu_weekly_meal_order_sql(string $column = 'meal_type'): string
{
    return "FIELD($column, 'Завтрак', 'Второй завтрак', 'Обед', 'Полдник')";
}

function menu_weekly_for_group(PDO $pdo, int $groupId): array
{
    if ($groupId <= 0) {
        return [];
    }

    $tpl = menu_weekly_template_sql('m');
    $stmt = $pdo->prepare("
        SELECT m.*, g.name AS group_name, g.age_category AS group_age
        FROM menu m
        JOIN `groups` g ON m.group_id = g.id
        WHERE m.group_id = ? AND $tpl
        ORDER BY m.weekday ASC, " . menu_weekly_meal_order_sql('m.meal_type') . ', m.dish_name
    ');
    $stmt->execute([$groupId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function menu_weekly_grouped_by_day(array $items): array
{
    $byDay = [];
    foreach (array_keys(menu_weekly_weekdays()) as $day) {
        $byDay[$day] = [];
    }
    foreach ($items as $row) {
        $byDay[(int) $row['weekday']][] = $row;
    }

    return $byDay;
}

/**
 * Разворачивает недельное меню в строки с конкретными датами (для ЛК).
 */
function menu_weekly_for_date_range(
    PDO $pdo,
    string $from,
    string $to,
    array $groupIds,
    bool $onlyAssignedGroups = false
): array {
    if ($onlyAssignedGroups && $groupIds === []) {
        return [];
    }

    $tpl = menu_weekly_template_sql('m');
    $params = [];
    if ($groupIds === []) {
        $groupSql = '1=1';
    } else {
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $groupSql = "m.group_id IN ($placeholders)";
        $params = array_values($groupIds);
    }

    $stmt = $pdo->prepare("
        SELECT m.*, g.name AS group_name, g.age_category AS group_age
        FROM menu m
        JOIN `groups` g ON m.group_id = g.id
        WHERE $tpl AND $groupSql
        ORDER BY g.name ASC, m.weekday ASC, " . menu_weekly_meal_order_sql('m.meal_type') . ', m.dish_name
    ');
    $stmt->execute($params);
    $weekly = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($weekly === []) {
        return [];
    }

    $byGroupDay = [];
    foreach ($weekly as $row) {
        $gid = (int) $row['group_id'];
        $day = (int) $row['weekday'];
        $byGroupDay[$gid][$day][] = $row;
    }

    $start = new DateTime($from);
    $end = new DateTime($to);
    if ($end < $start) {
        return [];
    }

    $result = [];
    for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
        $weekday = (int) $d->format('N');
        if ($weekday > 5) {
            continue;
        }
        $dateStr = $d->format('Y-m-d');
        foreach ($byGroupDay as $gid => $days) {
            if ($groupIds !== [] && !in_array($gid, $groupIds, true)) {
                continue;
            }
            foreach ($days[$weekday] ?? [] as $row) {
                $item = $row;
                $item['date'] = $dateStr;
                $result[] = $item;
            }
        }
    }

    usort($result, static function ($a, $b) {
        $cmp = strcmp($a['date'], $b['date']);
        if ($cmp !== 0) {
            return $cmp;
        }
        $cmp = strcmp($a['group_name'] ?? '', $b['group_name'] ?? '');
        if ($cmp !== 0) {
            return $cmp;
        }

        return ((int) $a['weekday']) <=> ((int) $b['weekday']);
    });

    return $result;
}

function menu_weekly_today_for_group(PDO $pdo, int $groupId): array
{
    $weekday = (int) date('N');
    if ($weekday > 5) {
        return [];
    }

    $tpl = menu_weekly_template_sql('m');
    $stmt = $pdo->prepare("
        SELECT m.*, g.name AS group_name, g.age_category AS group_age
        FROM menu m
        JOIN `groups` g ON m.group_id = g.id
        WHERE m.group_id = ? AND m.weekday = ? AND $tpl
        ORDER BY " . menu_weekly_meal_order_sql('m.meal_type') . ', m.dish_name
    ');
    $stmt->execute([$groupId, $weekday]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Объединяет несколько строк одного приёма пищи в одну.
 *
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function menu_weekly_merge_meal_rows(array $rows): array
{
    if ($rows === []) {
        return [];
    }

    $buckets = [];

    foreach ($rows as $row) {
        $meal = (string)($row['meal_type'] ?? '');
        if ($meal === '') {
            continue;
        }
        if (!isset($buckets[$meal])) {
            $buckets[$meal] = $row;
            $buckets[$meal]['_dishes'] = [(string)$row['dish_name']];
            $buckets[$meal]['_weights'] = isset($row['weight']) && $row['weight'] !== '' && $row['weight'] !== null
                ? [(int)$row['weight']]
                : [];
            $buckets[$meal]['_allergies'] = menu_weekly_split_allergens((string)($row['allergies'] ?? ''));
            $buckets[$meal]['_alternatives'] = menu_weekly_collect_alternatives((string)($row['alternative_dish'] ?? ''));
            menu_weekly_merge_nutrition_into($buckets[$meal], $row, true);
            continue;
        }

        $buckets[$meal]['_dishes'][] = (string)$row['dish_name'];
        if (isset($row['weight']) && $row['weight'] !== '' && $row['weight'] !== null) {
            $buckets[$meal]['_weights'][] = (int)$row['weight'];
        }
        $buckets[$meal]['_allergies'] = array_merge(
            $buckets[$meal]['_allergies'],
            menu_weekly_split_allergens((string)($row['allergies'] ?? ''))
        );
        $buckets[$meal]['_alternatives'] = array_merge(
            $buckets[$meal]['_alternatives'] ?? [],
            menu_weekly_collect_alternatives((string)($row['alternative_dish'] ?? ''))
        );
        menu_weekly_merge_nutrition_into($buckets[$meal], $row, false);
    }

    $merged = [];
    foreach (menu_weekly_meal_types() as $meal) {
        if (!isset($buckets[$meal])) {
            continue;
        }
        $item = $buckets[$meal];
        $item['dish_name'] = implode('; ', array_filter($item['_dishes']));
        $weights = $item['_weights'];
        $item['weight'] = $weights !== [] ? array_sum($weights) : null;
        $allergens = array_unique(array_filter($item['_allergies']));
        $item['allergies'] = $allergens !== [] ? implode(', ', $allergens) : null;
        $alternatives = array_unique(array_filter($item['_alternatives'] ?? []));
        $item['alternative_dish'] = $alternatives !== [] ? implode('; ', $alternatives) : null;
        if (empty($item['_has_nutrition'])) {
            $item['protein_g'] = null;
            $item['fat_g'] = null;
            $item['carb_g'] = null;
        }
        unset(
            $item['_dishes'],
            $item['_weights'],
            $item['_allergies'],
            $item['_alternatives'],
            $item['_has_nutrition']
        );
        $merged[] = $item;
    }

    return $merged;
}

/** @param array<string, mixed> $bucket */
function menu_weekly_merge_nutrition_into(array &$bucket, array $row, bool $reset): void
{
    if ($reset) {
        $bucket['protein_g'] = 0.0;
        $bucket['fat_g'] = 0.0;
        $bucket['carb_g'] = 0.0;
        $bucket['_has_nutrition'] = false;
    }

    foreach (['protein_g', 'fat_g', 'carb_g'] as $key) {
        if (!isset($row[$key]) || $row[$key] === '' || $row[$key] === null) {
            continue;
        }
        $bucket['_has_nutrition'] = true;
        $bucket[$key] = round((float)($bucket[$key] ?? 0) + (float)$row[$key], 1);
    }
}

/** @return list<string> */
function menu_weekly_collect_alternatives(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    $parts = preg_split('/[;]+/u', $raw) ?: [];

    return array_values(array_filter(array_map('trim', $parts), static fn($p) => $p !== ''));
}

/** @return list<string> */
function menu_weekly_split_allergens(string $raw): array
{
    if (trim($raw) === '' || trim($raw) === '—') {
        return [];
    }

    $parts = preg_split('/[,;]+/u', $raw) ?: [];

    return array_values(array_filter(array_map('trim', $parts), static fn($p) => $p !== '' && $p !== '—'));
}

/** Свести меню группы к 4 приёмам пищи на каждый день недели. */
function menu_weekly_normalize_group(PDO $pdo, int $groupId): int
{
    if ($groupId <= 0) {
        return 0;
    }

    $tpl = menu_weekly_template_sql('m');
    $stmt = $pdo->prepare("
        SELECT id, weekday, meal_type, dish_name, weight, allergies, alternative_dish, protein_g, fat_g, carb_g
        FROM menu m
        WHERE group_id = ? AND $tpl
        ORDER BY weekday ASC, " . menu_weekly_meal_order_sql('meal_type') . ", dish_name
    ");
    $stmt->execute([$groupId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $byDay = [];
    foreach ($rows as $row) {
        $byDay[(int)$row['weekday']][] = $row;
    }

    $pdo->prepare('DELETE FROM menu WHERE group_id = ? AND date IS NULL AND weekday BETWEEN 1 AND 5')->execute([$groupId]);

    $insert = $pdo->prepare('
        INSERT INTO menu (group_id, weekday, date, meal_type, dish_name, weight, allergies, alternative_dish, protein_g, fat_g, carb_g)
        VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $written = 0;
    foreach ($byDay as $weekday => $dayRows) {
        foreach (menu_weekly_merge_meal_rows($dayRows) as $merged) {
            $insert->execute([
                $groupId,
                $weekday,
                $merged['meal_type'],
                $merged['dish_name'],
                $merged['weight'] ?: null,
                $merged['allergies'] ?: null,
                $merged['alternative_dish'] ?: null,
                $merged['protein_g'] ?? null,
                $merged['fat_g'] ?? null,
                $merged['carb_g'] ?? null,
            ]);
            $written++;
        }
    }

    return $written;
}

/** @return array{groups: int, rows: int} */
function menu_weekly_normalize_all(PDO $pdo): array
{
    $groups = $pdo->query('SELECT id FROM `groups` ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    $rows = 0;
    foreach ($groups as $groupId) {
        $rows += menu_weekly_normalize_group($pdo, (int)$groupId);
    }

    return ['groups' => count($groups), 'rows' => $rows];
}
