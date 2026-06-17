<?php



require_once '_group_helpers.php';



function admin_group_form_fields(PDO $pdo, ?array $group = null): void

{

    $teachers = $pdo->query("

        SELECT u.id, u.full_name, e.position, e.group_id

        FROM users u

        JOIN employees e ON e.user_id = u.id

        WHERE e.position IN ('Воспитатель', 'Логопед')

        ORDER BY u.full_name

    ")->fetchAll(PDO::FETCH_ASSOC);



    $selectedStaff = $group ? admin_group_staff_user_ids($pdo, (int)$group['id']) : [];

    ?>

    <div class="mb-3">

        <label class="form-label fw-semibold">Название <span class="text-danger">*</span></label>

        <input type="text" name="name" class="form-control" required maxlength="150"

               value="<?= htmlspecialchars($group['name'] ?? '') ?>"

               placeholder='Группа №10 «Радуга»'>

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">Краткое описание</label>

        <textarea name="description" class="form-control" rows="2"

                  placeholder="Вводный абзац на странице группы"><?= htmlspecialchars($group['description'] ?? '') ?></textarea>

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">Особенности занятий</label>

        <textarea name="activities_features" class="form-control" rows="4"

                  placeholder="Режим, направления развития, специальные занятия"><?= htmlspecialchars($group['activities_features'] ?? '') ?></textarea>

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">Образовательная программа</label>

        <textarea name="education_program" class="form-control" rows="4"

                  placeholder="Название и содержание реализуемой программы"><?= htmlspecialchars($group['education_program'] ?? '') ?></textarea>

    </div>

    <div class="row">

        <div class="col-md-6 mb-3">

            <label class="form-label fw-semibold">Возрастная категория</label>

            <select name="age_category" class="form-select">

                <option value=""><?= $group ? '—' : 'Выберите…' ?></option>

                <?php admin_render_group_age_options($group['age_category'] ?? null); ?>

            </select>

        </div>

        <div class="col-md-6 mb-3">

            <label class="form-label fw-semibold">Вместимость</label>

            <input type="number" name="capacity" class="form-control"

                   value="<?= isset($group['capacity']) ? (int) $group['capacity'] : 25 ?>" min="1" max="99">

        </div>

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">Режим пребывания</label>

        <select name="care_mode" class="form-select">

            <?php
            require_once __DIR__ . '/../includes/parent_fee.php';
            $selectedCare = parent_fee_normalize_care_mode($group['care_mode'] ?? '12h');
            foreach (parent_fee_care_mode_labels() as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>"<?= $selectedCare === $value ? ' selected' : '' ?>>

                <?= htmlspecialchars($label) ?>

            </option>

            <?php endforeach; ?>

        </select>

        <div class="form-text">Для расчёта родительской платы (постановление № 162 от 27.06.2024)</div>

    </div>

    <div class="mb-4">
        <label class="form-label fw-semibold"><i class="bi bi-person-badge me-1"></i>Педагоги группы</label>
        <div class="border rounded-3 p-3 bg-light-subtle">
            <?php if ($teachers === []): ?>
                <p class="text-muted small mb-0">Нет воспитателей и логопедов. <a href="add_employee.php">Добавить сотрудника</a></p>
            <?php else: ?>
                <?php foreach ($teachers as $t):
                    $gid = (int)($t['group_id'] ?? 0);
                    $currentGroupId = $group ? (int)$group['id'] : 0;
                    $inOther = $gid > 0 && ($currentGroupId === 0 || $gid !== $currentGroupId);
                    $isChecked = in_array((int)$t['id'], $selectedStaff, true);
                    $inputId = 'staff-user-' . (int)$t['id'];
                ?>
                <div class="form-check mb-2">
                    <input type="checkbox" name="staff_user_ids[]" value="<?= (int)$t['id'] ?>"
                           id="<?= $inputId ?>" class="form-check-input"
                           <?= $isChecked ? 'checked' : '' ?>
                           <?= $inOther ? 'disabled' : '' ?>>
                    <label class="form-check-label <?= $inOther ? 'text-muted' : '' ?>" for="<?= $inputId ?>">
                        <?= htmlspecialchars($t['full_name']) ?>
                        <span class="text-muted">(<?= htmlspecialchars($t['position']) ?>)</span>
                        <?php if ($inOther): ?>
                            <span class="badge bg-secondary ms-1">занят в другой группе</span>
                        <?php endif; ?>
                    </label>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php

}


