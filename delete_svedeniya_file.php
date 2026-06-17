<?php

require_once '_auth.php';

require_once __DIR__ . '/../includes/org_documents.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: svedeniya.php');

    exit;

}



$id = (int)($_POST['id'] ?? 0);

$slug = trim((string)($_POST['slug'] ?? ''));



try {

    $doc = $id > 0 ? org_document_get($pdo, $id) : null;

    if (!$doc || !org_document_is_file($doc)) {

        throw RuntimeException('Файл не найден');

    }

    org_document_delete($pdo, $id);

    admin_flash('success', 'Файл удалён');

} catch (Throwable $e) {

    admin_flash('error', 'Не удалось удалить файл');

}



header('Location: edit_svedeniya_section.php?slug=' . urlencode($slug !== '' ? $slug : ($doc['section_slug'] ?? 'documents')) . '#files');

exit;


