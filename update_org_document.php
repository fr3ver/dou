<?php
require_once '_auth.php';
require_once __DIR__ . '/../includes/org_documents.php';

$id = (int)($_POST['id'] ?? 0);
$doc = $id > 0 ? org_document_get($pdo, $id) : null;
$slug = $doc ? (string)($doc['section_slug'] ?? 'documents') : 'documents';
header('Location: edit_svedeniya_section.php?slug=' . urlencode($slug) . ($id ? '&file_id=' . $id : '') . '#files', true, 301);
exit;
