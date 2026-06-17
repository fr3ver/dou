<?php
require_once '../includes/config.php';
require_once '../includes/auth_helpers.php';
require_once '../includes/lk_helpers.php';

lk_require_role(1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$text = trim($_POST['text'] ?? '');
$rating = (int)($_POST['rating'] ?? 0);

if ($text === '' || $rating < 1 || $rating > 5) {
    auth_set_flash('error', 'Заполните текст и выберите оценку от 1 до 5');
    header('Location: dashboard.php');
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO reviews (parent_id, text, rating, status) VALUES (?, ?, ?, ?)');
    $stmt->execute([(int)$_SESSION['user_id'], $text, $rating, 'pending']);
    auth_set_flash('success', 'Отзыв отправлен и ожидает модерации');
} catch (Exception $e) {
    auth_set_flash('error', 'Не удалось сохранить отзыв');
}

header('Location: dashboard.php');
exit;
