<?php
/**
 * Разметка раздела «Образование» (стиль gosweb).
 * @var string $slug
 * @var array $section
 * @var PDO $pdo
 * @var list<array<string, mixed>> $displayFiles
 */
require_once __DIR__ . '/../svedeniya_education.php';

$pageTitle = $section['title'];
$content = trim((string)($section['content'] ?? ''));
$sidebarItems = svedeniya_education_sidebar_items();
?>
<div class="gos-sved-page">
    <div class="row g-4">
        <div class="col-lg-9 order-lg-1 gos-sved-education-main">
            <a href="svedeniya.php" class="gos-sved-back link-more d-inline-flex align-items-center mb-3">
                <i class="bi bi-arrow-left me-1"></i>Ко всем сведениям
            </a>
            <h1 class="gos-sved-page-title"><?= htmlspecialchars($pageTitle) ?></h1>
            <?php svedeniya_education_render_page($pdo, $content, $displayFiles); ?>
        </div>
        <div class="col-lg-3 order-lg-2">
            <?php svedeniya_render_page_sidebar($sidebarItems); ?>
        </div>
    </div>
</div>
