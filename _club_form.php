<?php

require_once '_club_helpers.php';

function admin_club_form_fields(PDO $pdo, ?array $club = null): void
{
    $teachers = admin_club_teachers($pdo);
    ?>
    <div class="mb-3">
        <label class="form-label fw-semibold">Название <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required maxlength="150"
               value="<?= htmlspecialchars($club['name'] ?? '') ?>"
               placeholder="Изобразительное искусство">
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Краткое описание</label>
        <textarea name="description" class="form-control" rows="2"
                  placeholder="Вводный абзац на странице кружка"><?= htmlspecialchars($club['description'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Особенности занятий</label>
        <textarea name="activities_features" class="form-control" rows="4"
                  placeholder="Цели, содержание и формы работы на занятиях"><?= htmlspecialchars($club['activities_features'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Образовательная программа</label>
        <textarea name="education_program" class="form-control" rows="3"
                  placeholder="Программа дополнительного образования"><?= htmlspecialchars($club['education_program'] ?? '') ?></textarea>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Возрастная категория</label>
            <input type="text" name="age_category" class="form-control" maxlength="50"
                   value="<?= htmlspecialchars($club['age_category'] ?? '') ?>"
                   placeholder="4–6 лет">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Расписание</label>
            <input type="text" name="schedule" class="form-control" maxlength="255"
                   value="<?= htmlspecialchars($club['schedule'] ?? '') ?>"
                   placeholder="Вт, Чт 16:00">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Руководитель</label>
            <select name="teacher_id" class="form-select">
                <option value="">— Не назначен —</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"
                        <?= (int)($club['teacher_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['full_name']) ?> (<?= htmlspecialchars($t['position']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Макс. участников</label>
            <input type="number" name="max_participants" class="form-control" min="1" max="999"
                   value="<?= isset($club['max_participants']) && $club['max_participants'] !== null
                       ? (int)$club['max_participants'] : '' ?>"
                   placeholder="15">
        </div>
    </div>
    <?php
}
