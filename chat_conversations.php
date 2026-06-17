<?php
require_once '../includes/config.php';
require_once '../includes/chat_helpers.php';

header('Content-Type: application/json; charset=utf-8');
chat_json_auth();

$userId = (int)$_SESSION['user_id'];
$roleId = (int)$_SESSION['role_id'];

try {
    $conversations = chat_list_conversations($pdo, $userId, $roleId);
    echo json_encode([
        'success' => true,
        'conversations' => $conversations,
        'unread_total' => array_sum(array_column($conversations, 'unread_count')),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка загрузки чатов'], JSON_UNESCAPED_UNICODE);
}
