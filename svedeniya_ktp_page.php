<?php
/**
 * @var string $slug
 * @var array $section
 * @var PDO $pdo
 */
require_once __DIR__ . '/../ktp.php';

$pageTitle = $section['title'];
$grouped = ktp_documents_grouped($pdo, ktp_documents($pdo));
?>
<div class="gos-sved-page">
    <div class="row g-4">
        <div class="col-lg-9 order-lg-1">
            <div class="gos-sved-panel">
                <a href="svedeniya.php" class="gos-sved-back link-more d-inline-flex align-items-center mb-3">
                    <i class="bi bi-arrow-left me-1"></i>Ко всем сведениям
                </a>
                <h1 class="gos-sved-page-title"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="gos-sved-prose mb-4"><?= htmlspecialchars(ktp_intro_text()) ?></p>
                <div class="ktp-page">
                    <?php ktp_render_grid($grouped, ''); ?>
                </div>
            </div>
        </div>
        <div class="col-lg-3 order-lg-2">
            <nav class="gos-sved-sidebar sticky-top" aria-label="Разделы планирования">
                <div class="gos-sved-sidebar-inner">
                    <div class="gos-sved-sidebar-title">Возрастные группы</div>
                    <ul class="gos-sved-sidebar-list">
                        <?php foreach (ktp_group_definitions() as $groupKey => $meta): ?>
                            <?php if (($grouped[$groupKey] ?? []) === []) {
                                continue;
                            } ?>
                        <li>
                            <a href="#ktp-<?= htmlspecialchars($groupKey) ?>" class="gos-sved-sidebar-link">
                                <?= htmlspecialchars($meta['title']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </nav>
        </div>
    </div>
</div>
