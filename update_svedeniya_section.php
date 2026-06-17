<?php

require_once '_auth.php';

require_once '_svedeniya_section_form.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: svedeniya.php');

    exit;

}



$slug = trim((string)($_POST['slug'] ?? ''));

$title = trim((string)($_POST['title'] ?? ''));

$content = $slug === 'education'
    ? admin_collect_education_content_from_post()
    : admin_collect_structure_content_from_post();



try {

    svedeniya_save_section($pdo, $slug, $title, $content);

    admin_flash('success', 'Раздел сохранён');

    header('Location: edit_svedeniya_section.php?slug=' . urlencode($slug));

} catch (InvalidArgumentException $e) {

    admin_flash('error', $e->getMessage());

    header('Location: edit_svedeniya_section.php?slug=' . urlencode($slug));

} catch (Throwable $e) {

    admin_flash('error', 'Не удалось сохранить');

    header('Location: edit_svedeniya_section.php?slug=' . urlencode($slug));

}

exit;


