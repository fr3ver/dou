<?php
// api/login.php

require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Введите логин и пароль']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, username, password, full_name, role_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Успешный вход
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id'];

            echo json_encode([
                'success' => true,
                'message' => 'Вход выполнен успешно',
                'role_id' => $user['role_id']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Неверный логин или пароль']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>
