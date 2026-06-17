<?php
require_once '_auth.php';
require_once __DIR__ . '/../includes/org_documents.php';

$id = (int)($_GET['id'] ?? 0);
$doc = $id > 0 ? org_document_get($pdo, $id) : null;

if (!$doc || !org_document_is_file($doc)) {
    admin_flash('error', 'Документ не найден');
    header('Location: svedeniya.php');
    exit;
}

$slug = (string)($doc['section_slug'] ?? 'documents');
header('Location: edit_svedeniya_section.php?slug=' . urlencode($slug) . '&file_id=' . $id . '#files', true, 301);
exit;
