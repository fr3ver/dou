<?php
/**
 * Блок «Сведения об образовательной организации» в кабинете родителя.
 * @var PDO $pdo
 */
require_once __DIR__ . '/../svedeniya.php';

svedeniya_seed_sections($pdo);

$basicSection = svedeniya_get_section($pdo, 'basic');
$basicExcerpt = $basicSection ? svedeniya_content_excerpt((string)($basicSection['content'] ?? ''), 5) : '';
?>
<?php lk_section_title('journal-text', 'Сведения об образовательной организации', 'Официальная информация о детском саде'); ?>

<div class="lk-panel lk-block">
        <?php if ($basicExcerpt !== ''): ?>
            <h3 class="h6 fw-semibold mb-2">Общие сведения</h3>
            <div class="svedeniya-content-body text-muted small mb-3">
                <?= nl2br(htmlspecialchars($basicExcerpt)) ?>
            </div>
        <?php else: ?>
            <p class="text-muted small mb-3">Официальные сведения о детском саде: документы, лицензии, информация об образовании и питании.</p>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2 mb-0">
            <a href="svedeniya.php" class="btn btn-accent btn-sm">
                <i class="bi bi-journal-text me-1"></i>Все сведения
            </a>
            <a href="../svedeniya.php" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right me-1"></i>На сайте
            </a>
        </div>
</div>
