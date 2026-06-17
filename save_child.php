<?php
require_once '_auth.php';
require_once '_allergies.php';
require_once '_allergy_fields.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_child.php');
    exit;
}

$full_name     = trim($_POST['full_name'] ?? '');
$date_of_birth = $_POST['date_of_birth'] ?? '';
$group_id      = (int)($_POST['group_id'] ?? 0);
$parent_id     = (int)($_POST['parent_id'] ?? 0);
$has_tnr       = (int)($_POST['has_tnr'] ?? 0);
$allergy_ids   = admin_collect_child_allergy_ids();

if (empty($full_name) || empty($date_of_birth) || $group_id <= 0 || $parent_id <= 0) {
    admin_flash('error', 'Заполните все обязательные поля');
    header('Location: add_child.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO children
        (full_name, date_of_birth, group_id, has_tnr, parent_id)
        VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $full_name, $date_of_birth, $group_id, $has_tnr, $parent_id
    ]);
    $child_id = (int)$pdo->lastInsertId();
    admin_sync_child_allergies($pdo, $child_id, $allergy_ids);

    $pdo->commit();
    admin_flash('success', 'Ребёнок «' . $full_name . '» добавлен');
    header('Location: children.php');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    admin_flash('error', $e->getMessage());
    header('Location: add_child.php');
}
exit;
