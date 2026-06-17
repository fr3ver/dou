<?php

require_once __DIR__ . '/../includes/menu_weekly.php';
require_once __DIR__ . '/_group_helpers.php';

function admin_menu_form_fields(?array $item = null, ?int $defaultGroupId = null, ?int $defaultWeekday = null): void
{
    global $pdo;
    $weekdays = menu_weekly_weekdays();
    $meals = menu_weekly_meal_types();
    $groupId = (int)($item['group_id'] ?? $defaultGroupId ?? 0);
    $weekday = (int)($item['weekday'] ?? $defaultWeekday ?? (int)date('N'));
    if ($weekday > 5) {
        $weekday = 1;
    }
    ?>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Группа <span class="text-danger">*</span></label>
            <select name="group_id" class="form-select-groups" required>
                <?php admin_render_group_select_options($pdo, $groupId, true); ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">День недели <span class="text-danger">*</span></label>
            <select name="weekday" class="form-select" required>
                <?php foreach ($weekdays as $num => $label): ?>
                    <option value="<?= $num ?>" <?= $weekday === $num ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Приём пищи <span class="text-danger">*</span></label>
            <select name="meal_type" class="form-select" required>
                <?php foreach ($meals as $meal): ?>
                    <option value="<?= $meal ?>" <?= ($item['meal_type'] ?? '') === $meal ? 'selected' : '' ?>><?= $meal ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Название блюда <span class="text-danger">*</span></label>
        <input type="text" name="dish_name" class="form-control" required maxlength="200"
               value="<?= htmlspecialchars($item['dish_name'] ?? '') ?>">
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Вес (г)</label>
            <input type="number" name="weight" class="form-control" min="0"
                   value="<?= htmlspecialchars($item['weight'] ?? '') ?>">
        </div>
        <div class="col-md-8 mb-3">
            <label class="form-label fw-semibold">Аллергены в блюде</label>
            <input type="text" name="allergies" class="form-control"
                   value="<?= htmlspecialchars($item['allergies'] ?? '') ?>" placeholder="молоко, глютен...">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Белки, г</label>
            <input type="number" name="protein_g" class="form-control" min="0" step="0.1"
                   value="<?= htmlspecialchars((string)($item['protein_g'] ?? '')) ?>" placeholder="в блюде">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Жиры, г</label>
            <input type="number" name="fat_g" class="form-control" min="0" step="0.1"
                   value="<?= htmlspecialchars((string)($item['fat_g'] ?? '')) ?>" placeholder="в блюде">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Углеводы, г</label>
            <input type="number" name="carb_g" class="form-control" min="0" step="0.1"
                   value="<?= htmlspecialchars((string)($item['carb_g'] ?? '')) ?>" placeholder="в блюде">
        </div>
    </div>
    <p class="small text-muted">Укажите БЖУ порции блюда (из техкарты). Суточные показатели в кабинете родителя считаются автоматически — суммой всех блюд за день.</p>
    <div class="mb-3">
        <label class="form-label fw-semibold">Альтернатива при аллергии</label>
        <input type="text" name="alternative_dish" class="form-control" maxlength="200"
               value="<?= htmlspecialchars($item['alternative_dish'] ?? '') ?>"
               placeholder="Например: каша на воде (если основное блюдо — каша молочная)">
        <div class="form-text">Укажите замену для детей с аллергией на аллергены этого блюда.</div>
    </div>
    <?php
}
