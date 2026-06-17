<?php

require_once '_auth.php';

require_once __DIR__ . '/../includes/entity_groups.php';



function admin_event_form_fields(?array $event = null): void

{

    global $pdo;

    $statuses = ['active' => 'Активно', 'finished' => 'Завершено', 'cancelled' => 'Отменено', 'postponed' => 'Перенесено'];

    $today = $pdo->query('SELECT CURDATE()')->fetchColumn();

    $selectedGroupIds = [];

    if ($event) {

        $selectedGroupIds = entity_groups_from_row($event);

    }

    ?>

    <div class="mb-3">

        <label class="form-label fw-semibold">Название <span class="text-danger">*</span></label>

        <input type="text" name="title" class="form-control" required maxlength="150"

               value="<?= htmlspecialchars($event['title'] ?? '') ?>">

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">Описание</label>

        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>

    </div>

    <div class="row">

        <div class="col-md-4 mb-3">

            <label class="form-label fw-semibold">Дата <span class="text-danger">*</span></label>

            <input type="date" name="event_date" class="form-control" required

                   value="<?= htmlspecialchars($event['event_date'] ?? $today) ?>">

        </div>

        <div class="col-md-4 mb-3">

            <label class="form-label fw-semibold">Время</label>

            <input type="time" name="event_time" class="form-control"

                   value="<?= !empty($event['event_time']) ? substr($event['event_time'], 0, 5) : '' ?>">

        </div>

        <div class="col-md-4 mb-3">

            <label class="form-label fw-semibold">Место</label>

            <input type="text" name="location" class="form-control"

                   value="<?= htmlspecialchars($event['location'] ?? '') ?>" placeholder="Актовый зал">

        </div>

    </div>

    <div class="row">

        <div class="col-md-6 mb-3">

            <label class="form-label fw-semibold">Статус</label>

            <select name="status" class="form-select">

                <?php foreach ($statuses as $val => $label): ?>

                    <option value="<?= $val ?>" <?= ($event['status'] ?? 'active') === $val ? 'selected' : '' ?>><?= $label ?></option>

                <?php endforeach; ?>

            </select>

        </div>

    </div>

    <div class="mb-3 form-check">

        <input type="checkbox" name="for_all_groups" value="1" id="for_all" class="form-check-input"

               <?= !empty($event['for_all_groups']) ? 'checked' : '' ?>>

        <label class="form-check-label" for="for_all">Для всех групп</label>

    </div>

    <div class="mb-3" id="eventGroupsWrap">

        <label class="form-label fw-semibold">Группы</label>

        <p class="small text-muted mb-2">Можно выбрать несколько групп</p>

        <?php admin_render_group_checkbox_list($pdo, $selectedGroupIds, 'eventGroupCheckboxes', !empty($event['for_all_groups'])); ?>

    </div>

    <div class="mb-3">

        <label class="form-label fw-semibold">Перенесено на</label>

        <input type="date" name="postponed_to" class="form-control" id="postponedDate"

               value="<?= htmlspecialchars($event['postponed_to'] ?? '') ?>">

    </div>

    <script>

    document.querySelector('[name=status]')?.addEventListener('change', function() {

        const b = document.getElementById('postponedDate');

        if (b) b.closest('.mb-3').style.display = this.value === 'postponed' ? 'block' : 'none';

    });

    document.querySelector('[name=status]')?.dispatchEvent(new Event('change'));

    document.getElementById('for_all')?.addEventListener('change', function() {

        document.querySelectorAll('#eventGroupCheckboxes input[type=checkbox]').forEach(function(cb) {

            cb.disabled = this.checked;

            if (this.checked) cb.checked = false;

        }, this);

    });

    document.getElementById('for_all')?.dispatchEvent(new Event('change'));

    </script>

    <?php

}

