<?php

/** @param list<array<string, string>> $units */
function svedeniya_content_json(string $intro, array $units = []): string
{
    return svedeniya_structure_encode(['intro' => $intro, 'units' => $units]);
}

/** @return array{intro: string, units: list<array<string, string>>} */
function svedeniya_structure_parse(string $content): array
{
    $content = trim($content);
    if ($content === '' || $content === 'Не предусмотрено') {
        return ['intro' => $content === 'Не предусмотрено' ? 'Не предусмотрено' : '', 'units' => []];
    }

    if ($content[0] === '{') {
        $data = json_decode($content, true);
        if (is_array($data)) {
            $units = [];
            foreach ($data['units'] ?? [] as $unit) {
                if (!is_array($unit)) {
                    continue;
                }
                $units[] = svedeniya_structure_normalize_unit($unit);
            }

            return [
                'intro' => trim((string)($data['intro'] ?? '')),
                'units' => $units,
            ];
        }
    }

    return ['intro' => $content, 'units' => []];
}

/** @param array<string, mixed> $unit @return list<int> */
function svedeniya_structure_unit_file_ids(array $unit): array
{
    if (isset($unit['file_ids']) && is_array($unit['file_ids'])) {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $unit['file_ids']),
            static fn(int $id): bool => $id > 0
        )));

        return $ids;
    }

    $single = max(0, (int)($unit['file_id'] ?? 0));

    return $single > 0 ? [$single] : [];
}

/** @param array<string, mixed> $unit */
function svedeniya_structure_normalize_unit(array $unit): array
{
    $title = trim((string)($unit['title'] ?? ''));
    $body = trim((string)($unit['body'] ?? ''));

    if ($body === '') {
        $lines = [];
        if (!empty($unit['head_name'])) {
            $lines[] = trim((string)$unit['head_name']);
        }
        if (!empty($unit['head_role'])) {
            $lines[] = trim((string)$unit['head_role']);
        }
        if (!empty($unit['address'])) {
            $lines[] = trim((string)$unit['address']);
        }
        if (!empty($unit['email'])) {
            $lines[] = trim((string)$unit['email']);
        }
        $body = implode("\n", $lines);
    }

    $fileIds = svedeniya_structure_unit_file_ids($unit);
    $url = trim((string)($unit['url'] ?? ''));
    if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
        $url = '';
    }

    return [
        'title'    => $title,
        'body'     => $body,
        'url'      => $url,
        'file_ids' => $fileIds,
        'file_id'  => $fileIds[0] ?? 0,
    ];
}

function svedeniya_unit_is_external_link(array $unit): bool
{
    $unit = svedeniya_structure_normalize_unit($unit);

    return $unit['url'] !== '' && $unit['title'] !== '';
}

/** @param list<array<string, mixed>> $units */
function svedeniya_has_accordion_units(array $units): bool
{
    foreach ($units as $unit) {
        if (svedeniya_unit_is_external_link($unit)) {
            continue;
        }
        $unit = svedeniya_structure_normalize_unit($unit);
        $hasFiles = $unit['file_ids'] !== [];
        if ($unit['title'] !== '' || $unit['body'] !== '' || $hasFiles) {
            return true;
        }
    }

    return false;
}

/** @param list<array<string, mixed>> $units */
function svedeniya_render_external_links(array $units, string $idPrefix = 'sv-link'): void
{
    $links = [];
    foreach ($units as $idx => $unit) {
        if (!svedeniya_unit_is_external_link($unit)) {
            continue;
        }
        $unit = svedeniya_structure_normalize_unit($unit);
        $links[] = ['id' => $idPrefix . '-' . $idx, 'title' => $unit['title'], 'url' => $unit['url']];
    }
    if ($links === []) {
        return;
    }
    ?>
    <div class="gos-sved-external-links">
        <?php foreach ($links as $link): ?>
        <a id="<?= htmlspecialchars($link['id']) ?>"
           class="gos-sved-external-link"
           href="<?= htmlspecialchars($link['url']) ?>"
           target="_blank"
           rel="noopener noreferrer">
            <span class="gos-sved-external-link-text"><?= htmlspecialchars($link['title']) ?></span>
            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
        </a>
        <?php endforeach; ?>
    </div>
    <?php
}

function svedeniya_section_uses_grid_layout(string $slug): bool
{
    return $slug === 'structure';
}

function svedeniya_section_uses_accordion_layout(string $slug): bool
{
    if (!svedeniya_section_meta($slug) || ($slug === 'education')) {
        return false;
    }

    return !svedeniya_section_uses_grid_layout($slug);
}

/** @param list<array<string, mixed>> $units */
function svedeniya_units_used_file_ids(array $units): array
{
    $ids = [];
    foreach ($units as $unit) {
        foreach (svedeniya_structure_unit_file_ids($unit) as $fileId) {
            $ids[$fileId] = $fileId;
        }
    }

    return array_values($ids);
}

/** @param list<array<string, mixed>> $units @param list<array<string, mixed>> $sectionFiles */
function svedeniya_section_loose_files(array $units, array $sectionFiles): array
{
    $usedIds = svedeniya_units_used_file_ids($units);

    return array_values(array_filter(
        $sectionFiles,
        static fn(array $file): bool => !in_array((int)$file['id'], $usedIds, true)
    ));
}

/** @param list<array<string, mixed>> $units @param array<string, array<string, mixed>> $filesById */
function svedeniya_render_accordion_units(
    array $units,
    array $filesById,
    string $idPrefix = 'sv-unit',
    bool $wrapPrograms = true,
    bool $expandFirst = true
): void {
    if ($units === [] || !svedeniya_has_accordion_units($units)) {
        return;
    }

    if ($wrapPrograms) {
        echo '<div class="gos-edu-programs">';
    }

    foreach ($units as $idx => $unit) {
        if (svedeniya_unit_is_external_link($unit)) {
            continue;
        }
        $unit = svedeniya_structure_normalize_unit($unit);
        $unitFiles = [];
        foreach ($unit['file_ids'] as $fileId) {
            if (isset($filesById[$fileId])) {
                $unitFiles[] = $filesById[$fileId];
            }
        }
        if ($unit['title'] === '' && $unit['body'] === '' && $unitFiles === []) {
            continue;
        }
        $collapseId = $idPrefix . '-' . $idx;
        $isOpen = $expandFirst && $idx === 0;
        ?>
        <article class="gos-edu-program" id="<?= htmlspecialchars($collapseId) ?>">
            <div class="gos-edu-program-head">
                <button class="gos-edu-program-toggle<?= $isOpen ? '' : ' collapsed' ?>"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#<?= htmlspecialchars($collapseId) ?>-body"
                        aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
                        aria-controls="<?= htmlspecialchars($collapseId) ?>-body">
                    <span class="gos-edu-program-title"><?= htmlspecialchars($unit['title'] !== '' ? $unit['title'] : 'Без названия') ?></span>
                    <span class="gos-edu-program-chevron" aria-hidden="true"><i class="bi bi-chevron-up"></i></span>
                </button>
            </div>
            <div id="<?= htmlspecialchars($collapseId) ?>-body" class="collapse<?= $isOpen ? ' show' : '' ?>">
                <div class="gos-edu-program-body">
                    <?php if ($unit['body'] !== ''): ?>
                    <div class="gos-edu-text-blocks">
                        <?php svedeniya_render_education_text_blocks($unit['body']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($unitFiles !== []): ?>
                        <?php org_document_render_file_list($unitFiles); ?>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php
    }

    if ($wrapPrograms) {
        echo '</div>';
    }
}

/** @param array{intro?: string, units?: list<array<string, string>>} $data */
function svedeniya_structure_encode(array $data): string
{
    $units = [];
    foreach ($data['units'] ?? [] as $unit) {
        if (!is_array($unit)) {
            continue;
        }
        $normalized = svedeniya_structure_normalize_unit($unit);
        if ($normalized['title'] === '' && $normalized['body'] === '' && $normalized['file_ids'] === [] && $normalized['url'] === '') {
            continue;
        }
        $stored = [
            'title' => $normalized['title'],
            'body'  => $normalized['body'],
        ];
        if ($normalized['url'] !== '') {
            $stored['url'] = $normalized['url'];
        }
        if ($normalized['file_ids'] !== []) {
            $stored['file_ids'] = $normalized['file_ids'];
        }
        $units[] = $stored;
    }

    return json_encode([
        'intro' => trim((string)($data['intro'] ?? '')),
        'units' => $units,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/** Обновляет intro и body из эталона, сохраняя file_ids и url из БД. */
function svedeniya_apply_canonical_section(PDO $pdo, string $slug): void
{
    require_once __DIR__ . '/svedeniya_sections_content.php';

    $canonical = svedeniya_canonical_section($pdo, $slug);
    if ($canonical === null) {
        return;
    }

    $row = org_document_text_get($pdo, $slug);
    if (!$row) {
        return;
    }

    $parsed = svedeniya_structure_parse((string)$row['content']);
    $existingByTitle = [];
    foreach ($parsed['units'] as $unit) {
        $title = trim((string)($unit['title'] ?? ''));
        if ($title !== '') {
            $existingByTitle[$title] = svedeniya_structure_normalize_unit($unit);
        }
    }

    $merged = [];
    $changed = false;

    foreach ($canonical['units'] as $unit) {
        $unit = svedeniya_structure_normalize_unit($unit);
        $title = $unit['title'];
        if ($title === '') {
            continue;
        }

        if (isset($existingByTitle[$title])) {
            $existing = $existingByTitle[$title];
            if ($existing['body'] !== $unit['body']) {
                $existing['body'] = $unit['body'];
                $changed = true;
            }
            if ($unit['url'] !== '') {
                if ($existing['url'] !== $unit['url']) {
                    $existing['url'] = $unit['url'];
                    $changed = true;
                }
            }
            if ($unit['file_ids'] !== []) {
                if ($existing['file_ids'] !== $unit['file_ids']) {
                    $existing['file_ids'] = $unit['file_ids'];
                    $changed = true;
                }
            }
            $merged[] = $existing;
            unset($existingByTitle[$title]);
            continue;
        }

        $merged[] = $unit;
        $changed = true;
    }

    foreach ($existingByTitle as $extra) {
        $merged[] = $extra;
    }

    $intro = trim((string)($canonical['intro'] ?? ''));
    if ($intro !== '' && $parsed['intro'] !== $intro) {
        $parsed['intro'] = $intro;
        $changed = true;
    }

    if (!$changed) {
        return;
    }

    $parsed['units'] = $merged;
    org_document_text_save(
        $pdo,
        $slug,
        (string)($row['title'] ?? $slug),
        svedeniya_structure_encode($parsed)
    );
}

function svedeniya_section_uses_structure_layout(string $slug): bool
{
    $meta = svedeniya_section_meta($slug);

    return $meta !== null && ($meta['type'] ?? '') === 'content';
}

/** @return array{units: string, unit: string, add: string, intro: string} */
function svedeniya_section_editor_labels(string $slug): array
{
    return [
        'intro' => 'Вводный текст',
        'units' => '',
        'unit'  => 'Блок',
        'add'   => '+ Добавить блок',
    ];
}

function svedeniya_section_units_title(string $slug): string
{
    return svedeniya_section_editor_labels($slug)['units'];
}

function svedeniya_section_intro_title(string $slug): string
{
    return match ($slug) {
        'nutrition' => 'Условия питания и охраны здоровья обучающихся',
        'safety'       => 'Организация безопасности',
        'parents_info' => 'Материалы для родителей',
        default        => 'Общие сведения',
    };
}

function svedeniya_structure_default_json(): string
{
    return svedeniya_content_json(
        'Структура и органы управления образовательной организацией определяются уставом учреждения.',
        [
            [
                'title' => 'Администрация',
                'body'  => "Руководитель\n" . SITE_ADDRESS . "\n" . SITE_EMAIL,
            ],
        ]
    );
}

/** @param list<array{id: string, label: string}> $items */
function svedeniya_render_page_sidebar(array $items): void
{
    if ($items === []) {
        return;
    }
    ?>
    <aside class="gos-sved-sidebar sticky-lg-top">
        <div class="gos-sved-sidebar-inner">
            <h2 class="gos-sved-sidebar-title">На этой странице</h2>
            <nav class="gos-sved-sidebar-nav">
                <?php foreach ($items as $item): ?>
                <a href="#<?= htmlspecialchars($item['id']) ?>" class="gos-sved-sidebar-link"><?= htmlspecialchars($item['label']) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>
    <?php
}

/** @param list<array<string, string>> $units */
function svedeniya_render_structure_units(array $units): void
{
    if ($units === []) {
        return;
    }
    ?>
    <div class="gos-sved-units-grid">
        <?php foreach ($units as $unit): ?>
        <article class="gos-sved-unit-card">
            <?php if ($unit['title'] !== ''): ?>
                <h3 class="gos-sved-unit-title"><?= htmlspecialchars($unit['title']) ?></h3>
            <?php endif; ?>
            <?php if ($unit['body'] !== ''): ?>
                <div class="gos-sved-unit-body"><?= svedeniya_render_unit_body($unit['body']) ?></div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function svedeniya_render_unit_body(string $body): string
{
    ob_start();
    svedeniya_render_education_text_blocks($body);

    return (string)ob_get_clean();
}

/** @return list<string> */
function svedeniya_text_paragraphs(string $text): array
{
    $text = trim($text);
    if ($text === '') {
        return [];
    }

    $parts = preg_split('/\n\s*\n/', $text) ?: [];
    if (count($parts) === 1) {
        $parts = preg_split('/\r\n|\r|\n/', $text) ?: [];
    }

    return array_values(array_filter(array_map('trim', $parts), static fn(string $p): bool => $p !== ''));
}

function svedeniya_paragraph_is_bullet_list(string $paragraph): bool
{
    $lines = preg_split('/\r\n|\r|\n/', $paragraph) ?: [];
    $hasBullet = false;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (!str_starts_with($line, '• ')) {
            return false;
        }
        $hasBullet = true;
    }

    return $hasBullet;
}

function svedeniya_render_bullet_list(string $paragraph): void
{
    $lines = preg_split('/\r\n|\r|\n/', $paragraph) ?: [];
    ?>
    <ul class="gos-edu-text-list">
        <?php foreach ($lines as $line):
            $line = trim($line);
            if ($line === '' || !str_starts_with($line, '• ')) {
                continue;
            }
            ?>
            <li><?= htmlspecialchars(mb_substr($line, 2)) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function svedeniya_render_education_text_blocks(string $text): void
{
    $paragraphs = svedeniya_text_paragraphs($text);
    if ($paragraphs === []) {
        return;
    }

    foreach ($paragraphs as $para) {
        if (str_starts_with($para, '## ')) {
            ?>
            <h4 class="gos-edu-text-subhead"><?= htmlspecialchars(mb_substr($para, 3)) ?></h4>
            <?php
            continue;
        }
        if (svedeniya_paragraph_is_bullet_list($para)) {
            svedeniya_render_bullet_list($para);
            continue;
        }
        ?>
        <p class="gos-edu-text-para"><?= svedeniya_render_text_line($para) ?></p>
        <?php
    }
}

function svedeniya_render_text_line(string $text): string
{
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    $html = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (filter_var($line, FILTER_VALIDATE_EMAIL)) {
            $html[] = '<a href="mailto:' . htmlspecialchars($line) . '">' . htmlspecialchars($line) . '</a>';
        } else {
            $html[] = htmlspecialchars($line);
        }
    }

    return implode('<br>', $html);
}

function svedeniya_render_intro_education_card(string $intro, string $title = 'Общие сведения', string $collapseId = 'sv-intro-body'): void
{
    $paragraphs = svedeniya_text_paragraphs($intro);
    if ($paragraphs === []) {
        return;
    }
    ?>
    <article class="gos-edu-program gos-edu-intro-card">
        <div class="gos-edu-program-head">
            <button class="gos-edu-program-toggle" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#<?= htmlspecialchars($collapseId) ?>"
                    aria-expanded="true"
                    aria-controls="<?= htmlspecialchars($collapseId) ?>">
                <span class="gos-edu-program-title"><?= htmlspecialchars($title) ?></span>
                <span class="gos-edu-program-chevron" aria-hidden="true"><i class="bi bi-chevron-up"></i></span>
            </button>
        </div>
        <div id="<?= htmlspecialchars($collapseId) ?>" class="collapse show">
            <div class="gos-edu-program-body">
                <?php svedeniya_render_education_text_blocks($intro); ?>
            </div>
        </div>
    </article>
    <?php
}
