<?php

require_once __DIR__ . '/../menu_alternatives.php';
require_once __DIR__ . '/../menu_nutrition.php';

/**

 * Меню на неделю с выбором дня (пн–пт) и блоком БЖУ.

 *

 * @var array  $menu

 * @var string $weekFrom

 * @var string $weekTo

 * @var string $emptyMessage

 * @var array<int, array<int, array<string, mixed>>> $nutritionMap
 * @var list<array<string, mixed>> $children
 */

$emptyMessage ??= 'Меню ещё не настроено. Обратитесь к администратору.';

$nutritionMap ??= [];
$children ??= [];

$dateOptions = lk_menu_weekday_date_options($weekFrom, $weekTo);

?>

<?php if ($menu === []): ?>

    <div class="lk-panel text-muted"><?= htmlspecialchars($emptyMessage) ?></div>

<?php else:

    $menuByGroup = [];

    foreach ($menu as $row) {

        $menuByGroup[(int) $row['group_id']][] = $row;

    }

    foreach ($menuByGroup as $groupId => $groupMenu):

        $groupLabel = lk_menu_group_label($groupMenu[0]);

        $defaultDate = lk_menu_default_date($groupMenu, $dateOptions);

        $defaultWeekday = menu_weekday_from_date($defaultDate);

        $groupNutrition = $nutritionMap[$groupId] ?? [];
        $allergicChildren = menu_children_in_group_with_allergies($children, $groupId);
        $showAllergyHint = $allergicChildren !== [];

        ?>

        <div class="lk-panel lk-menu-card p-0 overflow-hidden mb-3" data-lk-menu-card>

            <div class="lk-menu-card-header">

                <span class="fw-semibold"><?= htmlspecialchars($groupLabel) ?></span>
                <span class="lk-menu-card-hint text-muted small d-none d-md-inline">Типовое меню на выбранный день недели</span>

                <label class="lk-menu-day-label mb-0">

                    <span class="visually-hidden">День недели</span>

                    <select class="form-select form-select-sm lk-menu-day-select" aria-label="День недели">

                        <?php foreach ($dateOptions as $value => $label): ?>

                            <option value="<?= htmlspecialchars($value) ?>"<?= $value === $defaultDate ? ' selected' : '' ?>>

                                <?= htmlspecialchars($label) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </label>

            </div>

            <?php if ($showAllergyHint): ?>
            <div class="lk-menu-allergy-hint px-3 pt-3 pb-0">
                <i class="bi bi-shield-exclamation me-1"></i>
                <?php
                $names = [];
                foreach ($allergicChildren as $child) {
                    $label = trim((string)($child['full_name'] ?? ''));
                    $allergy = trim((string)($child['allergy_names'] ?? ''));
                    if ($label !== '' && $allergy !== '') {
                        $names[] = $label . ' (' . $allergy . ')';
                    }
                }
                ?>
                Отмечены аллергии<?= $names !== [] ? ' у детей: ' . htmlspecialchars(implode('; ', $names)) : ' у детей группы' ?>.
                Для блюд с аллергенами ниже указаны альтернативы.
            </div>
            <?php endif; ?>

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0 lk-menu-table">

                    <thead>

                        <tr>

                            <th>Приём</th>

                            <th>Блюдо</th>

                            <th>Вес</th>

                            <th>Аллергены</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php
                        $rowsByDate = [];
                        foreach ($groupMenu as $row) {
                            $rowsByDate[$row['date'] ?? ''][] = $row;
                        }
                        foreach ($rowsByDate as $rowDate => $dateRows):
                            foreach (menu_weekly_merge_meal_rows($dateRows) as $row):
                            $hidden = $rowDate !== $defaultDate;
                            $rowRelevant = $showAllergyHint && menu_row_relevant_for_group_children($row, $children, $groupId);
                            $rowClass = trim(($hidden ? 'd-none ' : '') . ($rowRelevant ? 'lk-menu-row-allergy' : ''));
                        ?>
                            <tr data-menu-date="<?= htmlspecialchars($rowDate) ?>"<?= $rowClass !== '' ? ' class="' . htmlspecialchars($rowClass) . '"' : '' ?>>
                                <td><?= htmlspecialchars($row['meal_type']) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($row['dish_name']) ?></div>
                                    <?php if (menu_has_alternative($row)): ?>
                                    <div class="lk-menu-alt">
                                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                        Альтернатива: <?= htmlspecialchars((string)$row['alternative_dish']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (menu_nutrition_compact_label($row) !== ''): ?>
                                    <div class="lk-menu-bju-dish"><?= htmlspecialchars(menu_nutrition_compact_label($row)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($row['weight']) ? (int) $row['weight'] . ' г' : '—' ?></td>
                                <td class="small text-allergy"><?= !empty($row['allergies']) ? htmlspecialchars($row['allergies']) : '—' ?></td>
                            </tr>
                        <?php
                            endforeach;
                        endforeach;
                        ?>

                    </tbody>

                </table>

            </div>

            <div class="lk-menu-homemade-note px-3 py-3">
                <i class="bi bi-house-heart me-1"></i>
                <?= htmlspecialchars(menu_homemade_food_notice_text()) ?>
            </div>

            <?php if ($groupNutrition !== []): ?>

            <div class="lk-menu-bju-wrap" data-lk-menu-bju-wrap>

                <div class="lk-menu-bju-title">Суточные показатели БЖУ <span class="lk-menu-bju-auto">(сумма блюд)</span></div>

                <?php foreach (menu_weekly_weekdays() as $weekdayNum => $weekdayLabel):

                    $bju = $groupNutrition[$weekdayNum] ?? null;

                    if (!menu_nutrition_has_values($bju)) {

                        continue;

                    }

                    $isActive = $weekdayNum === $defaultWeekday;

                    ?>

                <div class="lk-menu-bju<?= $isActive ? '' : ' d-none' ?>"

                     data-menu-weekday="<?= (int)$weekdayNum ?>">

                    <div class="lk-menu-bju-item">

                        <span class="lk-menu-bju-label">Белки</span>

                        <span class="lk-menu-bju-value"><?= menu_nutrition_format_grams($bju['protein_g'] ?? null) ?></span>

                    </div>

                    <div class="lk-menu-bju-item">

                        <span class="lk-menu-bju-label">Жиры</span>

                        <span class="lk-menu-bju-value"><?= menu_nutrition_format_grams($bju['fat_g'] ?? null) ?></span>

                    </div>

                    <div class="lk-menu-bju-item">

                        <span class="lk-menu-bju-label">Углеводы</span>

                        <span class="lk-menu-bju-value"><?= menu_nutrition_format_grams($bju['carb_g'] ?? null) ?></span>

                    </div>

                </div>

                <?php endforeach; ?>

            </div>

            <?php endif; ?>

        </div>

    <?php endforeach; ?>

<?php endif; ?>


