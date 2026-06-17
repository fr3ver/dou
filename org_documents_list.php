<?php
/**
 * @var array<string, list<array<string, mixed>>> $grouped
 * @var bool $compact
 */
$compact = $compact ?? false;
$base = $base ?? '';

if ($grouped === []): ?>
    <p class="text-muted mb-0">Документы пока не опубликованы.</p>
<?php return; endif;

foreach ($grouped as $category => $items): ?>
    <section class="org-doc-section mb-4">
        <?php if (empty($compact)): ?>
        <h2 class="org-doc-section-title h5 fw-semibold mb-3">
            <?= htmlspecialchars(org_document_category_label($category)) ?>
        </h2>
        <?php endif; ?>
        <div class="org-doc-list">
            <?php foreach ($items as $doc): ?>
            <?php
            $ext = strtolower(pathinfo((string)($doc['file_path'] ?? ''), PATHINFO_EXTENSION));
            $iconClass = match ($ext) {
                'pdf' => 'org-doc-file-icon-pdf',
                'doc', 'docx' => 'org-doc-file-icon-doc',
                'jpg', 'jpeg', 'png', 'webp' => 'org-doc-file-icon-img',
                default => 'org-doc-file-icon-file',
            };
            $iconLabel = match ($ext) {
                'pdf' => 'PDF',
                'doc', 'docx' => 'DOC',
                'jpg', 'jpeg', 'png', 'webp' => 'IMG',
                default => strtoupper($ext !== '' ? $ext : 'FILE'),
            };
            ?>
            <article class="org-doc-item">
                <span class="org-doc-file-icon <?= $iconClass ?>" aria-hidden="true"><?= htmlspecialchars($iconLabel) ?></span>
                <div class="org-doc-item-main">
                    <h3 class="org-doc-item-title mb-0"><?= htmlspecialchars($doc['title']) ?></h3>
                    <span class="org-doc-item-size text-muted">
                        <?= org_document_format_size(isset($doc['file_size']) ? (int)$doc['file_size'] : null) ?>
                    </span>
                </div>
                <div class="org-doc-item-actions">
                    <?php org_document_render_file_actions($doc, $base, 'org-doc-download-link'); ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
