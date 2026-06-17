<?php

require_once '_auth.php';

require_once __DIR__ . '/../includes/org_documents.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: svedeniya.php');

    exit;

}



$id = (int)($_POST['id'] ?? 0);

$doc = $id > 0 ? org_document_get($pdo, $id) : null;

$slug = $doc ? (string)($doc['section_slug'] ?? 'documents') : 'documents';



try {

    org_document_delete($pdo, $id);

    admin_flash('success', 'Документ удалён');

} catch (Throwable $e) {

    admin_flash('error', 'Не удалось удалить документ');

}



header('Location: edit_svedeniya_section.php?slug=' . urlencode($slug) . '#files');

exit;


