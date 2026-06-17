<?php
require_once '_auth.php';
require_once '_group_helpers.php';
require_once __DIR__ . '/../includes/menu_weekly.php';
require_once __DIR__ . '/../includes/menu_nutrition.php';
require_once __DIR__ . '/../includes/roles.php';

$groups = admin_groups_sorted_by_band($pdo);
$groupId = (int)($_GET['group_id'] ?? ($groups[0]['id'] ?? 0));

$selectedGroup = null;
foreach ($groups as $g) {
    if ((int)$g['id'] === $groupId) {
        $selectedGroup = $g;
        break;
    }
}

$items = $groupId > 0 ? menu_weekly_for_group($pdo, $groupId) : [];
$nutritionByDay = $groupId > 0 ? menu_daily_nutrition_for_group($pdo, $groupId) : [];
$byDay = menu_weekly_grouped_by_day($items);
$canEditMenu = role_can_edit_menu((int)($_SESSION['role_id'] ?? 0));
$todayWeekday = (int)date('N');
$todayItems = ($todayWeekday <= 5 && $groupId > 0) ? menu_weekly_today_for_group($pdo, $groupId) : [];
$weekdayLabels = menu_weekly_weekdays();

admin_page_start('Меню питания', 'Утверждённое меню на неделю (пн–пт). Заполняют заведующая и старший воспитатель.');
?>

<?php if ($groups === []): ?>
    <div class="alert alert-warning border-0 shadow-sm">
        Сначала создайте группы, затем настройте меню.
    </div>
<?php else: ?>

<form method="GET" class="row g-2 align-items-end mb-4">
    <div class="col-auto">
        <label class="form-label fw-semibold">Группа</label>
        <select name="group_id" class="form-select-groups" onchange="this.form.submit()">
            <?php admin_render_group_select_options($pdo, $groupId); ?>
        </select>
    </div>
    <div class="col-auto">
        <a href="add_menu.php?group_id=<?= $groupId ?>" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i>Добавить блюдо
        </a>
    </div>
</form>

<?php if ($selectedGroup): ?>
<div class="card border-0 shadow-sm mb-4 border-start border-4 border-success">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <span class="badge bg-soft-green text-dark mb-2">Утверждённое меню</span>
                <h2 class="h5 fw-semibold mb-1"><?= htmlspecialchars($selectedGroup['name']) ?></h2>
                <?php if (!empty($selectedGroup['age_category'])): ?>
                    <p class="text-muted small mb-0">
                        <?= htmlspecialchars(admin_group_age_display($selectedGroup['age_category'])) ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <div class="admin-stat-value fs-4"><?= count($items) ?></div>
                <div class="admin-stat-label">блюд на неделю</div>
            </div>
        </div>
        <p class="small text-muted mb-0 mt-3">
            <?= htmlspecialchars(menu_chef_notice_text()) ?>
            Меню задаётся по дням недели и автоматически показывается родителям каждую соответствующую неделю.
            Укажите <strong>БЖУ в каждом блюде</strong> — суточные показатели считаются автоматически (сумма по дню) и отображаются в кабинете родителя.
            <a href="edit_svedeniya_section.php?slug=nutrition#files" class="ms-1">Документы по питанию (СанПиН)</a>
        </p>
    </div>
</div>

<?php if ($todayWeekday <= 5): ?>
    <?php admin_collapse_start('menu-today', 'Сегодня, ' . menu_weekly_weekday_label($todayWeekday) . ' — что видят в кабинете', true, (string)count($todayItems)); ?>
        <?php if ($todayItems === []): ?>
            <p class="text-muted mb-0 py-2">На сегодня блюда не заданы. Добавьте позиции для <?= htmlspecialchars(menu_weekly_weekday_label($todayWeekday)) ?> ниже.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Приём</th><th>Блюдо</th><th>Вес</th><th>Аллергены</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (menu_weekly_merge_meal_rows($todayItems) as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['meal_type']) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($row['dish_name']) ?></td>
                            <td><?= !empty($row['weight']) ? (int)$row['weight'] . ' г' : '—' ?></td>
                            <td class="small text-allergy"><?= htmlspecialchars($row['allergies'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php admin_collapse_end(); ?>
<?php endif; ?>

<?php foreach ($weekdayLabels as $dayNum => $dayLabel): ?>
    <?php $dayItems = $byDay[$dayNum] ?? []; ?>
    <?php admin_collapse_start(
        'menu-day-' . $dayNum,
        $dayLabel,
        $dayNum === ($todayWeekday <= 5 ? $todayWeekday : 1),
        (string)count($dayItems)
    ); ?>
        <?php
        $bju = $nutritionByDay[$dayNum] ?? null;
        $bjuNorms = menu_nutrition_daily_for_category($selectedGroup['age_category'] ?? null, $dayNum);
        $dayDishRows = $dayItems;
        $bjuFromDishes = menu_nutrition_sum_rows($dayDishRows);
        if ($bjuFromDishes['has_values']) {
            $bju = [
                'protein_g' => $bjuFromDishes['protein_g'],
                'fat_g' => $bjuFromDishes['fat_g'],
                'carb_g' => $bjuFromDishes['carb_g'],
                'source' => 'dishes',
            ];
        }
        ?>
        <div class="border rounded p-3 mb-3 bg-light">
            <div class="small fw-semibold mb-2">Суточные показатели БЖУ (<?= htmlspecialchars($dayLabel) ?>)</div>
            <p class="small text-muted mb-2">
                Норма для возраста: белки <?= (int)$bjuNorms['protein'] ?> г, жиры <?= (int)$bjuNorms['fat'] ?> г, углеводы <?= (int)$bjuNorms['carb'] ?> г.
            </p>
            <?php if (menu_nutrition_has_values($bju)): ?>
            <p class="small mb-0">
                <span class="badge bg-soft-green text-dark me-1">авто</span>
                Итого за день: белки <?= menu_nutrition_format_grams($bju['protein_g'] ?? null) ?>,
                жиры <?= menu_nutrition_format_grams($bju['fat_g'] ?? null) ?>,
                углеводы <?= menu_nutrition_format_grams($bju['carb_g'] ?? null) ?>
                (сумма блюд).
            </p>
            <?php else: ?>
            <p class="small text-muted mb-0">Заполните БЖУ в блюдах этого дня — суточные показатели появятся автоматически.</p>
            <?php endif; ?>
        </div>
        <?php if ($dayItems === []): ?>
            <p class="text-muted mb-0 py-2">
                Нет блюд.
                <a href="add_menu.php?group_id=<?= $groupId ?>&weekday=<?= $dayNum ?>">Добавить</a>
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead class="table-light">
                        <tr>
                            <th>Приём пищи</th>
                            <th>Блюдо</th>
                            <th>Вес (г)</th>
                            <th>Аллергены</th>
                            <th>Альтернатива</th>
                            <th>БЖУ (в приёме)</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (menu_weekly_merge_meal_rows($dayItems) as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['meal_type']) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($m['dish_name']) ?></td>
                            <td><?= $m['weight'] ? (int)$m['weight'] : '—' ?></td>
                            <td class="small text-allergy"><?= htmlspecialchars($m['allergies'] ?? '—') ?></td>
                            <td class="small"><?= htmlspecialchars($m['alternative_dish'] ?? '—') ?></td>
                            <td class="small"><?= menu_nutrition_compact_label($m) !== '' ? htmlspecialchars(menu_nutrition_compact_label($m)) : '—' ?></td>
                            <td class="text-end">
                                <a href="edit_menu.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-primary-dou"><i class="bi bi-pencil"></i></a>
                                <form action="delete_menu.php" method="POST" class="d-inline" onsubmit="return confirm('Удалить блюдо?')">
                                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php admin_collapse_end(); ?>
<?php endforeach; ?>

<?php endif; ?>
<?php endif; ?>

<?php admin_page_end(); ?>
