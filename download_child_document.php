<?php

require_once __DIR__ . '/includes/config.php';

require_once __DIR__ . '/includes/child_documents.php';



if (!isset($_SESSION['user_id'])) {

    header('Location: login.php');

    exit;

}



$childId = (int)($_GET['child_id'] ?? 0);

$docType = trim((string)($_GET['type'] ?? ''));

$userId = (int)$_SESSION['user_id'];

$roleId = (int)($_SESSION['role_id'] ?? 0);



if (!in_array($roleId, [1, 2, 3, 4], true)) {

    http_response_code(403);

    exit('Нет доступа');

}



$doc = ($childId > 0 && isset(child_document_types()[$docType]))

    ? child_document_get($pdo, $childId, $docType)

    : null;



if (!$doc || !child_document_user_can_view($pdo, $doc, $userId, $roleId)) {

    http_response_code(404);

    exit('Документ не найден');

}



$filePath = $doc['file_path'] ?? '';

$full = __DIR__ . '/' . $filePath;



if (!is_file($full) || !str_starts_with($filePath, 'uploads/child_docs/')) {

    http_response_code(404);

    exit('Файл не найден');

}



$inline = isset($_GET['inline']) && $_GET['inline'] === '1';

$mime = child_document_mime_type($filePath);

$filename = $doc['original_name'] ?: basename($filePath);

$disposition = ($inline && child_document_is_inline($filePath)) ? 'inline' : 'attachment';



header('Content-Type: ' . $mime);

header('Content-Length: ' . filesize($full));

header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $filename) . '"');

header('X-Content-Type-Options: nosniff');



readfile($full);

exit;

