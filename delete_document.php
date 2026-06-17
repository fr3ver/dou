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

    child_document_delete($pdo, $childId, $docType, $parentId);

    auth_set_flash('success', 'Документ удалён');

} catch (Throwable $e) {

    auth_set_flash('error', 'Не удалось удалить документ');

}



header('Location: documents.php');

exit;

