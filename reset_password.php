<?php
require_once '_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$id       = (int)($_POST['id'] ?? 0);
$password = $_POST['password'] ?? '';

if ($id <= 0 || strlen($password) < 4) {
    admin_flash('error', 'Пароль должен быть не короче 4 символов');
    header('Location: edit_user.php?id=' . $id);
    exit;
}

try {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $id]);
    admin_flash('success', 'Новый пароль сохранён');
} catch (Exception $e) {
    admin_flash('error', 'Ошибка при сохранении пароля');
}

header('Location: edit_user.php?id=' . $id);
exit;
