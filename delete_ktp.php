<?php
require_once '_auth.php';
require_once __DIR__ . '/../includes/ktp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ktp.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $doc = org_document_get($pdo, $id);
    if ($doc && ($doc['section_slug'] ?? '') === ktp_section_slug()) {
        org_document_delete($pdo, $id);
        admin_flash('success', 'Файл удалён');
    }
}

header('Location: ktp.php');
exit;
