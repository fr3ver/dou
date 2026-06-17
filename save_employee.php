<?php
require_once '_auth.php';
require_once '_employee_helpers.php';
require_once '_photo_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_employee.php');
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$position  = trim($_POST['position'] ?? '');
$group_id  = (int)($_POST['group_id'] ?? 0);
$password  = $_POST['password'] ?? '';

if ($full_name === '' || $username === '' || $password === '' || $position === '') {
    admin_flash('error', 'Заполните все обязательные поля');
    header('Location: add_employee.php');
    exit;
}

if (!admin_position_is_valid($pdo, $position)) {
    admin_flash('error', 'Некорректная должность');
    header('Location: add_employee.php');
    exit;
}

$group_id = admin_normalize_employee_group($position, $group_id);
$club_ids = admin_position_skips_group($position) ? [] : admin_collect_employee_club_ids();
$photo_url = null;

try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new Exception('Логин уже занят');
    }

    $photo_url = admin_photo_upload($_FILES['photo'] ?? []);

    $pdo->beginTransaction();

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $role_id = admin_role_id_for_position($position);
    $stmt = $pdo->prepare('INSERT INTO users (username, password, full_name, phone, photo_url, role_id) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$username, $hashed, $full_name, $phone ?: null, $photo_url, $role_id]);
    $user_id = (int)$pdo->lastInsertId();

    $employee_id = admin_ensure_employee_record($pdo, $user_id, $position, null);

    admin_sync_employee($pdo, $employee_id, $user_id, $position, $group_id);
    admin_sync_employee_clubs($pdo, $employee_id, $club_ids);
    admin_save_employee_profile($pdo, $employee_id, admin_collect_employee_profile($position));

    $pdo->commit();
    $accessNote = $role_id === 4 ? ' Доступ в админ-панель (старший воспитатель).' : '';
    admin_flash('success', 'Сотрудник «' . $full_name . '» создан. Логин: ' . $username . '.' . $accessNote);
    header('Location: employees.php');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    admin_photo_delete($photo_url ?? null);
    admin_flash('error', $e->getMessage());
    header('Location: add_employee.php');
}
exit;
