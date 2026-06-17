<?php
require_once '_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: groups.php');
    exit;
}

$id              = (int)($_POST['id'] ?? 0);
require_once '_group_helpers.php';

$name                = trim($_POST['name'] ?? '');
$description         = trim($_POST['description'] ?? '');
$activities_features = trim($_POST['activities_features'] ?? '');
$education_program   = trim($_POST['education_program'] ?? '');
$age_category        = trim($_POST['age_category'] ?? '');
$capacity            = (int) ($_POST['capacity'] ?? 25);
$care_mode           = trim($_POST['care_mode'] ?? '12h');
$staff_user_ids      = array_map('intval', $_POST['staff_user_ids'] ?? []);

require_once __DIR__ . '/../includes/parent_fee.php';
$care_mode = parent_fee_normalize_care_mode($care_mode);

if ($id <= 0 || empty($name)) {
    admin_flash('error', 'Заполните обязательные поля');
    header('Location: edit_group.php?id=' . $id);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        UPDATE `groups`
        SET name = ?, description = ?, activities_features = ?,
            education_program = ?, age_category = ?, care_mode = ?, capacity = ?
        WHERE id = ?
    ');
    $stmt->execute([
        $name,
        $description !== '' ? $description : null,
        $activities_features !== '' ? $activities_features : null,
        $education_program !== '' ? $education_program : null,
        $age_category !== '' ? $age_category : null,
        $care_mode,
        $capacity,
        $id,
    ]);

    admin_sync_group_staff($pdo, $id, $staff_user_ids);

    $pdo->commit();
    admin_flash('success', 'Группа обновлена');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    admin_flash('error', $e->getMessage());
}

header('Location: edit_group.php?id=' . $id);
exit;
