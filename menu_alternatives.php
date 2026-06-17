<?php

require_once __DIR__ . '/allergies.php';
require_once __DIR__ . '/menu_weekly.php';

function menu_homemade_food_notice_text(): string
{
    return 'Если предложенная альтернатива не подходит ребёнку, по назначению врача родитель может приносить '
        . 'в детский сад готовые домашние блюда при соблюдении определённых условий. '
        . 'Это предусмотрено санитарно-эпидемиологическими правилами СанПиН 2.3/2.4.3590-20 '
        . '«Санитарно-эпидемиологические требования к организации общественного питания населения». '
        . 'Порядок согласуйте с медработником и администрацией сада.';
}

/** @param list<array<string, mixed>> $children */
function menu_children_attach_allergy_ids(PDO $pdo, array $children): array
{
    foreach ($children as &$child) {
        if (!isset($child['allergy_ids'])) {
            $child['allergy_ids'] = get_child_allergy_ids($pdo, (int)($child['id'] ?? 0));
        }
    }
    unset($child);

    return $children;
}

/** @return list<string> */
function menu_normalize_allergen_tokens(string $raw): array
{
    $tokens = menu_weekly_split_allergens($raw);
    $out = [];
    foreach ($tokens as $token) {
        $out[] = mb_strtolower($token);
    }

    return $out;
}

/** @param list<array<string, mixed>> $children */
function menu_children_in_group_with_allergies(array $children, int $groupId): array
{
    $out = [];
    foreach ($children as $child) {
        if ((int)($child['group_id'] ?? 0) !== $groupId) {
            continue;
        }
        if (!empty($child['allergy_names']) || !empty($child['allergy_ids'])) {
            $out[] = $child;
        }
    }

    return $out;
}

/** @param list<array<string, mixed>> $children */
function menu_group_has_allergic_children(array $children, int $groupId): bool
{
    return menu_children_in_group_with_allergies($children, $groupId) !== [];
}

function menu_allergens_overlap_dish_and_child(string $dishAllergies, array $child): bool
{
    $dishTokens = menu_normalize_allergen_tokens($dishAllergies);
    if ($dishTokens === []) {
        return false;
    }

    $childNames = [];
    if (!empty($child['allergy_names'])) {
        foreach (explode(',', (string)$child['allergy_names']) as $name) {
            $name = trim($name);
            if ($name !== '') {
                $childNames[] = mb_strtolower($name);
            }
        }
    }

    foreach ($dishTokens as $dishToken) {
        foreach ($childNames as $childName) {
            if ($dishToken === $childName
                || str_contains($dishToken, $childName)
                || str_contains($childName, $dishToken)
            ) {
                return true;
            }
        }
    }

    return false;
}

/** @param list<array<string, mixed>> $children */
function menu_row_relevant_for_group_children(array $row, array $children, int $groupId): bool
{
    if (empty($row['allergies'])) {
        return false;
    }

    foreach (menu_children_in_group_with_allergies($children, $groupId) as $child) {
        if (menu_allergens_overlap_dish_and_child((string)$row['allergies'], $child)) {
            return true;
        }
    }

    return false;
}

function menu_has_alternative(?array $row): bool
{
    return is_array($row)
        && trim((string)($row['alternative_dish'] ?? '')) !== ''
        && trim((string)($row['allergies'] ?? '')) !== '';
}

/**
 * Подсказка замены по названию блюда и аллергенам (для seed).
 */
function menu_suggest_alternative_dish(string $dishName, string $allergies): ?string
{
    $dish = mb_strtolower(trim($dishName));
    $allergens = menu_normalize_allergen_tokens($allergies);
    if ($allergens === []) {
        return null;
    }

    $hasMilk = false;
    $hasGluten = false;
    $hasEgg = false;
    $hasFish = false;

    foreach ($allergens as $token) {
        if (str_contains($token, 'молок') || $token === 'лактоза') {
            $hasMilk = true;
        }
        if (str_contains($token, 'глютен') || str_contains($token, 'пшениц') || $token === 'батон') {
            $hasGluten = true;
        }
        if (str_contains($token, 'яйц') || $token === 'яйцо') {
            $hasEgg = true;
        }
        if (str_contains($token, 'рыб')) {
            $hasFish = true;
        }
    }

    if ($hasMilk) {
        if (str_contains($dish, 'каша')) {
            return 'Каша на воде';
        }
        if (str_contains($dish, 'какао')) {
            return 'Какао на воде';
        }
        if (str_contains($dish, 'чай') && str_contains($dish, 'молок')) {
            return 'Чай без молока';
        }
        if (str_contains($dish, 'йогурт')) {
            return 'Йогурт безлактозный';
        }
        if (str_contains($dish, 'кефир')) {
            return 'Кефир безлактозный';
        }
        if (str_contains($dish, 'молок')) {
            return 'Напиток растительный (по согласованию с медработником)';
        }
    }

    if ($hasGluten && (str_contains($dish, 'батон') || str_contains($dish, 'хлеб') || str_contains($dish, 'гренк'))) {
        return 'Хлеб безглютеновый';
    }

    if ($hasEgg && (str_contains($dish, 'вафл') || str_contains($dish, 'беляш') || str_contains($dish, 'суфле'))) {
        return 'Блюдо без яйца (по согласованию с медработником)';
    }

    if ($hasFish && str_contains($dish, 'суфле')) {
        return 'Котлета мясная (по согласованию с медработником)';
    }

    return null;
}

/** Заполнить alternative_dish там, где есть аллергены, но замена ещё не задана. */
function menu_fill_suggested_alternatives(PDO $pdo): int
{
    $tpl = menu_weekly_template_sql('m');
    $rows = $pdo->query("
        SELECT id, dish_name, allergies, alternative_dish
        FROM menu m
        WHERE $tpl
          AND allergies IS NOT NULL AND allergies <> ''
    ")->fetchAll(PDO::FETCH_ASSOC);

    $update = $pdo->prepare('UPDATE menu SET alternative_dish = ? WHERE id = ?');
    $updated = 0;

    foreach ($rows as $row) {
        if (trim((string)($row['alternative_dish'] ?? '')) !== '') {
            continue;
        }
        $alt = menu_suggest_alternative_dish((string)$row['dish_name'], (string)$row['allergies']);
        if ($alt === null) {
            continue;
        }
        $update->execute([$alt, (int)$row['id']]);
        $updated++;
    }

    return $updated;
}
