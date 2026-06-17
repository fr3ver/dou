<?php
require_once '_auth.php';
require_once '_group_helpers.php';
require_once __DIR__ . '/../includes/child_documents.php';

$filter = $_GET['filter'] ?? 'all';
$childId = (int)($_GET['child_id'] ?? 0);
$allowedFilters = ['all', 'tnr', 'allergy'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$docType = $filter === 'all' ? null : $filter;
$documents = child_documents_admin_list($pdo, $docType, $childId);
$stats = child_documents_admin_stats($pdo);
$types = child_document_types();

$filterLabels = [
    'all'     => 'Все',
    'tnr'     => 'ТНР',
    'allergy' => 'Аллергии',
];

$subtitle = 'Справки, загруженные родителями в личном кабинете';
if ($childId > 0 && $documents !== []) {
    $subtitle = 'Документы: ' . ($documents[0]['child_name'] ?? '');
} elseif ($childId > 0) {
    $stmt = $pdo->prepare('SELECT full_name FROM children WHERE id = ?');
    $stmt->execute([$childId]);
    $childName = $stmt->fetchColumn();
    if ($childName) {
        $subtitle = 'Документы: ' . $childName;
    }
}

admin_page_start('Справки детей', $subtitle);
?>

<div class="admin-stats-bar card border-0 shadow-sm mb-4">
    <div class="card-body py-4">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3">
                <div class="admin-stat-item">
                    <i class="bi bi-file-earmark-medical fs-4 text-primary"></i>
                    <div class="admin-stat-value"><?= $stats['total'] ?></div>
                    <div class="admin-stat-label">Всего файлов</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="admin-stat-item">
                    <i class="bi bi-cup-hot fs-4" style="color: var(--dou-orange);"></i>
                    <div class="admin-stat-value"><?= $stats['tnr'] ?></div>
                    <div class="admin-stat-label">Справки о ТНР</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="admin-stat-item">
                    <i class="bi bi-shield-exclamation fs-4 text-danger"></i>
                    <div class="admin-stat-value"><?= $stats['allergy'] ?></div>
                    <div class="admin-stat-label">Об аллергиях</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="admin-stat-item">
                    <i class="bi bi-emoji-smile fs-4" style="color: var(--dou-green-dark);"></i>
                    <div class="admin-stat-value"><?= $stats['children'] ?></div>
                    <div class="admin-stat-label">Детей со справками</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <?php foreach ($filterLabels as $key => $label):
        $href = 'documents.php?filter=' . $key;
        if ($childId > 0) {
            $href .= '&child_id=' . $childId;
        }
        $active = $filter === $key ? ' btn-accent' : ' btn-outline-secondary';
    ?>
    <a href="<?= htmlspecialchars($href) ?>" class="btn btn-sm<?= $active ?>"><?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
    <?php if ($childId > 0): ?>
        <a href="documents.php?filter=<?= htmlspecialchars($filter) ?>" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-x-lg me-1"></i>Сбросить фильтр по ребёнку
        </a>
    <?php endif; ?>
</div>

<?php
admin_collapse_toolbar('documents-list', 'Загруженные справки', false, (string)count($documents), '', false);
admin_render_table_search('Поиск по ребёнку, группе, родителю, файлу...');
?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 admin-table">
        <thead class="table-light">
            <tr>
                <th>Дата</th>
                <th>Ребёнок</th>
                <th>Группа</th>
                <th>Родитель</th>
                <th>Тип</th>
                <th>Файл</th>
                <th class="text-end">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($documents === []): ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    Справок пока нет. Родители загружают их в личном кабинете.
                </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($documents as $doc):
                $size = child_document_file_size($doc['file_path']);
            ?>
            <tr>
                <td class="small text-nowrap">
                    <?= date('d.m.Y', strtotime($doc['created_at'])) ?>
                    <br><span class="text-muted"><?= date('H:i', strtotime($doc['created_at'])) ?></span>
                </td>
                <td class="fw-semibold">
                    <a href="edit_child.php?id=<?= (int)$doc['child_id'] ?>#child-docs" class="text-decoration-none">
                        <?= htmlspecialchars($doc['child_name']) ?>
                    </a>
                    <?php if (!empty($doc['has_tnr'])): ?>
                        <span class="badge badge-tnr ms-1">ТНР</span>
                    <?php endif; ?>
                </td>
                <td class="small"><?= htmlspecialchars($doc['group_name']) ?></td>
                <td class="small"><?= htmlspecialchars($doc['parent_name']) ?></td>
                <td>
                    <span class="badge <?= $doc['doc_type'] === 'tnr' ? 'bg-soft-yellow text-dark' : 'bg-soft-blue text-dark' ?>">
                        <?= htmlspecialchars($types[$doc['doc_type']] ?? $doc['doc_type']) ?>
                    </span>
                </td>
                <td class="small">
                    <i class="bi bi-file-earmark-<?= child_document_is_image($doc['file_path']) ? 'image' : 'pdf' ?> me-1 text-primary"></i>
                    <?= htmlspecialchars($doc['original_name']) ?>
                    <?php if ($size !== null): ?>
                        <br><span class="text-muted"><?= child_document_format_size($size) ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-end text-nowrap">
                    <?php if (child_document_is_inline($doc['file_path'])): ?>
                    <a href="../<?= htmlspecialchars(child_document_download_url((int)$doc['child_id'], (string)$doc['doc_type'], true)) ?>"
                       class="btn btn-sm btn-primary-dou" target="_blank" rel="noopener" title="Открыть в браузере">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <?php endif; ?>
                    <a href="../<?= htmlspecialchars(child_document_download_url((int)$doc['child_id'], (string)$doc['doc_type'], false)) ?>"
                       class="btn btn-sm btn-outline-secondary" title="Скачать">
                        <i class="bi bi-download"></i>
                    </a>
                    <a href="edit_child.php?id=<?= (int)$doc['child_id'] ?>"
                       class="btn btn-sm btn-outline-secondary" title="Карточка ребёнка">
                        <i class="bi bi-person"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
admin_render_table_search_end();
admin_collapse_toolbar_end(false);
admin_page_end();
