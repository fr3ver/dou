<?php
/**
 * Разметка страницы раздела сведений (стиль gosweb).
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
$sidebarItems = [];
$usesCards = svedeniya_section_uses_structure_layout($slug);
$pageData = $usesCards ? svedeniya_structure_parse($content) : null;
$unitsTitle = svedeniya_section_units_title($slug);

if ($usesCards && $pageData) {
    if ($pageData['intro'] !== '' && $pageData['intro'] !== 'Не предусмотрено') {
        $sidebarItems[] = ['id' => 'sv-intro', 'label' => svedeniya_section_intro_title($slug)];
    }
    if ($pageData['units'] !== [] && $unitsTitle !== '') {
        $sidebarItems[] = ['id' => 'sv-units', 'label' => $unitsTitle];
    } elseif ($pageData['intro'] === 'Не предусмотрено') {
        $sidebarItems[] = ['id' => 'sv-intro', 'label' => 'Содержание'];
    }
}

$fileGroups = [];
if (svedeniya_section_accepts_files($slug) && $displayFiles !== []) {
    if (svedeniya_section_is_documents($slug)) {
        $fileGroups = org_documents_group_by_category($displayFiles);
        foreach ($fileGroups as $cat => $items) {
            $sidebarItems[] = [
                'id'    => 'sv-cat-' . $cat,
                'label' => org_document_category_label($cat),
            ];
        }
    } else {
        $sidebarItems[] = ['id' => 'sv-files', 'label' => 'Документы'];
        $fileGroups = ['other' => $displayFiles];
    }
}

$categories = org_document_categories();
?>
<div class="gos-sved-page">
    <div class="row g-4">
        <div class="col-lg-9 order-lg-1">
            <div class="gos-sved-panel">
                <a href="svedeniya.php" class="gos-sved-back link-more d-inline-flex align-items-center mb-3">
                    <i class="bi bi-arrow-left me-1"></i>Ко всем сведениям
                </a>
                <h1 class="gos-sved-page-title"><?= htmlspecialchars($pageTitle) ?></h1>

                <?php if ($usesCards && $pageData): ?>
                    <?php if ($pageData['intro'] === 'Не предусмотрено'): ?>
                    <section id="sv-intro" class="gos-sved-block scroll-margin-top">
                        <p class="text-muted mb-0">Не предусмотрено</p>
                    </section>
                    <?php elseif ($pageData['intro'] !== ''): ?>
                    <section id="sv-intro" class="gos-sved-block scroll-margin-top">
                        <div class="gos-edu-programs">
                            <?php svedeniya_render_intro_education_card($pageData['intro'], svedeniya_section_intro_title($slug)); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    <?php if ($pageData['units'] !== []): ?>
                    <section id="sv-units" class="gos-sved-block scroll-margin-top">
                        <?php if ($unitsTitle !== ''): ?>
                        <h2 class="gos-sved-block-title"><?= htmlspecialchars($unitsTitle) ?></h2>
                        <?php endif; ?>
                        <?php svedeniya_render_structure_units($pageData['units']); ?>
                    </section>
                    <?php elseif ($pageData['intro'] === '' && $pageData['units'] === [] && !$hasAnyFiles): ?>
                    <p class="text-muted">Информация уточняется.</p>
                    <?php endif; ?>
                <?php elseif (!$hasAnyFiles): ?>
                    <p class="text-muted">Информация уточняется.</p>
                <?php endif; ?>

                <?php if ($fileGroups !== []): ?>
                    <?php foreach ($fileGroups as $cat => $items): ?>
                    <section id="<?= svedeniya_section_is_documents($slug) ? 'sv-cat-' . htmlspecialchars($cat) : 'sv-files' ?>"
                             class="gos-sved-block scroll-margin-top">
                        <?php if (svedeniya_section_is_documents($slug)): ?>
                            <h2 class="gos-sved-block-title"><?= htmlspecialchars(org_document_category_label($cat)) ?></h2>
                        <?php else: ?>
                            <h2 class="gos-sved-block-title">Документы</h2>
                        <?php endif; ?>
                        <?php
                        $grouped = [$cat => $items];
                        $compact = !svedeniya_section_is_documents($slug);
                        $base = '';
                        require __DIR__ . '/org_documents_list.php';
                        ?>
                    </section>
                    <?php endforeach; ?>
                <?php elseif (svedeniya_section_accepts_files($slug) && !$hasAnyFiles && svedeniya_section_is_documents($slug)): ?>
                    <section class="gos-sved-block">
                        <p class="text-muted mb-0">Документы пока не опубликованы.</p>
                    </section>
                <?php elseif (svedeniya_section_is_documents($slug) && $hasAnyFiles && $displayFiles === []): ?>
                    <section class="gos-sved-block">
                        <p class="text-muted mb-0">В выбранной категории документов нет.</p>
                    </section>
                <?php endif; ?>
            </div>
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
