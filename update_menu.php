<?php
require_once '_auth.php';
require_once __DIR__ . '/../includes/menu_nutrition.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}

$id        = (int)($_POST['id'] ?? 0);
$group_id  = (int)($_POST['group_id'] ?? 0);
$weekday   = (int)($_POST['weekday'] ?? 0);
$meal_type = trim($_POST['meal_type'] ?? '');
$dish_name = trim($_POST['dish_name'] ?? '');
$weight    = (int)($_POST['weight'] ?? 0);
$allergies = trim($_POST['allergies'] ?? '');
$alternative_dish = trim($_POST['alternative_dish'] ?? '');
$protein_g = menu_nutrition_parse_optional($_POST['protein_g'] ?? null);
$fat_g = menu_nutrition_parse_optional($_POST['fat_g'] ?? null);
$carb_g = menu_nutrition_parse_optional($_POST['carb_g'] ?? null);

if ($id <= 0 || $group_id <= 0 || $weekday < 1 || $weekday > 5 || $meal_type === '' || $dish_name === '') {
    admin_flash('error', 'Заполните обязательные поля');
    header('Location: edit_menu.php?id=' . $id);
    exit;
}

try {
    $pdo->prepare('
        UPDATE menu
        SET group_id = ?, weekday = ?, meal_type = ?, dish_name = ?, weight = ?, allergies = ?, alternative_dish = ?,
            protein_g = ?, fat_g = ?, carb_g = ?
        WHERE id = ? AND date IS NULL
    ')->execute([
        $group_id,
        $weekday,
        $meal_type,
        $dish_name,
        $weight ?: null,
        $allergies ?: null,
        $alternative_dish !== '' ? $alternative_dish : null,
        $protein_g,
        $fat_g,
        $carb_g,
        $id,
    ]);
    admin_flash('success', 'Блюдо обновлено');
    header('Location: menu.php?group_id=' . $group_id);
} catch (Exception $e) {
    admin_flash('error', $e->getMessage());
    header('Location: edit_menu.php?id=' . $id);
}
exit;
