<?php

require_once __DIR__ . '/menu_pdf_plan.php';
require_once __DIR__ . '/group_labels.php';

/**
 * Недельное меню (пн–пт): 4 приёма пищи, по образцу menuuuuu19.pdf, с адаптацией по возрасту.
 */

/** @return list<array{0: string, 1: string, 2: int, 3: string}> */
function seed_menu_day_profile(string $ageCategory, int $weekday): array
{
    if ($weekday < 1 || $weekday > 5) {
        $weekday = (($weekday - 1) % 5) + 1;
    }

    return menu_pdf_day_for_category($ageCategory, $weekday);
}

/**
 * @return array{added: int, skipped: int}
 */
function seed_menu_for_all_groups(PDO $pdo, bool $replace = false): array
{
    $groups = $pdo->query('SELECT id, age_category FROM `groups` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

    $check = $pdo->prepare('
        SELECT COUNT(*) FROM menu
        WHERE group_id = ? AND weekday = ? AND date IS NULL
          AND meal_type = ? AND dish_name = ?
    ');
    $insert = $pdo->prepare('
        INSERT INTO menu (group_id, weekday, date, meal_type, dish_name, weight, allergies)
        VALUES (?, ?, NULL, ?, ?, ?, ?)
    ');
    $deleteGroup = $pdo->prepare('DELETE FROM menu WHERE group_id = ? AND date IS NULL AND weekday BETWEEN 1 AND 5');

    $added = 0;
    $skipped = 0;

    foreach ($groups as $group) {
        $groupId = (int)$group['id'];
        if ($replace) {
            $deleteGroup->execute([$groupId]);
        }

        for ($weekday = 1; $weekday <= 5; $weekday++) {
            foreach (seed_menu_day_profile($group['age_category'] ?? '', $weekday) as [$mealType, $dish, $weight, $allergies]) {
                if (!$replace) {
                    $check->execute([$groupId, $weekday, $mealType, $dish]);
                    if ((int)$check->fetchColumn() > 0) {
                        $skipped++;
                        continue;
                    }
                }
                $insert->execute([$groupId, $weekday, $mealType, $dish, $weight, $allergies ?: null]);
                $added++;
            }
        }
    }

    return ['added' => $added, 'skipped' => $skipped];
}

/** @deprecated */
function seed_menu_profile(string $ageCategory): array
{
    return seed_menu_day_profile($ageCategory, 1);
}

/** @deprecated */
function seed_menu_mon_tue_extras(string $ageCategory): array
{
    return [];
}

/** @deprecated */
function seed_menu_mon_tue_for_all_groups(PDO $pdo): array
{
    return ['added' => 0, 'skipped' => 0];
}
