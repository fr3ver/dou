<?php

function admin_collect_child_allergy_ids(): array
{
    if (isset($_POST['has_allergies']) && (int)$_POST['has_allergies'] === 0) {
        return [];
    }

    $ids = [];
    foreach ($_POST['allergy_ids'] ?? [] as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }

    return array_values($ids);
}

function admin_render_allergy_checkboxes(array $allergies, array $selected_ids = []): void
{
    if (empty($allergies)) {
        echo '<p class="text-muted small mb-0">Справочник пуст. <a href="allergies.php">Добавить аллергены</a></p>';
        return;
    }

    echo '<select name="allergy_ids[]" id="allergy-select" class="form-select-allergies" multiple>';
    foreach ($allergies as $a) {
        $id = (int)$a['id'];
        $selected = in_array($id, $selected_ids, true) ? ' selected' : '';
        echo '<option value="' . $id . '"' . $selected . '>' . htmlspecialchars((string)$a['name']) . '</option>';
    }
    echo '</select>';
}

function admin_render_allergy_section(array $allergies, array $selected_ids = [], bool $embedded = false): void
{
    $hasAllergies = $selected_ids !== [];
    $wrapClass = $embedded ? 'child-health-subblock' : 'p-3 rounded-3 bg-soft-blue';
    ?>
    <div class="<?= $wrapClass ?>">
        <label class="form-label fw-semibold mb-2">
            <?php if ($embedded): ?><i class="bi bi-shield-exclamation me-1 text-primary"></i><?php endif; ?>Аллергии
        </label>
        <?php if ($embedded): ?>
        <p class="small text-muted mb-2">Отметьте аллергены из справочника — для меню и отчётов.</p>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-3 mb-2">
            <div class="form-check">
                <input type="radio" name="has_allergies" value="0" id="allergies_no"
                       class="form-check-input"<?= !$hasAllergies ? ' checked' : '' ?>>
                <label class="form-check-label" for="allergies_no">Аллергенов нет</label>
            </div>
            <div class="form-check">
                <input type="radio" name="has_allergies" value="1" id="allergies_yes"
                       class="form-check-input"<?= $hasAllergies ? ' checked' : '' ?>>
                <label class="form-check-label" for="allergies_yes">Есть аллергии</label>
            </div>
        </div>
        <div id="allergy-select-wrap"<?= $hasAllergies ? '' : ' style="display:none"' ?>>
            <?php admin_render_allergy_checkboxes($allergies, $selected_ids); ?>
        </div>
    </div>
    <?php
    admin_append_footer(<<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrap = document.getElementById('allergy-select-wrap');
    const select = document.getElementById('allergy-select');
    const no = document.getElementById('allergies_no');
    const yes = document.getElementById('allergies_yes');
    if (!wrap || !select || !no || !yes || typeof TomSelect === 'undefined') {
        return;
    }

    let allergyTs = null;

    function initAllergySelect() {
        if (select.tomselect) {
            return select.tomselect;
        }

        const holder = document.createElement('div');
        holder.className = 'allergy-select-wrap';
        select.parentNode.insertBefore(holder, select);
        holder.appendChild(select);

        allergyTs = new TomSelect(select, {
            plugins: ['remove_button', 'dropdown_input'],
            create: false,
            maxItems: null,
            placeholder: 'Выберите аллергены…',
            searchField: ['text'],
            sortField: { field: 'text', direction: 'asc' },
            hideSelected: true,
            closeAfterSelect: false,
            copyClassesToDropdown: false,
            render: {
                no_results: function () {
                    return '<div class="no-results px-3 py-2 text-muted">Ничего не найдено</div>';
                },
            },
        });

        const syncWidth = function () {
            const w = holder.getBoundingClientRect().width;
            if (w > 0 && allergyTs.dropdown) {
                allergyTs.dropdown.style.width = w + 'px';
            }
        };
        allergyTs.on('dropdown_open', syncWidth);
        window.addEventListener('resize', syncWidth);

        return allergyTs;
    }

    function setMode() {
        const show = yes.checked;
        wrap.style.display = show ? '' : 'none';

        if (!show) {
            if (allergyTs) {
                allergyTs.clear(true);
            } else {
                Array.from(select.options).forEach(function (opt) {
                    opt.selected = false;
                });
            }
            return;
        }

        initAllergySelect();
    }

    no.addEventListener('change', setMode);
    yes.addEventListener('change', setMode);

    if (yes.checked) {
        initAllergySelect();
    }
});
</script>
JS
    );
}
