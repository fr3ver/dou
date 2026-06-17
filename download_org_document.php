<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/org_documents.php';

$id = (int)($_GET['id'] ?? 0);
$inline = isset($_GET['inline']);
$isLoggedIn = isset($_SESSION['user_id']);
$roleId = (int)($_SESSION['role_id'] ?? 0);

$doc = $id > 0 ? org_document_get($pdo, $id) : null;
if (!$doc || !org_document_is_file($doc) || !org_document_user_can_download($doc, $isLoggedIn, $roleId)) {
    http_response_code(404);
    exit('Документ не найден');
}

$filePath = $doc['file_path'] ?? '';
$full = __DIR__ . '/' . $filePath;

if (!is_file($full) || !str_starts_with($filePath, 'uploads/org_docs/')) {
    http_response_code(404);
    exit('Файл не найден');
}

$mime = org_document_mime_type($filePath);
$filename = $doc['original_name'] ?: basename($filePath);
$disposition = ($inline && org_document_is_inline($filePath)) ? 'inline' : 'attachment';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($full));
header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $filename) . '"');
header('X-Content-Type-Options: nosniff');

readfile($full);
exit;
