<?php
require_once '../includes/config.php';
require_once '../includes/club_attendance.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || (int)$_SESSION['role_id'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

$clubId = (int)($_POST['club_id'] ?? 0);
$childId = (int)($_POST['child_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

if ($clubId <= 0 || $childId <= 0 || !in_array($status, ['present', 'absent'], true)) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id FROM employees WHERE user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $employeeId = (int)$stmt->fetchColumn();

    if ($employeeId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Профиль сотрудника не найден']);
        exit;
    }

    club_attendance_mark($pdo, $employeeId, $clubId, $childId, $status);

    $labels = ['present' => 'Присутствует', 'absent' => 'Отсутствует'];

    echo json_encode([
        'success' => true,
        'message' => 'Отметка сохранена',
        'status' => $status,
        'status_label' => $labels[$status],
    ]);
} catch (RuntimeException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
}
