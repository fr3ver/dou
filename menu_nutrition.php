<?php

require_once __DIR__ . '/menu_weekly.php';
require_once __DIR__ . '/group_labels.php';
require_once __DIR__ . '/menu_pdf_plan.php';

function menu_chef_notice_text(): string
{
    return 'Меню разработано квалифицированными поварами с учётом возрастных норм питания. '
        . 'Утверждают и выгружают в систему старший воспитатель и заведующая.';
}

function menu_nutrition_parse_optional(?string $raw): ?float
{
    $raw = trim((string)$raw);
    if ($raw === '') {
        return null;
    }
    $value = (float)str_replace(',', '.', $raw);

    return $value >= 0 ? round($value, 1) : null;
}

function menu_nutrition_row_value(?array $row, string $key): float
{
    if (!$row || !isset($row[$key]) || $row[$key] === '' || $row[$key] === null) {
        return 0.0;
    }

    return (float)$row[$key];
}

/**
 * Сумма БЖУ по списку блюд одного дня.
 *
 * @param list<array<string, mixed>> $rows
 * @return array{protein_g: ?float, fat_g: ?float, carb_g: ?float, has_values: bool}
 */
function menu_nutrition_sum_rows(array $rows): array
{
    $hasValues = false;
    foreach ($rows as $row) {
        foreach (['protein_g', 'fat_g', 'carb_g'] as $key) {
            if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                $hasValues = true;
                break 2;
            }
        }
    }

    if (!$hasValues) {
        return ['protein_g' => null, 'fat_g' => null, 'carb_g' => null, 'has_values' => false];
    }

    $protein = $fat = $carb = 0.0;
    foreach ($rows as $row) {
        $protein += menu_nutrition_row_value($row, 'protein_g');
        $fat += menu_nutrition_row_value($row, 'fat_g');
        $carb += menu_nutrition_row_value($row, 'carb_g');
    }

    return [
        'protein_g' => round($protein, 1),
        'fat_g' => round($fat, 1),
        'carb_g' => round($carb, 1),
        'has_values' => true,
    ];
}

function menu_nutrition_compact_label(?array $row): string
{
    if (!$row || !menu_nutrition_has_values($row)) {
        return '';
    }

    $parts = [];
    foreach (['protein_g' => 'Б', 'fat_g' => 'Ж', 'carb_g' => 'У'] as $key => $label) {
        if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
            $parts[] = $label . ' ' . menu_nutrition_format_grams($row[$key]);
        }
    }

    return implode(' · ', $parts);
}

/**
 * Суточные БЖУ как сумма блюд menu.
 *
 * @return array<int, array<string, mixed>> weekday => row
 */
function menu_daily_nutrition_for_group(PDO $pdo, int $groupId): array
{
    $map = menu_daily_nutrition_map($pdo, [$groupId]);

    return $map[$groupId] ?? [];
}

/**
 * @param list<int> $groupIds
 * @return array<int, array<int, array<string, mixed>>>
 */
function menu_daily_nutrition_map(PDO $pdo, array $groupIds): array
{
    if ($groupIds === []) {
        return [];
    }

    $tpl = menu_weekly_template_sql('m');
    $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
    $stmt = $pdo->prepare("
        SELECT group_id, weekday, protein_g, fat_g, carb_g
        FROM menu m
        WHERE $tpl AND group_id IN ($placeholders)
    ");
    $stmt->execute(array_values($groupIds));

    $byGroupDay = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $gid = (int)$row['group_id'];
        $wd = (int)$row['weekday'];
        $byGroupDay[$gid][$wd][] = $row;
    }

    $map = [];
    foreach ($byGroupDay as $gid => $days) {
        foreach ($days as $wd => $rows) {
            $sum = menu_nutrition_sum_rows($rows);
            if (!$sum['has_values']) {
                continue;
            }
            $map[$gid][$wd] = [
                'group_id' => $gid,
                'weekday' => $wd,
                'protein_g' => $sum['protein_g'],
                'fat_g' => $sum['fat_g'],
                'carb_g' => $sum['carb_g'],
                'source' => 'dishes',
            ];
        }
    }

    return $map;
}

function menu_nutrition_format_grams($value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $num = (float)$value;

    return rtrim(rtrim(number_format($num, 1, '.', ''), '0'), '.') . ' г';
}

function menu_nutrition_has_values(?array $row): bool
{
    if (!$row) {
        return false;
    }

    foreach (['protein_g', 'fat_g', 'carb_g'] as $key) {
        if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
            return true;
        }
    }

    return false;
}

function menu_weekday_from_date(string $date): int
{
    return (int)(new DateTime($date))->format('N');
}

/**
 * Суточные нормы БЖУ по возрастной группе.
 *
 * @return array{protein: float, fat: float, carb: float}
 */
function menu_nutrition_norms_for_category(?string $ageCategory): array
{
    $band = dou_age_band($ageCategory);

    if ($band === 'nursery') {
        return ['protein' => 42.0, 'fat' => 47.0, 'carb' => 203.0];
    }

    return ['protein' => 54.0, 'fat' => 60.0, 'carb' => 261.0];
}

/**
 * БЖУ на день с учётом возраста и меню из PDF.
 *
 * @return array{protein: float, fat: float, carb: float}
 */
function menu_nutrition_daily_for_category(?string $ageCategory, int $weekday): array
{
    $band = dou_age_band($ageCategory);
    $pdfDay = menu_pdf_daily_bju()[$weekday] ?? null;

    if ($band === 'nursery') {
        return menu_nutrition_norms_for_category($ageCategory);
    }

    if (in_array($band, ['senior', 'speech'], true) && $pdfDay !== null) {
        return $pdfDay;
    }

    if ($band === 'middle' && $pdfDay !== null) {
        return [
            'protein' => round($pdfDay['protein'] * 0.95, 1),
            'fat'     => round($pdfDay['fat'] * 0.95, 1),
            'carb'    => round($pdfDay['carb'] * 0.95, 1),
        ];
    }

    if ($band === 'junior' && $pdfDay !== null) {
        return [
            'protein' => round($pdfDay['protein'] * 0.88, 1),
            'fat'     => round($pdfDay['fat'] * 0.88, 1),
            'carb'    => round($pdfDay['carb'] * 0.88, 1),
        ];
    }

    return menu_nutrition_norms_for_category($ageCategory);
}

function menu_bju_lookup_key(int $weekday, string $mealType, string $dish): string
{
    return $weekday . '|' . $mealType . '|' . mb_strtolower(trim($dish));
}

/** Подогнать сумму БЖУ блюд за день к точной суточной норме (коррекция округления). */
function menu_reconcile_group_day_bju(PDO $pdo, int $groupId, int $weekday, ?string $ageCategory): void
{
    if ($groupId <= 0 || $weekday < 1 || $weekday > 5) {
        return;
    }

    $target = menu_nutrition_daily_for_category($ageCategory, $weekday);
    $tpl = menu_weekly_template_sql('m');
    $stmt = $pdo->prepare("
        SELECT id, protein_g, fat_g, carb_g
        FROM menu m
        WHERE group_id = ? AND weekday = ? AND $tpl
        ORDER BY id
    ");
    $stmt->execute([$groupId, $weekday]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows === []) {
        return;
    }

    $sum = menu_nutrition_sum_rows($rows);
    if (!$sum['has_values']) {
        return;
    }

    $lastId = (int)$rows[count($rows) - 1]['id'];
    $adjust = $pdo->prepare('UPDATE menu SET protein_g = ?, fat_g = ?, carb_g = ? WHERE id = ?');

    $last = $rows[count($rows) - 1];
    $protein = menu_nutrition_row_value($last, 'protein_g');
    $fat = menu_nutrition_row_value($last, 'fat_g');
    $carb = menu_nutrition_row_value($last, 'carb_g');

    $protein += round((float)$target['protein'] - (float)$sum['protein_g'], 1);
    $fat += round((float)$target['fat'] - (float)$sum['fat_g'], 1);
    $carb += round((float)$target['carb'] - (float)$sum['carb_g'], 1);

    $adjust->execute([
        max(0, round($protein, 1)),
        max(0, round($fat, 1)),
        max(0, round($carb, 1)),
        $lastId,
    ]);
}

/**
 * Оценка БЖУ блюда по названию и весу (без нормализации к суточной норме).
 *
 * @return array{protein_g: float, fat_g: float, carb_g: float}
 */
function menu_estimate_dish_nutrition(string $dishName, int $weight): array
{
    require_once __DIR__ . '/nutrition_sanpin_pdf.php';

    $dishes = nutrition_sanpin_split_dishes($dishName);
    $count = count($dishes);
    $portion = $count > 0 ? max(1, (int)round($weight / $count)) : max(1, $weight);

    $protein = $fat = $carb = 0.0;
    foreach ($dishes as $dish) {
        $est = nutrition_sanpin_estimate_per_100g($dish);
        $factor = $portion / 100;
        $protein += $est['protein'] * $factor;
        $fat += $est['fat'] * $factor;
        $carb += $est['carb'] * $factor;
    }

    return [
        'protein_g' => round($protein, 1),
        'fat_g' => round($fat, 1),
        'carb_g' => round($carb, 1),
    ];
}

/**
 * Точные БЖУ в menu: расчёт по техкартам + нормализация к суточным нормам возраста.
 * Сумма блюд за день совпадает с нормами menu_pdf_plan / СанПиН.
 */
function menu_fill_precise_dish_bju(PDO $pdo, bool $overwrite = true): int
{
    require_once __DIR__ . '/nutrition_sanpin_pdf.php';

    $groups = $pdo->query('SELECT id, age_category FROM `groups` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $update = $pdo->prepare('UPDATE menu SET protein_g = ?, fat_g = ?, carb_g = ? WHERE id = ?');
    $updated = 0;

    foreach ($groups as $group) {
        $groupId = (int)$group['id'];
        $ageCategory = $group['age_category'] ?? null;
        $items = menu_weekly_for_group($pdo, $groupId);
        if ($items === []) {
            continue;
        }

        $menuInput = [];
        foreach ($items as $row) {
            $menuInput[] = [
                'weekday' => (int)$row['weekday'],
                'meal_type' => (string)$row['meal_type'],
                'dish_name' => (string)$row['dish_name'],
                'weight' => (int)($row['weight'] ?? 0),
                'allergies' => (string)($row['allergies'] ?? ''),
            ];
        }

        $bjuRows = nutrition_sanpin_build_dish_bju($menuInput, $ageCategory);
        $index = [];
        foreach ($bjuRows as $bju) {
            $key = menu_bju_lookup_key((int)$bju['weekday'], (string)$bju['meal_type'], (string)$bju['dish']);
            $index[$key][] = $bju;
        }

        foreach ($items as $row) {
            if (!$overwrite) {
                $has = false;
                foreach (['protein_g', 'fat_g', 'carb_g'] as $key) {
                    if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                        $has = true;
                        break;
                    }
                }
                if ($has) {
                    continue;
                }
            }

            $weekday = (int)$row['weekday'];
            $mealType = (string)$row['meal_type'];
            $subdishes = nutrition_sanpin_split_dishes((string)$row['dish_name']);

            $protein = $fat = $carb = 0.0;
            $matched = false;
            foreach ($subdishes as $sub) {
                $key = menu_bju_lookup_key($weekday, $mealType, $sub);
                if (empty($index[$key])) {
                    continue;
                }
                $matched = true;
                $bju = array_shift($index[$key]);
                $protein += (float)$bju['protein'];
                $fat += (float)$bju['fat'];
                $carb += (float)$bju['carb'];
            }

            if (!$matched) {
                $weight = max(1, (int)($row['weight'] ?? 0));
                $est = menu_estimate_dish_nutrition((string)$row['dish_name'], $weight);
                $protein = $est['protein_g'];
                $fat = $est['fat_g'];
                $carb = $est['carb_g'];
            }

            $update->execute([
                round($protein, 1),
                round($fat, 1),
                round($carb, 1),
                (int)$row['id'],
            ]);
            $updated++;
        }

        for ($weekday = 1; $weekday <= 5; $weekday++) {
            menu_reconcile_group_day_bju($pdo, $groupId, $weekday, $ageCategory);
        }
    }

    return $updated;
}

/** @deprecated Используйте menu_fill_precise_dish_bju */
function menu_fill_dish_bju_estimates(PDO $pdo, bool $overwrite = false): int
{
    return menu_fill_precise_dish_bju($pdo, $overwrite);
}
