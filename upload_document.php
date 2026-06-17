<?php
require_once '../includes/config.php';
require_once '../includes/auth_helpers.php';
require_once '../includes/lk_helpers.php';
require_once '../includes/child_documents.php';

lk_require_role(1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: documents.php');
    exit;
}

$childId = (int)($_POST['child_id'] ?? 0);
$docType = trim((string)($_POST['doc_type'] ?? ''));
$parentId = (int)$_SESSION['user_id'];

try {
    child_document_upload($pdo, $childId, $parentId, $docType, $_FILES['document'] ?? []);
    auth_set_flash('success', 'Документ «' . child_document_type_label($docType) . '» загружен');
} catch (InvalidArgumentException $e) {
    auth_set_flash('error', $e->getMessage());
} catch (Throwable $e) {
    auth_set_flash('error', 'Не удалось загрузить документ');
}

header('Location: documents.php');
exit;
