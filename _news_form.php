<?php



require_once '_news_helpers.php';

require_once __DIR__ . '/../includes/entity_groups.php';



function admin_news_form_fields(?array $news = null): void

{

    global $pdo;

    $roles = admin_news_target_roles();

    $forAllGroups = true;

    $selectedGroupIds = [];

    if ($news) {

        $selectedGroupIds = entity_groups_from_row($news);
        $forAllGroups = $selectedGroupIds === [];

    }

    ?>

    <div class="mb-3">

        <label class="form-label fw-semibold">Заголовок <span class="text-danger">*</span></label>

        <input type="text" name="title" class="form-control" required maxlength="150"

               value="<?= htmlspecialchars($news['title'] ?? '') ?>">

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">Текст <span class="text-danger">*</span></label>

        <textarea name="content" class="form-control" rows="8" required><?= htmlspecialchars($news['content'] ?? '') ?></textarea>

    </div>

    <div class="row">

        <div class="col-md-6 mb-3">

            <label class="form-label fw-semibold">Дата публикации <span class="text-danger">*</span></label>

            <input type="date" name="publish_date" class="form-control" required

                   value="<?= htmlspecialchars($news['publish_date'] ?? date('Y-m-d')) ?>">

        </div>

        <div class="col-md-6 mb-3">

            <label class="form-label fw-semibold">Аудитория</label>

            <select name="target_role" class="form-select">

                <?php foreach ($roles as $val => $label): ?>

                    <option value="<?= $val ?>" <?= ($news['target_role'] ?? 'all') === $val ? 'selected' : '' ?>><?= $label ?></option>

                <?php endforeach; ?>

            </select>

        </div>

    </div>

    <div class="mb-3 form-check">

        <input type="checkbox" name="for_all_groups" value="1" id="news_for_all" class="form-check-input"

               <?= $forAllGroups ? 'checked' : '' ?>>

        <label class="form-check-label" for="news_for_all">Для всех групп</label>

    </div>

    <div class="mb-3" id="newsGroupsWrap">

        <label class="form-label fw-semibold">Группы</label>

        <p class="small text-muted mb-2">Можно выбрать несколько групп</p>

        <?php admin_render_group_checkbox_list($pdo, $selectedGroupIds, 'newsGroupCheckboxes', $forAllGroups); ?>

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">Изображение</label>

        <?php if (!empty($news['image_url'])): ?>

            <div class="mb-2">

                <img src="../<?= htmlspecialchars($news['image_url']) ?>" alt="" class="rounded border" style="max-height: 120px; object-fit: cover;">

            </div>

            <div class="form-check mb-2">

                <input type="checkbox" name="remove_image" value="1" id="remove_image" class="form-check-input">

                <label class="form-check-label" for="remove_image">Удалить текущее изображение</label>

            </div>

        <?php endif; ?>

        <input type="file" name="image" class="form-control" accept="image/*">

    </div>

    <script>

    document.getElementById('news_for_all')?.addEventListener('change', function() {

        document.querySelectorAll('#newsGroupCheckboxes input[type=checkbox]').forEach(function(cb) {

            cb.disabled = this.checked;

            if (this.checked) cb.checked = false;

        }, this);

    });

    document.getElementById('news_for_all')?.dispatchEvent(new Event('change'));

    </script>

    <?php

}

