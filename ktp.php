<?php
require_once '_auth.php';
require_once __DIR__ . '/../includes/ktp.php';

admin_page_start(
    'Календарно-тематическое планирование',
    'Планы воспитателя по возрастным группам — публикуются в сведениях и кабинете сотрудников'
);

$files = ktp_documents($pdo);
$fileId = (int)($_GET['file_id'] ?? 0);
$editFile = $fileId > 0 ? org_document_get($pdo, $fileId) : null;
if ($editFile && (($editFile['section_slug'] ?? '') !== ktp_section_slug())) {
    $editFile = null;
}
$grouped = ktp_documents_grouped($pdo, $files);
?>

<div class="mb-3 d-flex flex-wrap gap-2">
    <a href="../svedeniya_section.php?slug=ktp" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">
        <i class="bi bi-box-arrow-up-right me-1"></i>На сайте (сведения)
    </a>
    <a href="../employee/materials.php" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">
        <i class="bi bi-box-arrow-up-right me-1"></i>Кабинет сотрудников
    </a>
</div>

<?php if ($files === []): ?>
<div class="alert alert-info border-0 shadow-sm">
  Файлов нет. Выполните <code>php scripts/seed_ktp.php</code> для создания шаблонов.
</div>
<?php else: ?>
<?php foreach (ktp_group_definitions() as $groupKey => $meta): ?>
    <?php $groupFiles = $grouped[$groupKey] ?? []; if ($groupFiles === []) continue; ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-4">
            <h2 class="h6 fw-semibold mb-3"><?= htmlspecialchars($meta['title']) ?></h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Название</th>
                            <th>Размер</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupFiles as $file): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars($file['title']) ?></td>
                            <td class="text-muted small"><?= org_document_format_size((int)($file['file_size'] ?? 0)) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="ktp.php?file_id=<?= (int)$file['id'] ?>#upload-form" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="delete_ktp.php" method="POST" class="d-inline"
                                      onsubmit="return confirm('Удалить файл?')">
                                    <input type="hidden" name="id" value="<?= (int)$file['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php endif; ?>

<div id="upload-form" class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h2 class="h6 fw-semibold mb-3"><?= $editFile ? 'Заменить файл' : 'Загрузить план' ?></h2>
        <form action="save_ktp.php" method="POST" enctype="multipart/form-data">
            <?php if ($editFile): ?>
            <input type="hidden" name="file_id" value="<?= (int)$editFile['id'] ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Название</label>
                <input type="text" name="file_title" class="form-control" required maxlength="255"
                       value="<?= htmlspecialchars($editFile['title'] ?? '') ?>">
            </div>
            <?php if (!$editFile): ?>
            <div class="mb-3">
                <label class="form-label">Группа (категория)</label>
                <select name="category" class="form-select" required>
                    <?php foreach (ktp_catalog_items() as $item): ?>
                    <option value="<?= htmlspecialchars($item['key']) ?>"><?= htmlspecialchars($item['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">PDF<?= $editFile ? ' (оставьте пустым, чтобы не менять)' : '' ?></label>
                <input type="file" name="file_document" class="form-control" accept=".pdf"<?= $editFile ? '' : ' required' ?>>
            </div>
            <button type="submit" class="btn btn-accent"><?= $editFile ? 'Сохранить' : 'Загрузить' ?></button>
            <?php if ($editFile): ?>
            <a href="ktp.php#upload-form" class="btn btn-outline-secondary">Отмена</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php admin_page_end(); ?>
