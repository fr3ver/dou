<?php
require_once '../includes/config.php';
require_once '../includes/auth_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Сначала войдите в систему']);
    exit;
}

$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($current === '' || $new === '' || $confirm === '') {
    echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
    exit;
}

if (strlen($new) < 4) {
    echo json_encode(['success' => false, 'message' => 'Новый пароль — минимум 4 символа']);
    exit;
}

if ($new !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'Новый пароль и подтверждение не совпадают']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($current, $row['password'])) {
        echo json_encode(['success' => false, 'message' => 'Текущий пароль неверный']);
        exit;
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Пароль успешно изменён']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
}
м