<?php

require_once '_auth.php';
require_once __DIR__ . '/../includes/staff_materials.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: staff_materials.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    $doc = $id > 0 ? org_document_get($pdo, $id) : null;
    if (!$doc || ($doc['section_slug'] ?? '') !== staff_materials_section_slug()) {
        throw RuntimeException('Файл не найден');
    }
    org_document_delete($pdo, $id);
    admin_flash('success', 'Файл удалён');
} catch (Throwable $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: staff_materials.php');
exit;
