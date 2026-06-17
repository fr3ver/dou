<?php
require_once '_auth.php';
require_once '_employee_helpers.php';
require_once '_photo_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employees.php');
    exit;
}

$id        = (int)($_POST['id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$position  = trim($_POST['position'] ?? '');
$group_id  = (int)($_POST['group_id'] ?? 0);
$remove_photo = !empty($_POST['remove_photo']);
$club_ids  = admin_position_skips_group($position) ? [] : admin_collect_employee_club_ids();

if ($id <= 0 || $full_name === '' || $position === '') {
    admin_flash('error', 'Заполните все обязательные поля');
    header('Location: edit_employee.php?id=' . $id);
    exit;
}

$stmt = $pdo->prepare('SELECT e.*, u.full_name, u.photo_url FROM employees e JOIN users u ON e.user_id = u.id WHERE e.id = ?');
$stmt->execute([$id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    admin_flash('error', 'Сотрудник не найден');
    header('Location: employees.php');
    exit;
}

if (!admin_position_is_valid($pdo, $position, $emp['position'] ?? null)) {
    admin_flash('error', 'Некорректная должность');
    header('Location: edit_employee.php?id=' . $id);
    exit;
}

$group_id = admin_normalize_employee_group($position, $group_id);

$old_photo = $emp['photo_url'] ?? null;
$new_upload = null;

try {
    $photo_url = admin_photo_update(
        $pdo,
        (int)$emp['user_id'],
        $old_photo,
        $_FILES['photo'] ?? [],
        $remove_photo
    );

    if ($photo_url !== $old_photo && $photo_url !== null && str_starts_with($photo_url, 'uploads/teachers/')) {
        $new_upload = $photo_url;
    }

    $pdo->beginTransaction();

    $role_id = admin_role_id_for_position($position);
    $pdo->prepare('UPDATE users SET full_name = ?, phone = ?, photo_url = ?, role_id = ? WHERE id = ?')
        ->execute([$full_name, $phone ?: null, $photo_url, $role_id, $emp['user_id']]);

    admin_sync_employee(
        $pdo,
        $id,
        (int)$emp['user_id'],
        $position,
        $group_id,
        $emp['position'],
        $emp['group_id'] ? (int)$emp['group_id'] : null
    );

    admin_sync_employee_clubs($pdo, $id, $club_ids);
    admin_save_employee_profile($pdo, $id, admin_collect_employee_profile($position));

    $pdo->commit();
    admin_photo_finalize($old_photo, $photo_url, $remove_photo);
    admin_flash('success', 'Данные сотрудника обновлены');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($new_upload) {
        admin_photo_delete($new_upload);
    }
    admin_flash('error', $e->getMessage());
}

header('Location: edit_employee.php?id=' . $id);
exit;
