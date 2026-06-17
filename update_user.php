<?php
require_once '_auth.php';
require_once '_employee_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$id        = (int)($_POST['id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$role_id   = (int)($_POST['role_id'] ?? 0);

if ($id <= 0 || empty($full_name) || empty($username) || $role_id <= 0) {
    admin_flash('error', 'Заполните все обязательные поля');
    header('Location: edit_user.php?id=' . $id);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
    $stmt->execute([$username, $id]);
    if ($stmt->fetch()) {
        throw new Exception('Логин уже занят');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE users SET username = ?, full_name = ?, phone = ?, role_id = ? WHERE id = ?');
    $stmt->execute([$username, $full_name, $phone ?: null, $role_id, $id]);

    if ($role_id === 2) {
        admin_ensure_employee_record($pdo, $id, 'Сотрудник', null);
    } elseif ($role_id !== 4) {
        $pdo->prepare('DELETE FROM employees WHERE user_id = ?')->execute([$id]);
    }

    $pdo->commit();
    admin_flash('success', 'Пользователь обновлён');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    admin_flash('error', $e->getMessage());
}

header('Location: edit_user.php?id=' . $id);
exit;
