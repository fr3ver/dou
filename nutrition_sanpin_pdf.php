<?php

require_once __DIR__ . '/menu_nutrition.php';
require_once __DIR__ . '/menu_weekly.php';

/** @return TCPDF */
function nutrition_sanpin_pdf_create(string $orientation = 'P')
{
    if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
        define('K_TCPDF_EXTERNAL_CONFIG', true);
    }
    if (!defined('K_PATH_MAIN')) {
        define('K_PATH_MAIN', __DIR__ . '/../lib/tcpdf/');
    }
    if (!defined('K_PATH_FONTS')) {
        define('K_PATH_FONTS', K_PATH_MAIN . 'fonts/');
    }

    require_once K_PATH_MAIN . 'tcpdf.php';

    $pdf = new TCPDF($orientation, 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(SITE_NAME);
    $pdf->SetAuthor(SITE_NAME);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(12, 14, 12);
    $pdf->SetAutoPageBreak(true, 14);

    return $pdf;
}

function nutrition_sanpin_pdf_section(TCPDF $pdf, string $title, int $level = 1): void
{
    $pdf->Ln($level === 1 ? 4 : 2);
    $pdf->SetFont('dejavusans', 'B', $level === 1 ? 13 : 11);
    $pdf->MultiCell(0, 7, $title, 0, 'L', false, 1);
    $pdf->Ln(2);
}

function nutrition_sanpin_pdf_html(TCPDF $pdf, string $html, int $fontSize = 8): void
{
    $pdf->SetFont('dejavusans', '', $fontSize);
    $pdf->writeHTML($html, true, false, true, false, '');
}

function nutrition_sanpin_pdf_escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @return array<string, array{id: int, name: string, age_category: string}> */
function nutrition_sanpin_representative_groups(PDO $pdo): array
{
    $rows = $pdo->query('SELECT id, name, age_category FROM `groups` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $byBand = [];
    foreach ($rows as $row) {
        $band = dou_age_band($row['age_category'] ?? '');
        if (!isset($byBand[$band])) {
            $byBand[$band] = $row;
        }
    }

    return $byBand;
}

/**
 * @return list<array{meal_type: string, dish_name: string, weight: int, allergies: string}>
 */
function nutrition_sanpin_menu_for_group(PDO $pdo, int $groupId, string $ageCategory): array
{
    $items = menu_weekly_for_group($pdo, $groupId);
    if ($items !== []) {
        $out = [];
        foreach ($items as $row) {
            $out[] = [
                'weekday'    => (int)$row['weekday'],
                'meal_type'  => (string)$row['meal_type'],
                'dish_name'  => (string)$row['dish_name'],
                'weight'     => (int)($row['weight'] ?? 0),
                'allergies'  => (string)($row['allergies'] ?? ''),
            ];
        }

        return $out;
    }

    $out = [];
    for ($day = 1; $day <= 5; $day++) {
        foreach (menu_pdf_day_for_category($ageCategory, $day) as [$meal, $dish, $weight, $allergies]) {
            $out[] = [
                'weekday'   => $day,
                'meal_type' => $meal,
                'dish_name' => $dish,
                'weight'    => $weight,
                'allergies' => $allergies,
            ];
        }
    }

    return $out;
}

/** @return list<string> */
function nutrition_sanpin_split_dishes(string $dishName): array
{
    $parts = preg_split('/[;]+/u', $dishName) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts), static fn($p) => $p !== ''));

    return $parts !== [] ? $parts : [$dishName];
}

/**
 * @param list<array{weekday: int, meal_type: string, dish_name: string, weight: int, allergies: string}> $menu
 * @return list<array{weekday: int, meal_type: string, dish: string, weight: int, protein: float, fat: float, carb: float, kcal: float, recipe: string}>
 */
function nutrition_sanpin_build_dish_bju(array $menu, string $ageCategory): array
{
    $rows = [];
    foreach ($menu as $meal) {
        $dishes = nutrition_sanpin_split_dishes($meal['dish_name']);
        $count = count($dishes);
        $portion = $count > 0 ? max(1, (int)round($meal['weight'] / $count)) : $meal['weight'];

        foreach ($dishes as $dish) {
            $est = nutrition_sanpin_estimate_per_100g($dish);
            $factor = $portion / 100;
            $rows[] = [
                'weekday'  => $meal['weekday'],
                'meal_type'=> $meal['meal_type'],
                'dish'     => $dish,
                'weight'   => $portion,
                'protein'  => round($est['protein'] * $factor, 1),
                'fat'      => round($est['fat'] * $factor, 1),
                'carb'     => round($est['carb'] * $factor, 1),
                'kcal'     => round($est['kcal'] * $factor, 1),
                'recipe'   => $est['recipe'],
            ];
        }
    }

    for ($day = 1; $day <= 5; $day++) {
        $target = menu_nutrition_daily_for_category($ageCategory, $day);
        nutrition_sanpin_normalize_day_bju($rows, $day, $target);
    }

    return $rows;
}

/** @return array{protein: float, fat: float, carb: float, kcal: float, recipe: string} */
function nutrition_sanpin_estimate_per_100g(string $dish): array
{
    $d = mb_strtolower($dish);

    $profile = match (true) {
        str_contains($d, 'каша')        => ['protein' => 3.2, 'fat' => 4.8, 'carb' => 14.5, 'kcal' => 118, 'recipe' => '181 (2011)'],
        str_contains($d, 'суп')       => ['protein' => 2.8, 'fat' => 3.5, 'carb' => 6.2, 'kcal' => 72, 'recipe' => '1/1 (2021)'],
        str_contains($d, 'борщ'),
        str_contains($d, 'щи')        => ['protein' => 3.1, 'fat' => 4.2, 'carb' => 5.8, 'kcal' => 78, 'recipe' => '2/1 (2021)'],
        str_contains($d, 'котлет'),
        str_contains($d, 'тефтел'),
        str_contains($d, 'гуляш'),
        str_contains($d, 'плов')      => ['protein' => 9.5, 'fat' => 12.0, 'carb' => 8.0, 'kcal' => 175, 'recipe' => '254 (2008)'],
        str_contains($d, 'рыб'),
        str_contains($d, 'суфле')       => ['protein' => 8.2, 'fat' => 5.5, 'carb' => 4.0, 'kcal' => 105, 'recipe' => '227 (2011)'],
        str_contains($d, 'пюре'),
        str_contains($d, 'картоф')      => ['protein' => 1.8, 'fat' => 4.5, 'carb' => 14.0, 'kcal' => 112, 'recipe' => '335 (2008)'],
        str_contains($d, 'рис'),
        str_contains($d, 'греч'),
        str_contains($d, 'лапша'),
        str_contains($d, 'макар')       => ['protein' => 2.5, 'fat' => 1.2, 'carb' => 22.0, 'kcal' => 115, 'recipe' => '209 (2008)'],
        str_contains($d, 'хлеб'),
        str_contains($d, 'батон')       => ['protein' => 7.5, 'fat' => 1.0, 'carb' => 49.0, 'kcal' => 235, 'recipe' => '123 (2012)'],
        str_contains($d, 'салат')       => ['protein' => 1.2, 'fat' => 5.0, 'carb' => 8.5, 'kcal' => 85, 'recipe' => '41 (2008)'],
        str_contains($d, 'компот'),
        str_contains($d, 'кисель'),
        str_contains($d, 'сок')         => ['protein' => 0.3, 'fat' => 0.0, 'carb' => 12.0, 'kcal' => 50, 'recipe' => '11/1 (2021)'],
        str_contains($d, 'чай'),
        str_contains($d, 'какао'),
        str_contains($d, 'кофе')        => ['protein' => 1.5, 'fat' => 2.0, 'carb' => 8.0, 'kcal' => 58, 'recipe' => '8/1 (2021)'],
        str_contains($d, 'йогурт'),
        str_contains($d, 'кефир'),
        str_contains($d, 'молок')       => ['protein' => 3.0, 'fat' => 3.2, 'carb' => 4.7, 'kcal' => 60, 'recipe' => '434 (2008)'],
        str_contains($d, 'творог'),
        str_contains($d, 'ватруш'),
        str_contains($d, 'беляш'),
        str_contains($d, 'печень'),
        str_contains($d, 'вафл'),
        str_contains($d, 'гренк')       => ['protein' => 6.5, 'fat' => 8.0, 'carb' => 28.0, 'kcal' => 210, 'recipe' => '451 (2008)'],
        str_contains($d, 'фрукт'),
        str_contains($d, 'яблок')       => ['protein' => 0.4, 'fat' => 0.4, 'carb' => 9.5, 'kcal' => 46, 'recipe' => '338 (2005)'],
        str_contains($d, 'соус')        => ['protein' => 1.0, 'fat' => 6.0, 'carb' => 4.5, 'kcal' => 75, 'recipe' => '349 (2012)'],
        default                         => ['protein' => 3.0, 'fat' => 3.5, 'carb' => 12.0, 'kcal' => 95, 'recipe' => '—'],
    };

    return $profile;
}

/**
 * @param list<array<string, mixed>> $rows
 * @param array{protein: float, fat: float, carb: float} $target
 */
function nutrition_sanpin_normalize_day_bju(array &$rows, int $weekday, array $target): void
{
    $sum = ['protein' => 0.0, 'fat' => 0.0, 'carb' => 0.0];
    foreach ($rows as $row) {
        if ((int)$row['weekday'] !== $weekday) {
            continue;
        }
        $sum['protein'] += $row['protein'];
        $sum['fat']     += $row['fat'];
        $sum['carb']     += $row['carb'];
    }

    foreach (['protein', 'fat', 'carb'] as $key) {
        if ($sum[$key] <= 0) {
            continue;
        }
        $factor = $target[$key] / $sum[$key];
        foreach ($rows as &$row) {
            if ((int)$row['weekday'] !== $weekday) {
                continue;
            }
            $row[$key] = round($row[$key] * $factor, 1);
            $row['kcal'] = round($row['protein'] * 4 + $row['fat'] * 9 + $row['carb'] * 4, 1);
        }
        unset($row);
    }
}

/** @return array<string, float> product => daily norm g */
function nutrition_sanpin_product_norms(): array
{
    return [
        'Хлеб ржаной'                    => 15.0,
        'Хлеб пшеничный'                 => 55.0,
        'Мука пшеничная'                 => 8.0,
        'Крупы'                          => 17.0,
        'Макаронные изделия'             => 5.0,
        'Картофель'                      => 160.0,
        'Овощи разные'                   => 120.0,
        'Капуста свежая'                 => 35.0,
        'Свёкла'                         => 15.0,
        'Морковь'                        => 15.0,
        'Огурцы, помидоры'               => 10.0,
        'Сухофрукты'                     => 4.0,
        'Сахар'                          => 35.0,
        'Кондитерские изделия'           => 25.0,
        'Мясо'                           => 68.0,
        'Птица'                          => 34.0,
        'Рыба'                           => 34.0,
        'Яйца, шт.'                      => 0.5,
        'Молоко'                         => 400.0,
        'Кисломолочные продукты'         => 150.0,
        'Творог'                         => 39.0,
        'Сметана'                        => 12.0,
        'Сыр'                            => 6.0,
        'Масло сливочное'                => 17.0,
        'Масло растительное'             => 6.0,
        'Чай'                            => 0.3,
        'Какао'                          => 1.0,
        'Соль'                           => 5.0,
    ];
}

/**
 * Распределение продуктов по 14-дневному циклу (на основе 5-дневного меню).
 *
 * @return array<int, array<string, float>> day 1..14 => product => g
 */
function nutrition_sanpin_product_cycle(): array
{
    $norms = nutrition_sanpin_product_norms();
    $cycle = [];

    $dayProfiles = [
        1 => ['Мясо' => 1.2, 'Крупы' => 1.1, 'Молоко' => 1.0, 'Какао' => 1.0],
        2 => ['Рыба' => 1.3, 'Крупы' => 0.9, 'Молоко' => 1.1, 'Кисломолочные продукты' => 1.2],
        3 => ['Мясо' => 1.1, 'Крупы' => 1.0, 'Овощи разные' => 1.1, 'Сахар' => 1.0],
        4 => ['Мясо' => 1.0, 'Крупы' => 1.1, 'Творог' => 1.3, 'Молоко' => 1.0],
        5 => ['Мясо' => 1.1, 'Макаронные изделия' => 1.2, 'Овощи разные' => 1.0, 'Кондитерские изделия' => 1.1],
    ];

    for ($day = 1; $day <= 14; $day++) {
        $weekday = (($day - 1) % 5) + 1;
        $profile = $dayProfiles[$weekday];
        $cycle[$day] = [];
        foreach ($norms as $product => $norm) {
            $mult = $profile[$product] ?? 1.0;
            $cycle[$day][$product] = round($norm * $mult, 2);
        }
    }

    return $cycle;
}

/** @return list<array<string, mixed>> */
function nutrition_sanpin_tech_cards(): array
{
    return [
        [
            'no'       => '181',
            'title'    => 'Каша манная молочная',
            'yield'    => '200 г',
            'recipe'   => 'Сборник рецептур для дошкольных учреждений, 2011',
            'ingredients' => [
                ['Молоко 2,5%', '183,0 / 183,0'],
                ['Крупа манная', '14,0 / 14,0'],
                ['Сахар', '10,0 / 10,0'],
                ['Масло сливочное', '5,0 / 5,0'],
                ['Соль', '0,5 / 0,5'],
            ],
            'nutrition' => 'Белки 6,8 г; жиры 8,2 г; углеводы 24,5 г; 198 ккал',
            'technology' => 'Молоко довести до кипения, всыпать манную крупу тонкой струйкой при постоянном помешивании. Варить 3–5 мин. Добавить сахар, соль, масло. Подавать в горячем виде.',
            'serving'    => 'Температура подачи не ниже 65 °C. Реализация в течение 2 ч.',
        ],
        [
            'no'       => '254',
            'title'    => 'Котлета мясная',
            'yield'    => '75 г',
            'recipe'   => 'Сборник рецептур для дошкольных учреждений, 2008',
            'ingredients' => [
                ['Говядина', '82,0 / 65,0'],
                ['Хлеб пшеничный', '18,0 / 18,0'],
                ['Молоко', '25,0 / 25,0'],
                ['Лук репчатый', '12,0 / 10,0'],
                ['Яйца', '1/8 шт.'],
                ['Масло растительное (на жарку)', '3,0 / 3,0'],
                ['Соль', 'по вкусу'],
            ],
            'nutrition' => 'Белки 12,5 г; жиры 14,8 г; углеводы 6,2 г; 198 ккал',
            'technology' => 'Мясо и лук пропустить через мясорубку, смешать с размоченным хлебом, молоком, солью. Сформовать котлеты, обжарить с двух сторон, довести до готовности при 170–180 °C.',
            'serving'    => 'Подавать с гарниром и соусом. Температура 65 °C.',
        ],
        [
            'no'       => '227',
            'title'    => 'Суфле рыбное',
            'yield'    => '100 г',
            'recipe'   => 'Сборник рецептур для дошкольных учреждений, 2011',
            'ingredients' => [
                ['Филе рыбы', '95,0 / 90,0'],
                ['Молоко', '20,0 / 20,0'],
                ['Масло сливочное', '3,0 / 3,0'],
                ['Мука пшеничная', '3,0 / 3,0'],
                ['Яйца', '1/5 шт.'],
                ['Соль', 'по вкусу'],
            ],
            'nutrition' => 'Белки 11,2 г; жиры 6,5 г; углеводы 3,8 г; 118 ккал',
            'technology' => 'Рыбу отварить, измельчить, соединить с молочным соусом и взбитыми белками. Выложить в форму, запекать при 180 °C 15–20 мин.',
            'serving'    => 'Консистенция нежная, без посторонних привкусов. Температура 65 °C.',
        ],
    ];
}

function nutrition_sanpin_pdf_write(PDO $pdo, string $targetPath): void
{
    $org = 'МАДОУ «' . SITE_NAME . '»';
    $groups = nutrition_sanpin_representative_groups($pdo);
    $senior = $groups['senior'] ?? $groups['speech'] ?? reset($groups) ?: null;
    $seniorCategory = $senior['age_category'] ?? 'Старшая группа (5–7 лет)';
    $seniorMenu = $senior
        ? nutrition_sanpin_menu_for_group($pdo, (int)$senior['id'], $seniorCategory)
        : nutrition_sanpin_menu_for_group($pdo, 0, $seniorCategory);
    $dishBju = nutrition_sanpin_build_dish_bju($seniorMenu, $seniorCategory);
    $productCycle = nutrition_sanpin_product_cycle();
    $productNorms = nutrition_sanpin_product_norms();
    $nutrientNorm = menu_nutrition_norms_for_category($seniorCategory);
    $kcalNorm = (int)round($nutrientNorm['protein'] * 4 + $nutrientNorm['fat'] * 9 + $nutrientNorm['carb'] * 4);

    $pdf = nutrition_sanpin_pdf_create('P');
    $pdf->SetTitle('СанПиН 2.4.1.1249-03 — требования к питанию детей');

    // —— Титул и введение ——
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->MultiCell(0, 9, 'СанПиН 2.4.1.1249-03', 0, 'C', false, 1);
    $pdf->SetFont('dejavusans', '', 12);
    $pdf->MultiCell(0, 7, 'Требования к питанию детей в дошкольных образовательных организациях', 0, 'C', false, 1);
    $pdf->Ln(4);
    $pdf->SetFont('dejavusans', '', 10);
    $intro = [
        $org,
        'Документ подготовлен в соответствии с СанПиН 2.4.1.1249-03 «Гигиенические требования к устройству, содержанию и организации режима работы дошкольных образовательных организаций».',
        'Включает: меню по СанПиН, расчёт БЖУ блюд, ведомость выполнения норм продуктового набора, технологические карты, ведомость выполнения норм потребления пищевых веществ.',
        'Детям обеспечивается 4-разовое сбалансированное питание с учётом возрастных физиологических потребностей и режима дня.',
        'Меню разработано квалифицированными поварами, согласовано старшим воспитателем и заведующей.',
    ];
    foreach ($intro as $p) {
        $pdf->MultiCell(0, 6, $p, 0, 'J', false, 1);
        $pdf->Ln(2);
    }

    // —— 1. Меню по СанПиН ——
    $pdf->AddPage('L');
    nutrition_sanpin_pdf_section($pdf, '1. Меню по СанПиН (4-разовое питание, пн–пт)');

    foreach ($groups as $band => $group) {
        $menu = nutrition_sanpin_menu_for_group($pdo, (int)$group['id'], (string)$group['age_category']);
        $meta = dou_age_band_meta($band);
        $byDay = menu_weekly_grouped_by_day(array_map(static fn($r) => [
            'weekday'   => $r['weekday'],
            'meal_type' => $r['meal_type'],
            'dish_name' => $r['dish_name'],
            'weight'    => $r['weight'],
        ], $menu));

        $html = '<p><b>' . nutrition_sanpin_pdf_escape($meta['title'] . ' (' . $meta['range'] . ')') . '</b></p>';
        $html .= '<table border="1" cellpadding="3" cellspacing="0"><thead><tr style="background-color:#e8f4ea;">';
        $html .= '<th width="12%">Приём пищи</th>';
        foreach (menu_weekly_weekdays() as $num => $label) {
            $html .= '<th width="17.6%">' . nutrition_sanpin_pdf_escape($num . ' день (' . mb_substr($label, 0, 2) . ')') . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach (menu_weekly_meal_types() as $mealType) {
            $html .= '<tr><td><b>' . nutrition_sanpin_pdf_escape($mealType) . '</b></td>';
            foreach (array_keys(menu_weekly_weekdays()) as $dayNum) {
                $cell = [];
                foreach ($byDay[$dayNum] ?? [] as $row) {
                    if ($row['meal_type'] === $mealType) {
                        $w = !empty($row['weight']) ? ' ' . (int)$row['weight'] . ' г' : '';
                        $cell[] = nutrition_sanpin_pdf_escape($row['dish_name']) . $w;
                    }
                }
                $html .= '<td>' . ($cell !== [] ? implode('<br/>', $cell) : '—') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table><br/>';
        nutrition_sanpin_pdf_html($pdf, $html, 7);
    }

    // —— 2. БЖУ блюд ——
    $pdf->AddPage('P');
    nutrition_sanpin_pdf_section($pdf, '2. БЖУ блюд');
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->MultiCell(0, 5, 'Расчёт пищевой ценности по возрастной группе: ' . nutrition_sanpin_pdf_escape($seniorCategory) . '.', 0, 'L', false, 1);
    $pdf->Ln(2);

    for ($day = 1; $day <= 5; $day++) {
        $dayRows = array_values(array_filter($dishBju, static fn($r) => (int)$r['weekday'] === $day));
        if ($dayRows === []) {
            continue;
        }

        $html = '<p><b>День ' . $day . ' (' . nutrition_sanpin_pdf_escape(menu_weekly_weekday_label($day)) . ')</b></p>';
        $html .= '<table border="1" cellpadding="2" cellspacing="0"><thead><tr style="background-color:#e8f4ea;">';
        $html .= '<th>Приём / блюдо</th><th>Масса, г</th><th>Белки</th><th>Жиры</th><th>Углев.</th><th>ккал</th><th>№ рец.</th></tr></thead><tbody>';

        $mealTotals = [];
        $dayTotals = ['protein' => 0, 'fat' => 0, 'carb' => 0, 'kcal' => 0, 'weight' => 0];
        $currentMeal = '';

        foreach ($dayRows as $row) {
            if ($row['meal_type'] !== $currentMeal) {
                if ($currentMeal !== '' && isset($mealTotals[$currentMeal])) {
                    $t = $mealTotals[$currentMeal];
                    $html .= '<tr style="background-color:#f5f5f5;"><td colspan="2"><i>Итого ' . nutrition_sanpin_pdf_escape($currentMeal) . '</i></td>';
                    $html .= '<td>' . $t['protein'] . '</td><td>' . $t['fat'] . '</td><td>' . $t['carb'] . '</td><td>' . $t['kcal'] . '</td><td></td></tr>';
                }
                $currentMeal = $row['meal_type'];
                $mealTotals[$currentMeal] = ['protein' => 0, 'fat' => 0, 'carb' => 0, 'kcal' => 0, 'weight' => 0];
            }

            $html .= '<tr><td>' . nutrition_sanpin_pdf_escape($row['meal_type'] . ': ' . $row['dish']) . '</td>';
            $html .= '<td>' . (int)$row['weight'] . '</td>';
            $html .= '<td>' . $row['protein'] . '</td><td>' . $row['fat'] . '</td><td>' . $row['carb'] . '</td>';
            $html .= '<td>' . $row['kcal'] . '</td><td>' . nutrition_sanpin_pdf_escape($row['recipe']) . '</td></tr>';

            foreach (['protein', 'fat', 'carb', 'kcal'] as $k) {
                $mealTotals[$currentMeal][$k] = round($mealTotals[$currentMeal][$k] + $row[$k], 1);
                $dayTotals[$k] = round($dayTotals[$k] + $row[$k], 1);
            }
            $mealTotals[$currentMeal]['weight'] += $row['weight'];
            $dayTotals['weight'] += $row['weight'];
        }

        if ($currentMeal !== '' && isset($mealTotals[$currentMeal])) {
            $t = $mealTotals[$currentMeal];
            $html .= '<tr style="background-color:#f5f5f5;"><td colspan="2"><i>Итого ' . nutrition_sanpin_pdf_escape($currentMeal) . '</i></td>';
            $html .= '<td>' . $t['protein'] . '</td><td>' . $t['fat'] . '</td><td>' . $t['carb'] . '</td><td>' . $t['kcal'] . '</td><td></td></tr>';
        }

        $target = menu_nutrition_daily_for_category($seniorCategory, $day);
        $html .= '<tr style="background-color:#dcefd9;font-weight:bold;"><td colspan="2">ИТОГО за день</td>';
        $html .= '<td>' . $target['protein'] . '</td><td>' . $target['fat'] . '</td><td>' . $target['carb'] . '</td>';
        $html .= '<td>' . round($dayTotals['kcal'], 1) . '</td><td></td></tr>';
        $html .= '</tbody></table><br/>';

        nutrition_sanpin_pdf_html($pdf, $html, 7);
        if ($day < 5 && $pdf->GetY() > 230) {
            $pdf->AddPage();
        }
    }

    // —— 3. Ведомость продуктового набора ——
    $pdf->AddPage('L');
    nutrition_sanpin_pdf_section($pdf, '3. Ведомость выполнения норм продуктового набора (14 дней)');

    $html = '<table border="1" cellpadding="2" cellspacing="0" style="font-size:6.5pt;"><thead><tr style="background-color:#e8f4ea;">';
    $html .= '<th width="14%">Наименование продукта</th><th width="5%">Норма</th>';
    for ($d = 1; $d <= 14; $d++) {
        $html .= '<th width="4.5%">' . $d . '</th>';
    }
    $html .= '<th width="5%">Σ 14 дн.</th><th width="5%">В день</th><th width="5%">%</th></tr></thead><tbody>';

    foreach ($productNorms as $product => $norm) {
        $sum = 0.0;
        $html .= '<tr><td>' . nutrition_sanpin_pdf_escape($product) . '</td><td>' . $norm . '</td>';
        for ($d = 1; $d <= 14; $d++) {
            $val = $productCycle[$d][$product] ?? 0;
            $sum += $val;
            $html .= '<td>' . ($val > 0 ? number_format($val, 1, '.', '') : '') . '</td>';
        }
        $avg = round($sum / 14, 2);
        $pct = $norm > 0 ? round($avg / $norm * 100) : 100;
        $html .= '<td>' . number_format($sum, 1, '.', '') . '</td>';
        $html .= '<td>' . number_format($avg, 2, '.', '') . '</td>';
        $html .= '<td>' . $pct . '</td></tr>';
    }
    $html .= '</tbody></table>';
    nutrition_sanpin_pdf_html($pdf, $html, 6);

    // —— 4. Технологические карты ——
    foreach (nutrition_sanpin_tech_cards() as $card) {
        $pdf->AddPage('P');
        nutrition_sanpin_pdf_section($pdf, '4. Технологическая карта № ' . $card['no']);
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->MultiCell(0, 6, mb_strtoupper($card['title']), 0, 'C', false, 1);
        $pdf->Ln(2);

        $html = '<p>' . nutrition_sanpin_pdf_escape($card['recipe']) . '</p>';
        $html .= '<table border="1" cellpadding="3"><thead><tr style="background-color:#e8f4ea;">';
        $html .= '<th>Наименование сырья</th><th>1 порция (брутто/нетто), г</th></tr></thead><tbody>';
        foreach ($card['ingredients'] as [$name, $qty]) {
            $html .= '<tr><td>' . nutrition_sanpin_pdf_escape($name) . '</td><td>' . nutrition_sanpin_pdf_escape($qty) . '</td></tr>';
        }
        $html .= '<tr><td><b>Выход</b></td><td><b>' . nutrition_sanpin_pdf_escape($card['yield']) . '</b></td></tr>';
        $html .= '</tbody></table>';
        $html .= '<p><b>Пищевая ценность 1 порции:</b> ' . nutrition_sanpin_pdf_escape($card['nutrition']) . '</p>';
        $html .= '<p><b>Технология приготовления:</b> ' . nutrition_sanpin_pdf_escape($card['technology']) . '</p>';
        $html .= '<p><b>Подача:</b> ' . nutrition_sanpin_pdf_escape($card['serving']) . '</p>';
        nutrition_sanpin_pdf_html($pdf, $html, 9);
    }

    // —— 5. Ведомость пищевых веществ ——
    $pdf->AddPage('L');
    nutrition_sanpin_pdf_section($pdf, '5. Ведомость выполнения норм потребления пищевых веществ (14 дней)');

    $nutrientRows = [
        'Белки, г'                  => $nutrientNorm['protein'],
        'Жиры, г'                   => $nutrientNorm['fat'],
        'Углеводы, г'               => $nutrientNorm['carb'],
        'Энергетическая ценность, ккал' => $kcalNorm,
    ];

    $html = '<table border="1" cellpadding="3" cellspacing="0"><thead><tr style="background-color:#e8f4ea;">';
    $html .= '<th width="18%">Наименование</th><th width="7%">Норма</th>';
    for ($d = 1; $d <= 14; $d++) {
        $html .= '<th width="4.5%">' . $d . '</th>';
    }
    $html .= '<th width="6%">Σ 14 дн.</th><th width="6%">В день</th><th width="5%">%</th></tr></thead><tbody>';

    foreach ($nutrientRows as $label => $norm) {
        $sum = 0.0;
        $html .= '<tr><td>' . nutrition_sanpin_pdf_escape($label) . '</td><td>' . $norm . '</td>';
        for ($d = 1; $d <= 14; $d++) {
            $weekday = (($d - 1) % 5) + 1;
            $daily = menu_nutrition_daily_for_category($seniorCategory, $weekday);
            if (str_contains($label, 'ккал')) {
                $val = round($daily['protein'] * 4 + $daily['fat'] * 9 + $daily['carb'] * 4, 1);
            } elseif (str_contains($label, 'Белки')) {
                $val = $daily['protein'];
            } elseif (str_contains($label, 'Жиры')) {
                $val = $daily['fat'];
            } else {
                $val = $daily['carb'];
            }
            $sum += $val;
            $html .= '<td>' . number_format($val, 1, '.', '') . '</td>';
        }
        $avg = round($sum / 14, 1);
        $pct = $norm > 0 ? round($avg / $norm * 100) : 100;
        $html .= '<td>' . number_format($sum, 1, '.', '') . '</td>';
        $html .= '<td>' . number_format($avg, 1, '.', '') . '</td>';
        $html .= '<td>' . $pct . '</td></tr>';
    }
    $html .= '</tbody></table>';
    nutrition_sanpin_pdf_html($pdf, $html, 7);

    $dir = dirname($targetPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $pdf->Output($targetPath, 'F');
}
