<?php
/**
 * Разметка раздела сведений в стиле «Образование» (аккордеон + файлы).
 * @var string $slug
 * @var array $section
 * @var PDO $pdo
 * @var string $category
 * @var list<array<string, mixed>> $displayFiles
 * @var bool $hasAnyFiles
 */
require_once __DIR__ . '/../svedeniya.php';

$pageTitle = $section['title'];
$content = trim((string)($section['content'] ?? ''));
$pageData = svedeniya_structure_parse($content);
$filesById = org_documents_index_by_id($displayFiles);
$looseFiles = svedeniya_section_is_documents($slug)
    ? $displayFiles
    : svedeniya_section_loose_files($pageData['units'], $displayFiles);

$sidebarItems = [];
if ($pageData['intro'] !== '' && $pageData['intro'] !== 'Не предусмотрено') {
    $sidebarItems[] = [
        'id'    => 'sv-intro',
        'label' => $slug === 'standards' ? 'Содержание' : svedeniya_section_intro_title($slug),
    ];
}
foreach ($pageData['units'] as $idx => $unit) {
    $unit = svedeniya_structure_normalize_unit($unit);
    if (svedeniya_unit_is_external_link($unit)) {
        $sidebarItems[] = [
            'id'    => 'sv-link-' . $idx,
            'label' => $unit['title'],
        ];
        continue;
    }
    if ($unit['title'] === '' && $unit['body'] === '' && $unit['file_ids'] === []) {
        continue;
    }
    $sidebarItems[] = [
        'id'    => 'sv-unit-' . $idx,
        'label' => $unit['title'] !== '' ? $unit['title'] : 'Раздел ' . ($idx + 1),
    ];
}

$fileGroups = [];
if (svedeniya_section_is_documents($slug) && $displayFiles !== []) {
    $fileGroups = org_documents_group_by_category($displayFiles);
    foreach ($fileGroups as $cat => $items) {
        $sidebarItems[] = [
            'id'    => 'sv-cat-' . $cat,
            'label' => org_document_category_label($cat),
        ];
    }
} elseif ($looseFiles !== []) {
    $sidebarItems[] = ['id' => 'sv-files', 'label' => 'Документы'];
    $fileGroups = ['files' => $looseFiles];
}

$categories = org_document_categories();
?>
<div class="gos-sved-page">
    <div class="row g-4">
        <div class="col-lg-9 order-lg-1 gos-sved-education-main">
            <a href="svedeniya.php" class="gos-sved-back link-more d-inline-flex align-items-center mb-3">
                <i class="bi bi-arrow-left me-1"></i>Ко всем сведениям
            </a>
            <h1 class="gos-sved-page-title"><?= htmlspecialchars($pageTitle) ?></h1>

            <?php
            $hasIntro = $pageData['intro'] !== '' && $pageData['intro'] !== 'Не предусмотрено';
            $hasUnits = $pageData['units'] !== [];
            ?>
            <?php if ($pageData['intro'] === 'Не предусмотрено'): ?>
            <section id="sv-intro" class="gos-sved-block scroll-margin-top">
                <p class="text-muted mb-0">Не предусмотрено</p>
            </section>
            <?php elseif ($hasIntro || $hasUnits): ?>
            <section class="gos-sved-block scroll-margin-top">
                <?php if ($hasIntro): ?>
                <div id="sv-intro" class="<?= $slug === 'standards' ? 'gos-edu-text-blocks' : 'gos-edu-programs' ?>">
                    <?php if ($slug === 'standards'): ?>
                        <?php svedeniya_render_education_text_blocks($pageData['intro']); ?>
                    <?php else: ?>
                        <?php svedeniya_render_intro_education_card($pageData['intro'], svedeniya_section_intro_title($slug)); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($hasUnits): ?>
                    <?php svedeniya_render_external_links($pageData['units'], 'sv-link'); ?>
                    <?php if (svedeniya_has_accordion_units($pageData['units'])): ?>
                        <?php svedeniya_render_accordion_units($pageData['units'], $filesById, 'sv-unit', true, !$hasIntro); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
            <?php elseif (!$hasAnyFiles): ?>
            <p class="text-muted">Информация уточняется.</p>
            <?php endif; ?>

            <?php if ($fileGroups !== []): ?>
                <?php foreach ($fileGroups as $cat => $items): ?>
                <section id="<?= svedeniya_section_is_documents($slug) ? 'sv-cat-' . htmlspecialchars($cat) : 'sv-files' ?>"
                         class="gos-sved-block scroll-margin-top">
                    <h2 class="gos-sved-block-title">
                        <?= htmlspecialchars(svedeniya_section_is_documents($slug) ? org_document_category_label($cat) : 'Документы') ?>
                    </h2>
                    <?php org_document_render_file_list($items); ?>
                </section>
                <?php endforeach; ?>
            <?php elseif (svedeniya_section_accepts_files($slug) && svedeniya_section_is_documents($slug)): ?>
            <section class="gos-sved-block scroll-margin-top">
                <p class="text-muted mb-0">Документы пока не опубликованы.</p>
            </section>
            <?php elseif (svedeniya_section_is_documents($slug) && $hasAnyFiles && $displayFiles === []): ?>
            <section class="gos-sved-block scroll-margin-top">
                <p class="text-muted mb-0">В выбранной категории документов нет.</p>
            </section>
            <?php endif; ?>
        </div>

        <div class="col-lg-3 order-lg-2">
            <?php if (svedeniya_section_is_documents($slug)): ?>
            <aside class="gos-sved-sidebar sticky-lg-top mb-4 mb-lg-0">
                <div class="gos-sved-sidebar-inner">
                    <h2 class="gos-sved-sidebar-title">Категории</h2>
                    <nav class="gos-sved-sidebar-nav">
                        <a href="svedeniya_section.php?slug=documents"
                           class="gos-sved-sidebar-link<?= $category === 'all' ? ' is-active' : '' ?>">Все документы</a>
                        <?php foreach ($categories as $key => $label): ?>
                        <a href="svedeniya_section.php?slug=documents&amp;category=<?= urlencode($key) ?>"
                           class="gos-sved-sidebar-link<?= $category === $key ? ' is-active' : '' ?>">
                            <?= htmlspecialchars($label) ?>
                        </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </aside>
            <?php endif; ?>

            <?php svedeniya_render_page_sidebar($sidebarItems); ?>
        </div>
    </div>
</div>
