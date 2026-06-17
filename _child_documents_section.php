<?php

require_once __DIR__ . '/../includes/child_documents.php';

function admin_render_child_documents(PDO $pdo, int $childId, bool $embedded = false): void
{
    $grouped = child_documents_grouped($pdo, [$childId]);
    $docs = $grouped[$childId] ?? ['tnr' => [], 'allergy' => []];
    $types = child_document_types();
    $hasAny = ($docs['tnr'] ?? []) !== [] || ($docs['allergy'] ?? []) !== [];
    ?>
    <?php if ($embedded): ?>
    <div class="child-health-docs-inner">
    <?php else: ?>
    <div class="card border-0 shadow-sm mt-4"><div class="card-body p-4">
    <?php endif; ?>
            <h2 class="h6 fw-semibold mb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>
                    <i class="bi bi-file-earmark-medical me-1 text-primary"></i>
                    Справки от родителя
                </span>
                <a href="documents.php?child_id=<?= $childId ?>" class="small fw-normal link-more">Все справки</a>
            </h2>
            <?php if ($embedded): ?>
            <p class="small text-muted mb-3">PDF или фото, загруженные родителем в личном кабинете. Только просмотр.</p>
            <?php endif; ?>
            <div class="row g-3">
                <?php foreach ($types as $type => $label): ?>
                    <?php $items = $docs[$type] ?? []; ?>
                    <div class="col-md-6">
                        <div class="child-doc-block border rounded p-3 h-100 bg-white">
                            <div class="small fw-semibold mb-2"><?= htmlspecialchars($label) ?></div>
                            <?php if ($items === []): ?>
                                <p class="text-muted small mb-0">Не загружено</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush child-doc-list">
                                    <?php foreach ($items as $doc):
                                        $size = child_document_file_size($doc['file_path']);
                                    ?>
                                    <li class="list-group-item px-0 d-flex align-items-start gap-2">
                                        <i class="bi bi-file-earmark-<?= child_document_is_image($doc['file_path']) ? 'image' : 'pdf' ?> text-primary mt-1"></i>
                                        <div class="min-w-0 flex-grow-1">
                                            <span class="fw-semibold d-block text-truncate"><?= htmlspecialchars($doc['original_name']) ?></span>
                                            <span class="small text-muted">
                                                <?= date('d.m.Y H:i', strtotime($doc['created_at'])) ?>
                                                <?php if ($size !== null): ?>
                                                    · <?= child_document_format_size($size) ?>
                                                <?php endif; ?>
                                            </span>
                                            <?php child_document_render_file_actions($doc, '../'); ?>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$hasAny): ?>
                <p class="text-muted small mb-0 mt-2">Родитель загружает справки в личном кабинете → «Справки».</p>
            <?php endif; ?>
    <?php if ($embedded): ?>
    </div>
    <?php else: ?>
    </div></div>
    <?php endif; ?>
    <?php
}

function admin_child_documents_badge(PDO $pdo, int $childId): string
{
    $grouped = child_documents_grouped($pdo, [$childId]);
    $docs = $grouped[$childId] ?? ['tnr' => [], 'allergy' => []];
    $tnr = count($docs['tnr'] ?? []);
    $allergy = count($docs['allergy'] ?? []);
    $total = $tnr + $allergy;

    if ($total === 0) {
        return '<span class="text-muted">—</span>';
    }

    $parts = [];
    if ($tnr > 0) {
        $parts[] = 'ТНР: ' . $tnr;
    }
    if ($allergy > 0) {
        $parts[] = 'аллерг.: ' . $allergy;
    }

    return '<a href="documents.php?child_id=' . $childId . '" class="badge bg-soft-blue text-dark text-decoration-none">'
        . htmlspecialchars(implode(', ', $parts))
        . '</a>';
}
