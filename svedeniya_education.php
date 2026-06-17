<?php

require_once __DIR__ . '/org_documents.php';

function svedeniya_education_distance_default(): string
{
    return 'В ДОУ используются дистанционные технологии обучения. Они помогают родителям не прерывать образовательный процесс ребёнка во время карантина';
}

/** @return array<string, array{id: string, title: string, type: string, file_category?: string}> */
function svedeniya_education_groups(): array
{
    return [
        'implemented' => [
            'id'            => 'edu-implemented',
            'title'         => 'Реализуемые образовательные программы',
            'type'          => 'programs',
        ],
        'adapted' => [
            'id'            => 'edu-adapted',
            'title'         => 'Адаптированные образовательные программы',
            'type'          => 'programs',
        ],
        'docs' => [
            'id'            => 'edu-docs',
            'title'         => 'Документы',
            'type'          => 'files',
            'file_category' => 'edu_docs',
        ],
        'enrollment' => [
            'id'            => 'edu-enrollment',
            'title'         => 'Численность обучающихся и языки образования',
            'type'          => 'files',
            'file_category' => 'edu_enrollment',
        ],
        'license' => [
            'id'            => 'edu-license',
            'title'         => 'Лицензии на осуществление образовательной деятельности',
            'type'          => 'files',
            'file_category' => 'edu_license',
        ],
    ];
}

function svedeniya_education_is_format(string $content): bool
{
    $content = trim($content);
    if ($content === '' || $content[0] !== '{') {
        return false;
    }

    $data = json_decode($content, true);

    return is_array($data) && ($data['format'] ?? '') === 'education';
}

/** @return array{format: string, programs: array<string, list<array<string, mixed>>>} */
function svedeniya_education_parse(string $content): array
{
    $content = trim($content);
    if (svedeniya_education_is_format($content)) {
        $data = json_decode($content, true);
        $programs = [];
        foreach (array_keys(svedeniya_education_groups()) as $key) {
            if ((svedeniya_education_groups()[$key]['type'] ?? '') !== 'programs') {
                continue;
            }
            $programs[$key] = [];
            foreach ($data['programs'][$key] ?? [] as $program) {
                if (!is_array($program)) {
                    continue;
                }
                $programs[$key][] = svedeniya_education_normalize_program($program);
            }
        }

        return ['format' => 'education', 'programs' => $programs];
    }

    return svedeniya_education_migrate_from_legacy($content);
}

/** @param array<string, mixed> $program */
function svedeniya_education_normalize_program(array $program): array
{
    return [
        'title'             => trim((string)($program['title'] ?? '')),
        'language'          => trim((string)($program['language'] ?? 'Русский')),
        'distance_learning' => trim((string)($program['distance_learning'] ?? svedeniya_education_distance_default())),
        'file_id'           => max(0, (int)($program['file_id'] ?? 0)),
    ];
}

/** @return array{format: string, programs: array<string, list<array<string, mixed>>>} */
function svedeniya_education_migrate_from_legacy(string $content): array
{
    require_once __DIR__ . '/svedeniya_structure.php';

    $legacy = svedeniya_structure_parse($content);
    $programs = [];

    foreach ($legacy['units'] as $unit) {
        $title = trim($unit['title'] ?? '');
        $body = trim($unit['body'] ?? '');
        if ($title === '' && $body === '') {
            continue;
        }
        $programs[] = [
            'title'             => $title !== '' ? $title : 'Образовательная программа',
            'language'          => 'Русский',
            'distance_learning' => $body !== '' ? $body : ($legacy['intro'] !== '' ? $legacy['intro'] : svedeniya_education_distance_default()),
            'file_id'           => 0,
        ];
    }

    if ($programs === [] && $legacy['intro'] !== '' && $legacy['intro'] !== 'Не предусмотрено') {
        $programs[] = [
            'title'             => 'Образовательная программа дошкольного образования',
            'language'          => 'Русский',
            'distance_learning' => $legacy['intro'],
            'file_id'           => 0,
        ];
    }

    return [
        'format'   => 'education',
        'programs' => [
            'implemented' => $programs,
            'adapted'     => [],
        ],
    ];
}

/** @param array{programs?: array<string, list<array<string, mixed>>>} $data */
function svedeniya_education_encode(array $data): string
{
    $programs = [];
    foreach (svedeniya_education_groups() as $key => $group) {
        if (($group['type'] ?? '') !== 'programs') {
            continue;
        }
        $items = [];
        foreach ($data['programs'][$key] ?? [] as $program) {
            if (!is_array($program)) {
                continue;
            }
            $normalized = svedeniya_education_normalize_program($program);
            if ($normalized['title'] === '') {
                continue;
            }
            $items[] = $normalized;
        }
        $programs[$key] = $items;
    }

    return json_encode([
        'format'   => 'education',
        'programs' => $programs,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function svedeniya_education_default_json(): string
{
    return svedeniya_education_encode([
        'programs' => [
            'implemented' => [
                [
                    'title'             => 'Основная образовательная программа «' . SITE_NAME . '»',
                    'language'          => 'Русский',
                    'distance_learning' => svedeniya_education_distance_default(),
                    'file_id'           => 0,
                ],
                [
                    'title'             => 'Программа развития',
                    'language'          => 'Русский',
                    'distance_learning' => svedeniya_education_distance_default(),
                    'file_id'           => 0,
                ],
            ],
            'adapted' => [],
        ],
    ]);
}

/** @param array<string, array<string, mixed>> $filesById */
function svedeniya_education_render_program(array $program, string $collapseId, bool $expanded, array $filesById): void
{
    $program = svedeniya_education_normalize_program($program);
    $file = $program['file_id'] > 0 ? ($filesById[$program['file_id']] ?? null) : null;
    ?>
    <article class="gos-edu-program">
        <div class="gos-edu-program-head">
            <button class="gos-edu-program-toggle<?= $expanded ? '' : ' collapsed' ?>"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#<?= htmlspecialchars($collapseId) ?>"
                    aria-expanded="<?= $expanded ? 'true' : 'false' ?>"
                    aria-controls="<?= htmlspecialchars($collapseId) ?>">
                <span class="gos-edu-program-title"><?= htmlspecialchars($program['title']) ?></span>
                <span class="gos-edu-program-chevron" aria-hidden="true"><i class="bi bi-chevron-up"></i></span>
            </button>
        </div>
        <div id="<?= htmlspecialchars($collapseId) ?>" class="collapse<?= $expanded ? ' show' : '' ?>">
            <div class="gos-edu-program-body">
                <?php if ($program['language'] !== ''): ?>
                <div class="gos-edu-field">
                    <div class="gos-edu-field-label">Язык обучения</div>
                    <div class="gos-edu-field-value"><?= htmlspecialchars($program['language']) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($program['distance_learning'] !== ''): ?>
                <div class="gos-edu-field">
                    <div class="gos-edu-field-label">Программы электронного обучения и дистанционные образовательные технологии</div>
                    <div class="gos-edu-field-value"><?= nl2br(htmlspecialchars($program['distance_learning'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($file): ?>
                    <?php org_document_render_file_bar($file); ?>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

function svedeniya_education_sidebar_items(): array
{
    $items = [];
    foreach (svedeniya_education_groups() as $group) {
        $items[] = ['id' => $group['id'], 'label' => $group['title']];
    }

    return $items;
}

function svedeniya_education_render_page(PDO $pdo, string $content, array $sectionFiles): void
{
    $data = svedeniya_education_parse($content);
    $filesById = org_documents_index_by_id($sectionFiles);
    $filesByCategory = [];
    foreach ($sectionFiles as $file) {
        $cat = org_document_resolve_category($file, 'education');
        $filesByCategory[$cat][] = $file;
    }

    foreach (svedeniya_education_groups() as $key => $group) {
        if (($group['type'] ?? '') !== 'programs') {
            continue;
        }
        $programs = $data['programs'][$key] ?? [];
        ?>
        <section id="<?= htmlspecialchars($group['id']) ?>" class="gos-sved-block scroll-margin-top">
            <h2 class="gos-sved-block-title"><?= htmlspecialchars($group['title']) ?></h2>
            <?php if ($programs === []): ?>
                <p class="text-muted mb-0">Информация уточняется.</p>
            <?php else: ?>
            <div class="gos-edu-programs">
                <?php foreach ($programs as $idx => $program): ?>
                    <?php svedeniya_education_render_program(
                        $program,
                        $group['id'] . '-p' . $idx,
                        $idx === 0,
                        $filesById
                    ); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php
    }

    foreach (svedeniya_education_groups() as $group) {
        if (($group['type'] ?? '') !== 'files') {
            continue;
        }
        $cat = $group['file_category'] ?? '';
        $items = $filesByCategory[$cat] ?? [];
        ?>
        <section id="<?= htmlspecialchars($group['id']) ?>" class="gos-sved-block scroll-margin-top">
            <h2 class="gos-sved-block-title"><?= htmlspecialchars($group['title']) ?></h2>
            <?php if ($items === []): ?>
                <p class="text-muted mb-0">Документы пока не опубликованы.</p>
            <?php else: ?>
                <?php org_document_render_file_list($items); ?>
            <?php endif; ?>
        </section>
        <?php
    }
}
