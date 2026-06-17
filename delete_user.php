<?php
require_once '_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0 || $id === (int)$_SESSION['user_id']) {
    admin_flash('error', 'Нельзя удалить этот аккаунт');
    header('Location: users.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM children WHERE parent_id = ?');
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        throw new Exception('Нельзя удалить: у пользователя есть привязанные дети');
    }

    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    admin_flash('success', 'Пользователь удалён');
} catch (Exception $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: users.php');
exit;
