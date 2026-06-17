<?php
require_once '../includes/config.php';
require_once '../includes/chat_helpers.php';

header('Content-Type: application/json; charset=utf-8');
chat_json_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$roleId = (int)$_SESSION['role_id'];
$convKey = trim((string)($_POST['conv'] ?? $_POST['conversation_id'] ?? ''));

$conv = chat_parse_conv_key($convKey);
if ($conv === null) {
    echo json_encode(['success' => false, 'message' => 'Укажите чат'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!chat_user_can_access($pdo, $userId, $roleId, $conv)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Нет доступа'], JSON_UNESCAPED_UNICODE);
    exit;
}

chat_mark_read($pdo, $conv, $userId);
echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
