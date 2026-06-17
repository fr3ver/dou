<?php
require_once '../includes/config.php';
require_once '../includes/attendance_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

$child_id = (int)($_POST['child_id'] ?? 0);

if ($child_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, group_id FROM employees WHERE user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee || !$employee['group_id']) {
        echo json_encode(['success' => false, 'message' => 'Группа не назначена']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM children WHERE id = ? AND group_id = ?');
    $stmt->execute([$child_id, $employee['group_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ребёнок не найден в вашей группе']);
        exit;
    }

    $groupClubId = attendance_group_club_id();
    $stmt = $pdo->prepare('
        SELECT id, status, arrival_time
        FROM attendance
        WHERE child_id = ? AND club_id = ? AND `date` = CURDATE()
        LIMIT 1
    ');
    $stmt->execute([$child_id, $groupClubId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing || $existing['status'] !== 'present') {
        echo json_encode(['success' => false, 'message' => 'Сначала отметьте ребёнка как присутствующего']);
        exit;
    }

    $departure = date('H:i:s');
    $arrival = $existing['arrival_time'] ?: $departure;

    $stmt = $pdo->prepare('
        UPDATE attendance
        SET departure_time = ?, arrival_time = COALESCE(arrival_time, ?), created_by = ?
        WHERE id = ?
    ');
    $stmt->execute([$departure, $arrival, $employee['id'], $existing['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Время ухода сохранено',
        'arrival_time' => $arrival,
        'departure_time' => $departure,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
}
