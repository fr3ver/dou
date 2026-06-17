<?php

require_once __DIR__ . '/../includes/org_documents.php';

function admin_org_document_form_fields(?array $doc = null): void
{
    $categories = org_document_categories();
    $category = $doc['category'] ?? 'normative';
    ?>
    <div class="mb-3">
        <label class="form-label fw-semibold">Название <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control" required maxlength="255"
               value="<?= htmlspecialchars($doc['title'] ?? '') ?>"
               placeholder="Устав, лицензия, положение…">
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Категория <span class="text-danger">*</span></label>
        <select name="category" class="form-select" id="orgDocCategory">
            <?php foreach ($categories as $val => $label): ?>
            <option value="<?= $val ?>" <?= $category === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Файл<?= $doc ? '' : ' <span class="text-danger">*</span>' ?></label>
        <?php if (!empty($doc['file_path'])): ?>
            <div class="small text-muted mb-2">
                Текущий: <?= htmlspecialchars($doc['original_name']) ?>
                (<?= org_document_format_size(isset($doc['file_size']) ? (int)$doc['file_size'] : null) ?>)
                <?php org_document_render_file_actions($doc, '../', 'child-doc-action-link'); ?>
            </div>
        <?php endif; ?>
        <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" <?= $doc ? '' : 'required' ?>>
        <div class="form-text">PDF, DOC, DOCX, JPG, PNG, WEBP — до 20 МБ</div>
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" name="is_published" value="1" id="org_published" class="form-check-input"
               <?= ($doc['is_published'] ?? 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="org_published">Опубликовать на сайте</label>
    </div>
    <div class="mb-3 form-check" id="orgParentLkWrap">
        <input type="checkbox" name="show_in_parent_lk" value="1" id="org_parent_lk" class="form-check-input"
               <?= ($doc['show_in_parent_lk'] ?? 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="org_parent_lk">Показывать в личном кабинете родителя</label>
    </div>
    <script>
    document.getElementById('orgDocCategory')?.addEventListener('change', function() {
        const parentWrap = document.getElementById('orgParentLkWrap');
        const parentCb = document.getElementById('org_parent_lk');
        if (this.value === 'license') {
            parentCb.checked = true;
            parentCb.disabled = true;
        } else {
            parentCb.disabled = false;
        }
    });
    document.getElementById('orgDocCategory')?.dispatchEvent(new Event('change'));
    </script>
    <?php
}

function admin_org_document_collect_post(): array
{
    return [
        'title'            => trim($_POST['title'] ?? ''),
        'category'         => $_POST['category'] ?? 'other',
        'sort_order'       => 0,
        'is_published'     => isset($_POST['is_published']),
        'show_in_parent_lk'=> isset($_POST['show_in_parent_lk']),
    ];
}
