<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/svedeniya.php';

$sections = svedeniya_all_sections($pdo);
$pageTitle = svedeniya_hub_title();
$breadcrumbs = [
    ['label' => 'Главная', 'href' => 'index.php'],
    ['label' => $pageTitle],
];

require __DIR__ . '/includes/partials/svedeniya_public_shell_start.php';
?>

<div class="gos-sved-panel">
    <h1 class="gos-sved-page-title mb-4"><?= htmlspecialchars($pageTitle) ?></h1>
    <div class="svedeniya-grid gos-sved-hub-grid">
            <?php foreach ($sections as $section): ?>
            <a href="<?= htmlspecialchars($section['href']) ?>" class="svedeniya-card">
                <span class="svedeniya-card-icon">
                    <i class="bi <?= htmlspecialchars($section['icon']) ?>"></i>
                </span>
                <span class="svedeniya-card-title"><?= htmlspecialchars($section['title']) ?></span>
                <span class="svedeniya-card-arrow"><i class="bi bi-chevron-right"></i></span>
            </a>
            <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/partials/svedeniya_public_shell_end.php'; ?>
