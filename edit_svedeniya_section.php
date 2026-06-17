<?php

require_once '_auth.php';

require_once '_svedeniya_section_form.php';



$slug = trim((string)($_GET['slug'] ?? ''));

$meta = svedeniya_section_meta($slug);

$section = $slug !== '' ? svedeniya_get_section($pdo, $slug) : null;



if (!$meta || ($meta['type'] ?? '') !== 'content' || !$section) {

    admin_flash('error', 'Раздел не найден');

    header('Location: svedeniya.php');

    exit;

}



$fileId = (int)($_GET['file_id'] ?? 0);

$editFile = $fileId > 0 ? org_document_get($pdo, $fileId) : null;

if ($editFile && (!org_document_is_file($editFile) || ($editFile['section_slug'] ?? '') !== $slug)) {

    $editFile = null;

}

$presetCategory = trim((string)($_GET['file_category'] ?? ''));
if ($presetCategory !== '' && !org_document_category_is_valid($presetCategory, $slug)) {
    $presetCategory = '';
}
if ($editFile) {
    $presetCategory = org_document_resolve_category($editFile, $slug);
}

admin_page_start('Раздел: ' . $section['title'], 'Вводный текст, карточки и файлы');

?>

<div class="row justify-content-center"><div class="col-lg-10">

<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">

    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">

        <a href="svedeniya.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Все разделы</a>

        <a href="../svedeniya_section.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">

            <i class="bi bi-box-arrow-up-right me-1"></i>На сайте

        </a>

    </div>



    <form action="update_svedeniya_section.php" method="POST">

        <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">

        <div class="mb-4">

            <label class="form-label fw-semibold">Заголовок на сайте</label>

            <input type="text" name="title" class="form-control" required maxlength="255"

                   value="<?= htmlspecialchars($section['title']) ?>">

        </div>

        <?php admin_render_svedeniya_content_editor($pdo, $section, $slug); ?>

        <button type="submit" class="btn btn-accent">Сохранить</button>

    </form>

</div></div>



<div id="files" class="card border-0 shadow-sm"><div class="card-body p-4">

    <?php if ($slug === 'education'): ?>
        <?php admin_render_education_files_panel($pdo, $slug); ?>
    <?php elseif ($slug === 'structure'): ?>
        <?php admin_render_svedeniya_files_list($pdo, $slug); ?>
    <?php else: ?>
        <?php admin_render_section_files_panel($pdo, $slug); ?>
    <?php endif; ?>

    <form action="save_svedeniya_file.php" method="POST" enctype="multipart/form-data" id="upload-form">

        <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">

        <?php admin_render_svedeniya_file_form($slug, $editFile, $presetCategory !== '' ? $presetCategory : null); ?>

        <button type="submit" class="btn btn-accent"><?= $editFile ? 'Сохранить файл' : 'Загрузить файл' ?></button>

        <?php if ($editFile): ?>

            <a href="edit_svedeniya_section.php?slug=<?= urlencode($slug) ?>#files" class="btn btn-outline-secondary">Отмена</a>

        <?php endif; ?>

    </form>

</div></div>



</div></div>

<?php admin_page_end(); ?>


