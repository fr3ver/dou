<?php

require_once __DIR__ . '/../includes/svedeniya.php';
require_once __DIR__ . '/../includes/org_documents.php';
require_once __DIR__ . '/../includes/svedeniya_structure.php';
require_once __DIR__ . '/../includes/svedeniya_education.php';

/** @return array{intro: string, units: list<array<string, string>>} */
function admin_svedeniya_structure_from_section(array $section): array
{
    return svedeniya_structure_parse((string)($section['content'] ?? ''));
}

function admin_collect_education_content_from_post(): string
{
    $programs = [];
    $posted = $_POST['edu_program'] ?? [];

    foreach (['implemented', 'adapted'] as $key) {
        $programs[$key] = [];
        $rows = is_array($posted[$key] ?? null) ? $posted[$key] : [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $programs[$key][] = [
                'title'             => trim((string)($row['title'] ?? '')),
                'language'          => trim((string)($row['language'] ?? '')),
                'distance_learning' => trim((string)($row['distance_learning'] ?? '')),
                'file_id'           => (int)($row['file_id'] ?? 0),
            ];
        }
    }

    return svedeniya_education_encode(['programs' => $programs]);
}

/** @param list<array<string, mixed>> $files */
function admin_render_education_program_row(string $groupKey, int $idx, array $program, array $files): void
{
    $program = svedeniya_education_normalize_program($program);
    $groupTitle = svedeniya_education_groups()[$groupKey]['title'] ?? $groupKey;
    ?>
    <div class="border rounded p-3 bg-light edu-program-row">
        <div class="d-flex justify-content-between mb-2">
            <span class="small fw-semibold text-muted"><?= htmlspecialchars($groupTitle) ?> — карточка <?= $idx + 1 ?></span>
            <button type="button" class="btn btn-sm btn-outline-danger edu-program-remove" title="Удалить">&times;</button>
        </div>
        <div class="mb-2">
            <label class="form-label small fw-semibold mb-1">Название программы</label>
            <input type="text" name="edu_program[<?= htmlspecialchars($groupKey) ?>][<?= $idx ?>][title]" class="form-control"
                   value="<?= htmlspecialchars($program['title']) ?>" placeholder="Основная образовательная программа">
        </div>
        <div class="mb-2">
            <label class="form-label small fw-semibold mb-1">Язык обучения</label>
            <input type="text" name="edu_program[<?= htmlspecialchars($groupKey) ?>][<?= $idx ?>][language]" class="form-control"
                   value="<?= htmlspecialchars($program['language']) ?>">
        </div>
        <div class="mb-2">
            <label class="form-label small fw-semibold mb-1">Электронное обучение и дистанционные технологии</label>
            <textarea name="edu_program[<?= htmlspecialchars($groupKey) ?>][<?= $idx ?>][distance_learning]" class="form-control" rows="3"><?= htmlspecialchars($program['distance_learning']) ?></textarea>
        </div>
        <div class="mb-0">
            <label class="form-label small fw-semibold mb-1">Файл в карточке</label>
            <select name="edu_program[<?= htmlspecialchars($groupKey) ?>][<?= $idx ?>][file_id]" class="form-select">
                <option value="0">— без файла —</option>
                <?php foreach ($files as $file): ?>
                <option value="<?= (int)$file['id'] ?>" <?= (int)$program['file_id'] === (int)$file['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($file['title']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Загрузите файл ниже (категория «Файл программы»), затем выберите его здесь.</div>
        </div>
    </div>
    <?php
}

function admin_render_education_content_editor(PDO $pdo, array $section, string $slug): void
{
    $data = svedeniya_education_parse((string)($section['content'] ?? ''));
    $files = admin_svedeniya_section_files($pdo, $slug);
    ?>
    <p class="small text-muted mb-3">
        Карточки программ — выше. Файлы для блоков «Документы», «Численность обучающихся» и «Лицензии» — внизу страницы, у каждого блока своя кнопка «Добавить файл».
    </p>
    <?php foreach (['implemented', 'adapted'] as $groupKey):
        $group = svedeniya_education_groups()[$groupKey];
        $programs = $data['programs'][$groupKey] ?? [];
    ?>
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label fw-semibold mb-0"><?= htmlspecialchars($group['title']) ?></label>
            <button type="button" class="btn btn-sm btn-accent edu-program-add" data-group="<?= htmlspecialchars($groupKey) ?>">
                + Добавить программу
            </button>
        </div>
        <div class="edu-programs-list d-flex flex-column gap-3" data-group="<?= htmlspecialchars($groupKey) ?>">
            <?php foreach ($programs as $idx => $program): ?>
                <?php admin_render_education_program_row($groupKey, $idx, $program, $files); ?>
            <?php endforeach; ?>
        </div>
        <?php if ($programs === []): ?>
        <p class="small text-muted mb-0 edu-programs-empty" data-group="<?= htmlspecialchars($groupKey) ?>">Программ пока нет.</p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <template id="eduProgramTemplate">
        <?php admin_render_education_program_row('implemented', 0, [
            'title' => '',
            'language' => 'Русский',
            'distance_learning' => svedeniya_education_distance_default(),
            'file_id' => 0,
        ], $files); ?>
    </template>
    <?php
    admin_append_footer(<<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tpl = document.getElementById('eduProgramTemplate');
    if (!tpl) return;

    function renumberGroup(list) {
        const group = list.dataset.group;
        list.querySelectorAll('.edu-program-row').forEach(function (row, i) {
            row.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(
                    /edu_program\[[^\]]+\]\[\d+\]/,
                    'edu_program[' + group + '][' + i + ']'
                );
            });
            const label = row.querySelector('.small.fw-semibold');
            if (label) {
                const base = label.textContent.split(' — ')[0];
                label.textContent = base + ' — карточка ' + (i + 1);
            }
        });
        const empty = document.querySelector('.edu-programs-empty[data-group="' + group + '"]');
        if (empty) {
            empty.style.display = list.querySelectorAll('.edu-program-row').length ? 'none' : '';
        }
    }

    document.querySelectorAll('.edu-program-add').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const group = btn.dataset.group;
            const list = document.querySelector('.edu-programs-list[data-group="' + group + '"]');
            if (!list) return;
            const row = tpl.content.querySelector('.edu-program-row').cloneNode(true);
            list.appendChild(row);
            renumberGroup(list);
        });
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.edu-program-remove');
        if (!btn) return;
        const row = btn.closest('.edu-program-row');
        const list = btn.closest('.edu-programs-list');
        if (row && list) {
            row.remove();
            renumberGroup(list);
        }
    });
});
</script>
JS
    );
}

function admin_collect_structure_content_from_post(): string
{
    $units = [];
    $titles = $_POST['unit_title'] ?? [];
    $bodies = $_POST['unit_body'] ?? [];
    $urls = $_POST['unit_url'] ?? [];
    $fileIdsByUnit = $_POST['unit_file_ids'] ?? [];
    $count = max(
        count($titles),
        count($bodies),
        count($urls),
        is_array($fileIdsByUnit) ? count($fileIdsByUnit) : 0
    );

    for ($i = 0; $i < $count; $i++) {
        $fileIds = [];
        if (is_array($fileIdsByUnit[$i] ?? null)) {
            foreach ($fileIdsByUnit[$i] as $fileId) {
                $fileId = (int)$fileId;
                if ($fileId > 0) {
                    $fileIds[$fileId] = $fileId;
                }
            }
        }
        $unit = [
            'title'    => trim((string)($titles[$i] ?? '')),
            'body'     => trim((string)($bodies[$i] ?? '')),
            'file_ids' => array_values($fileIds),
        ];
        $url = trim((string)($urls[$i] ?? ''));
        if ($url !== '') {
            $unit['url'] = $url;
        }
        $units[] = $unit;
    }

    return svedeniya_structure_encode([
        'intro' => trim((string)($_POST['structure_intro'] ?? '')),
        'units' => $units,
    ]);
}

/** @param list<array<string, mixed>> $files */
function admin_render_accordion_unit_row(int $idx, array $unit, array $files): void
{
    $unit = svedeniya_structure_normalize_unit($unit);
    $selectedFileIds = array_flip($unit['file_ids']);
    ?>
    <div class="border rounded p-3 bg-light structure-unit-row">
        <div class="d-flex justify-content-between mb-2">
            <span class="small fw-semibold text-muted">Карточка <?= $idx + 1 ?></span>
            <button type="button" class="btn btn-sm btn-outline-danger structure-unit-remove" title="Удалить">&times;</button>
        </div>
        <div class="mb-2">
            <label class="form-label small fw-semibold mb-1">Заголовок</label>
            <input type="text" name="unit_title[]" class="form-control" placeholder="Название блока"
                   value="<?= htmlspecialchars($unit['title']) ?>">
        </div>
        <div class="mb-2">
            <label class="form-label small fw-semibold mb-1">Текст</label>
            <textarea name="unit_body[]" class="form-control" rows="4"
                      placeholder="Каждая строка — отдельная строка на сайте"><?= htmlspecialchars($unit['body']) ?></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label small fw-semibold mb-1">Внешняя ссылка</label>
            <input type="url" name="unit_url[]" class="form-control" placeholder="https://..."
                   value="<?= htmlspecialchars($unit['url']) ?>">
        </div>
        <div class="mb-0">
            <label class="form-label small fw-semibold mb-1">Файлы в карточке</label>
            <?php if ($files === []): ?>
            <p class="small text-muted mb-0">Сначала загрузите файлы в блоке «Документы» ниже.</p>
            <?php else: ?>
            <div class="border rounded p-2 bg-white unit-file-picker" style="max-height: 11rem; overflow-y: auto;">
                <?php foreach ($files as $file): ?>
                <label class="d-flex align-items-start gap-2 small mb-2 unit-file-option">
                    <input type="checkbox"
                           class="mt-1 unit-file-checkbox"
                           name="unit_file_ids[<?= $idx ?>][]"
                           value="<?= (int)$file['id'] ?>"
                           <?= isset($selectedFileIds[(int)$file['id']]) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($file['title']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="form-text">Можно выбрать несколько документов для одной карточки.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function admin_render_accordion_content_editor(PDO $pdo, array $section, string $slug): void
{
    $data = admin_svedeniya_structure_from_section($section);
    $units = $data['units'];
    $files = admin_svedeniya_section_files($pdo, $slug);
    ?>
    <div class="mb-3">
        <label class="form-label fw-semibold">Вводный текст</label>
        <textarea name="structure_intro" class="form-control" rows="3" placeholder="Краткое описание раздела (необязательно)"><?= htmlspecialchars($data['intro']) ?></textarea>
        <div class="form-text">Если раздел не применим — укажите: <code>Не предусмотрено</code></div>
    </div>
    <div class="mb-2 d-flex justify-content-end align-items-center">
        <button type="button" class="btn btn-sm btn-accent" id="addStructureUnit">+ Добавить карточку</button>
    </div>
    <div id="structureUnitsList" class="d-flex flex-column gap-3">
        <?php foreach ($units as $idx => $unit): ?>
            <?php admin_render_accordion_unit_row($idx, $unit, $files); ?>
        <?php endforeach; ?>
    </div>
    <?php if ($units === []): ?>
        <p id="structureUnitsEmpty" class="small text-muted mb-0">Карточек пока нет.</p>
    <?php endif; ?>
    <template id="structureUnitTemplate">
        <?php admin_render_accordion_unit_row(0, ['title' => '', 'body' => '', 'file_ids' => []], $files); ?>
    </template>
    <?php
    admin_append_footer(<<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('structureUnitsList');
    const tpl = document.getElementById('structureUnitTemplate');
    const addBtn = document.getElementById('addStructureUnit');
    if (!list || !tpl || !addBtn) return;
    const emptyHint = document.getElementById('structureUnitsEmpty');
    function renumberBlocks() {
        list.querySelectorAll('.structure-unit-row').forEach(function (row, i) {
            const label = row.querySelector('.small.fw-semibold');
            if (label) label.textContent = 'Карточка ' + (i + 1);
            row.querySelectorAll('.unit-file-checkbox').forEach(function (checkbox) {
                checkbox.name = 'unit_file_ids[' + i + '][]';
            });
        });
        if (emptyHint) emptyHint.style.display = list.querySelectorAll('.structure-unit-row').length ? 'none' : '';
    }
    renumberBlocks();
    addBtn.addEventListener('click', function () { list.appendChild(tpl.content.cloneNode(true)); renumberBlocks(); });
    list.addEventListener('click', function (e) {
        const btn = e.target.closest('.structure-unit-remove');
        if (!btn) return;
        const row = btn.closest('.structure-unit-row');
        if (row) { row.remove(); renumberBlocks(); }
    });
});
</script>
JS
    );
}

function admin_render_structure_content_editor(PDO $pdo, array $section, string $slug): void
{
    $labels = svedeniya_section_editor_labels($slug);
    $data = admin_svedeniya_structure_from_section($section);
    $units = $data['units'];
    ?>
    <div class="mb-3">
        <label class="form-label fw-semibold"><?= htmlspecialchars($labels['intro']) ?></label>
        <textarea name="structure_intro" class="form-control" rows="3" placeholder="Краткое описание раздела (необязательно)"><?= htmlspecialchars($data['intro']) ?></textarea>
        <div class="form-text">Если раздел не применим — укажите: <code>Не предусмотрено</code></div>
    </div>
    <div class="mb-2 d-flex justify-content-<?= $labels['units'] !== '' ? 'between' : 'end' ?> align-items-center">
        <?php if ($labels['units'] !== ''): ?>
        <label class="form-label fw-semibold mb-0"><?= htmlspecialchars($labels['units']) ?></label>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-accent" id="addStructureUnit"><?= htmlspecialchars($labels['add']) ?></button>
    </div>
    <p class="small text-muted mb-2">Создайте любое количество блоков: заголовок и текст (каждая строка — отдельная строка на сайте).</p>
    <div id="structureUnitsList" class="d-flex flex-column gap-3">
        <?php foreach ($units as $idx => $unit): ?>
        <div class="border rounded p-3 bg-light structure-unit-row">
            <div class="d-flex justify-content-between mb-2">
                <span class="small fw-semibold text-muted"><?= htmlspecialchars($labels['unit']) ?> <?= $idx + 1 ?></span>
                <button type="button" class="btn btn-sm btn-outline-danger structure-unit-remove" title="Удалить блок">&times;</button>
            </div>
            <div class="mb-2">
                <input type="text" name="unit_title[]" class="form-control" placeholder="Заголовок блока"
                       value="<?= htmlspecialchars($unit['title']) ?>">
            </div>
            <div>
                <textarea name="unit_body[]" class="form-control" rows="4" placeholder="Текст блока: ФИО, должность, адрес, телефон, e-mail — каждая строка с новой строки"><?= htmlspecialchars($unit['body'] ?? '') ?></textarea>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($units === []): ?>
        <p id="structureUnitsEmpty" class="small text-muted mb-0">Блоков пока нет. Нажмите «<?= htmlspecialchars($labels['add']) ?>».</p>
    <?php endif; ?>
    <template id="structureUnitTemplate">
        <div class="border rounded p-3 bg-light structure-unit-row">
            <div class="d-flex justify-content-between mb-2">
                <span class="small fw-semibold text-muted"><?= htmlspecialchars($labels['unit']) ?></span>
                <button type="button" class="btn btn-sm btn-outline-danger structure-unit-remove" title="Удалить блок">&times;</button>
            </div>
            <div class="mb-2">
                <input type="text" name="unit_title[]" class="form-control" placeholder="Заголовок блока">
            </div>
            <div>
                <textarea name="unit_body[]" class="form-control" rows="4" placeholder="Текст блока"></textarea>
            </div>
        </div>
    </template>
    <?php
    admin_append_footer(<<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('structureUnitsList');
    const tpl = document.getElementById('structureUnitTemplate');
    const addBtn = document.getElementById('addStructureUnit');
    if (!list || !tpl || !addBtn) return;

    const emptyHint = document.getElementById('structureUnitsEmpty');

    function renumberBlocks() {
        list.querySelectorAll('.structure-unit-row').forEach(function (row, i) {
            const label = row.querySelector('.small.fw-semibold');
            if (label) label.textContent = 'Блок ' + (i + 1);
        });
        if (emptyHint) {
            emptyHint.style.display = list.querySelectorAll('.structure-unit-row').length ? 'none' : '';
        }
    }

    addBtn.addEventListener('click', function () {
        list.appendChild(tpl.content.cloneNode(true));
        renumberBlocks();
    });

    list.addEventListener('click', function (e) {
        const btn = e.target.closest('.structure-unit-remove');
        if (!btn) return;
        const row = btn.closest('.structure-unit-row');
        if (row) {
            row.remove();
            renumberBlocks();
        }
    });
});
</script>
JS
    );
}

function admin_render_svedeniya_content_editor(PDO $pdo, array $section, string $slug): void
{
    if ($slug === 'education') {
        admin_render_education_content_editor($pdo, $section, $slug);
        return;
    }
    if ($slug === 'structure') {
        admin_render_structure_content_editor($pdo, $section, $slug);
        return;
    }
    admin_render_accordion_content_editor($pdo, $section, $slug);
}

/** @return list<array{cat: string, title: string}> */
function admin_section_file_panel_groups(string $slug): array
{
    if ($slug === 'documents') {
        $groups = [];
        foreach (org_document_categories() as $cat => $title) {
            $groups[] = ['cat' => $cat, 'title' => $title];
        }

        return $groups;
    }

    return [['cat' => 'other', 'title' => 'Документы']];
}

/** @return array<string, list<array<string, mixed>>> */
function admin_section_files_by_category(PDO $pdo, string $slug): array
{
    $byCat = [];
    foreach (admin_section_file_panel_groups($slug) as $group) {
        $byCat[$group['cat']] = [];
    }
    foreach (admin_svedeniya_section_files($pdo, $slug) as $file) {
        $cat = org_document_resolve_category($file, $slug);
        if (!isset($byCat[$cat])) {
            $byCat[$cat] = [];
        }
        $byCat[$cat][] = $file;
    }

    return $byCat;
}

function admin_render_section_files_panel(PDO $pdo, string $slug): void
{
    $byCat = admin_section_files_by_category($pdo, $slug);
    $groups = admin_section_file_panel_groups($slug);
    ?>
    <h3 class="h6 fw-semibold mb-3"><i class="bi bi-paperclip me-1"></i>Файлы по блокам на сайте</h3>
    <p class="small text-muted mb-3">Нажмите «Добавить файл» у нужного блока.</p>
    <?php foreach ($groups as $group): ?>
    <div class="border rounded p-3 mb-3 bg-light" id="section-block-<?= htmlspecialchars($group['cat']) ?>">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h4 class="h6 fw-semibold mb-0"><?= htmlspecialchars($group['title']) ?></h4>
            <a href="edit_svedeniya_section.php?slug=<?= urlencode($slug) ?>&amp;file_category=<?= urlencode($group['cat']) ?>#upload-form"
               class="btn btn-sm btn-accent">+ Добавить файл</a>
        </div>
        <?php admin_render_education_file_group_rows($pdo, $slug, $byCat[$group['cat']] ?? []); ?>
    </div>
    <?php endforeach;
}

function admin_svedeniya_section_files(PDO $pdo, string $slug): array
{
    return org_documents_for_section($pdo, $slug, false);
}

function admin_render_svedeniya_file_form(string $slug, ?array $doc = null, ?string $presetCategory = null): void
{
    $categories = org_document_categories_for_section($slug);
    if ($doc) {
        $category = org_document_resolve_category($doc, $slug);
    } elseif ($presetCategory !== null && $presetCategory !== '' && org_document_category_is_valid($presetCategory, $slug)) {
        $category = $presetCategory;
    } else {
        $category = match ($slug) {
            'education' => 'edu_docs',
            'nutrition' => 'nutrition_sanpin',
            default     => 'other',
        };
    }
    $showCategory = svedeniya_section_is_documents($slug) || $slug === 'education' || $slug === 'nutrition';
    ?>
    <div class="border rounded p-3 bg-light mb-3">
        <h3 class="h6 fw-semibold mb-3"><?= $doc ? 'Изменить файл' : 'Добавить файл' ?></h3>
        <?php if ($doc): ?>
            <input type="hidden" name="file_id" value="<?= (int)$doc['id'] ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label fw-semibold">Название <span class="text-danger">*</span></label>
            <input type="text" name="file_title" class="form-control" required maxlength="255"
                   value="<?= htmlspecialchars($doc['title'] ?? '') ?>"
                   placeholder="Название документа для сайта">
        </div>
        <?php if ($showCategory): ?>
        <div class="mb-3">
            <label class="form-label fw-semibold">Категория</label>
            <select name="file_category" class="form-select">
                <?php foreach ($categories as $val => $label): ?>
                <option value="<?= $val ?>" <?= $category === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
            <input type="hidden" name="file_category" value="other">
        <?php endif; ?>
        <div class="mb-3 d-flex flex-wrap gap-3">
            <div class="form-check">
                <input type="checkbox" name="file_is_published" value="1" id="file_pub" class="form-check-input"
                       <?= ($doc['is_published'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="file_pub">На сайте</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="file_show_parent" value="1" id="file_parent" class="form-check-input"
                       <?= ($doc['show_in_parent_lk'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="file_parent">В кабинете родителя</label>
            </div>
        </div>
        <div class="mb-0">
            <label class="form-label fw-semibold">Файл<?= $doc ? '' : ' <span class="text-danger">*</span>' ?></label>
            <?php if (!empty($doc['file_path'])): ?>
                <div class="small text-muted mb-2">
                    Текущий: <?= htmlspecialchars($doc['original_name']) ?>
                    <?php org_document_render_file_actions($doc, '../', 'child-doc-action-link'); ?>
                </div>
            <?php endif; ?>
            <input type="file" name="file_document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" <?= $doc ? '' : 'required' ?>>
            <div class="form-text">PDF, DOC, DOCX, JPG, PNG, WEBP — до 20 МБ</div>
        </div>
    </div>
    <?php
}

function admin_collect_svedeniya_file_post(string $slug): array
{
    return [
        'section_slug'     => $slug,
        'title'            => trim($_POST['file_title'] ?? ''),
        'category'         => $_POST['file_category'] ?? 'other',
        'sort_order'       => 0,
        'is_published'     => isset($_POST['file_is_published']),
        'show_in_parent_lk'=> isset($_POST['file_show_parent']),
    ];
}

/** @return array<string, list<array<string, mixed>>> */
function admin_education_files_by_category(PDO $pdo, string $slug): array
{
    $byCat = [];
    foreach (array_keys(org_document_education_categories()) as $cat) {
        $byCat[$cat] = [];
    }
    foreach (admin_svedeniya_section_files($pdo, $slug) as $file) {
        $cat = org_document_resolve_category($file, $slug);
        $byCat[$cat][] = $file;
    }

    return $byCat;
}

function admin_render_education_file_group_rows(PDO $pdo, string $slug, array $files): void
{
    if ($files === []) {
        echo '<p class="text-muted small mb-0">Файлов пока нет.</p>';
        return;
    }
    ?>
    <ul class="list-group list-group-flush mb-0">
        <?php foreach ($files as $file): ?>
        <li class="list-group-item px-0 d-flex flex-wrap align-items-center gap-2">
            <div class="flex-grow-1 min-w-0">
                <span class="fw-semibold"><?= htmlspecialchars($file['title']) ?></span>
                <?php org_document_render_file_actions($file, '../', 'child-doc-action-link'); ?>
            </div>
            <span class="small text-muted"><?= !empty($file['is_published']) ? 'На сайте' : 'Скрыт' ?></span>
            <a href="edit_svedeniya_section.php?slug=<?= urlencode($slug) ?>&amp;file_id=<?= (int)$file['id'] ?>#upload-form"
               class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <form action="delete_svedeniya_file.php" method="POST" class="d-inline" onsubmit="return confirm('Удалить файл?')">
                <input type="hidden" name="id" value="<?= (int)$file['id'] ?>">
                <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function admin_render_education_files_panel(PDO $pdo, string $slug): void
{
    $byCat = admin_education_files_by_category($pdo, $slug);
    $groups = [
        ['cat' => 'edu_docs', 'title' => 'Документы'],
        ['cat' => 'edu_enrollment', 'title' => 'Численность обучающихся и языки образования'],
        ['cat' => 'edu_license', 'title' => 'Лицензии на осуществление образовательной деятельности'],
        ['cat' => 'edu_program', 'title' => 'Файлы для карточек программ'],
    ];
    ?>
    <h3 class="h6 fw-semibold mb-3"><i class="bi bi-paperclip me-1"></i>Файлы по блокам на сайте</h3>
    <p class="small text-muted mb-3">Нажмите «Добавить файл» у нужного блока — категория подставится автоматически.</p>
    <?php foreach ($groups as $group): ?>
    <div class="border rounded p-3 mb-3 bg-light" id="edu-block-<?= htmlspecialchars($group['cat']) ?>">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h4 class="h6 fw-semibold mb-0"><?= htmlspecialchars($group['title']) ?></h4>
            <a href="edit_svedeniya_section.php?slug=<?= urlencode($slug) ?>&amp;file_category=<?= urlencode($group['cat']) ?>#upload-form"
               class="btn btn-sm btn-accent">+ Добавить файл</a>
        </div>
        <?php admin_render_education_file_group_rows($pdo, $slug, $byCat[$group['cat']] ?? []); ?>
    </div>
    <?php endforeach;
}

function admin_render_svedeniya_files_list(PDO $pdo, string $slug): void
{
    $files = admin_svedeniya_section_files($pdo, $slug);
    ?>
    <div class="mb-4">
        <h3 class="h6 fw-semibold mb-3">
            <i class="bi bi-paperclip me-1"></i>Файлы раздела
            <span class="badge bg-secondary ms-1"><?= count($files) ?></span>
        </h3>
        <?php if ($files === []): ?>
            <p class="text-muted small mb-0">Файлы не загружены. Добавьте PDF или изображения ниже.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Название</th>
                            <?php if (svedeniya_section_is_documents($slug) || $slug === 'education'): ?><th>Категория</th><?php endif; ?>
                            <th>Сайт</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file): ?>
                        <tr>
                            <td>
                                <span class="d-block"><?= htmlspecialchars($file['title']) ?></span>
                                <?php org_document_render_file_actions($file, '../', 'child-doc-action-link'); ?>
                            </td>
                            <?php if (svedeniya_section_is_documents($slug) || $slug === 'education'): ?>
                            <td class="small"><?= htmlspecialchars(org_document_category_label(org_document_resolve_category($file, $slug))) ?></td>
                            <?php endif; ?>
                            <td><?= !empty($file['is_published']) ? 'Да' : 'Нет' ?></td>
                            <td class="text-end text-nowrap">
                                <a href="edit_svedeniya_section.php?slug=<?= urlencode($slug) ?>&amp;file_id=<?= (int)$file['id'] ?>#files" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form action="delete_svedeniya_file.php" method="POST" class="d-inline"
                                      onsubmit="return confirm('Удалить файл?')">
                                    <input type="hidden" name="id" value="<?= (int)$file['id'] ?>">
                                    <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
