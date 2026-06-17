<?php
require_once '../includes/config.php';
require_once '../includes/chat_helpers.php';

header('Content-Type: application/json; charset=utf-8');
chat_json_auth();

$userId = (int)$_SESSION['user_id'];
$roleId = (int)$_SESSION['role_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $convKey = trim((string)($_GET['conv'] ?? $_GET['conversation_id'] ?? ''));
    $afterId = (int)($_GET['after_id'] ?? 0);

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

    $messages = chat_get_messages($pdo, $conv, $userId, $roleId, $afterId);
    chat_mark_read($pdo, $conv, $userId);

    echo json_encode([
        'success' => true,
        'conversation' => [
            'id'        => chat_conv_key($conv),
            'conv_type' => $conv['conv_type'],
            'title'     => chat_conversation_title($pdo, $conv, $userId, $roleId),
            'subtitle'  => chat_conversation_subtitle($pdo, $conv, $userId, $roleId),
            'can_send'  => chat_user_can_send($pdo, $userId, $roleId, $conv),
        ],
        'messages' => $messages,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $convKey = trim((string)($_POST['conv'] ?? $_POST['conversation_id'] ?? ''));
    $body = (string)($_POST['body'] ?? '');

    $conv = chat_parse_conv_key($convKey);
    if ($conv === null) {
        echo json_encode(['success' => false, 'message' => 'Укажите чат'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $message = chat_send_message($pdo, $conv, $userId, $roleId, $body);
        echo json_encode(['success' => true, 'message' => $message], JSON_UNESCAPED_UNICODE);
    } catch (InvalidArgumentException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Не удалось отправить сообщение'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Неверный метод'], JSON_UNESCAPED_UNICODE);
