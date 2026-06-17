<?php
require_once '_auth.php';
require_once '_allergies.php';
require_once '_allergy_fields.php';

$id             = (int)($_POST['id'] ?? 0);
$full_name      = trim($_POST['full_name'] ?? '');
$date_of_birth  = $_POST['date_of_birth'] ?? '';
$group_id       = (int)($_POST['group_id'] ?? 0);
$parent_id      = (int)($_POST['parent_id'] ?? 0);
$has_tnr        = (int)($_POST['has_tnr'] ?? 0);
$allergy_ids    = admin_collect_child_allergy_ids();

if ($id <= 0 || empty($full_name) || empty($date_of_birth) || $group_id <= 0 || $parent_id <= 0) {
    admin_flash('error', 'Заполните все обязательные поля');
    header('Location: edit_child.php?id=' . $id);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE children SET
        full_name = ?, date_of_birth = ?, group_id = ?, parent_id = ?,
        has_tnr = ?
        WHERE id = ?");
    $stmt->execute([
        $full_name, $date_of_birth, $group_id, $parent_id,
        $has_tnr, $id
    ]);
    admin_sync_child_allergies($pdo, $id, $allergy_ids);

    $pdo->commit();
    admin_flash('success', 'Данные ребёнка обновлены');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    admin_flash('error', $e->getMessage());
}

header('Location: edit_child.php?id=' . $id);
exit;
