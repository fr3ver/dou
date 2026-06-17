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
$status   = trim($_POST['status'] ?? '');

if ($child_id <= 0 || !in_array($status, ['present', 'absent'], true)) {
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
        SELECT id, arrival_time FROM attendance
        WHERE child_id = ? AND club_id = ? AND `date` = CURDATE()
    ');
    $stmt->execute([$child_id, $groupClubId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($status === 'present') {
            $arrival = $existing['arrival_time'] ?: date('H:i:s');
            $stmt = $pdo->prepare('
                UPDATE attendance
                SET status = ?, group_id = ?, created_by = ?,
                    arrival_time = ?, departure_time = NULL
                WHERE id = ?
            ');
            $stmt->execute([$status, $employee['group_id'], $employee['id'], $arrival, $existing['id']]);
        } else {
            $stmt = $pdo->prepare('
                UPDATE attendance
                SET status = ?, group_id = ?, created_by = ?,
                    arrival_time = NULL, departure_time = NULL
                WHERE id = ?
            ');
            $stmt->execute([$status, $employee['group_id'], $employee['id'], $existing['id']]);
        }
    } else {
        $arrival = $status === 'present' ? date('H:i:s') : null;
        $stmt = $pdo->prepare('
            INSERT INTO attendance (child_id, `date`, status, arrival_time, group_id, club_id, created_by)
            VALUES (?, CURDATE(), ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $child_id,
            $status,
            $arrival,
            $employee['group_id'],
            $groupClubId,
            $employee['id'],
        ]);
    }

    $stmt = $pdo->prepare('
        SELECT arrival_time, departure_time
        FROM attendance
        WHERE child_id = ? AND club_id = ? AND `date` = CURDATE()
        LIMIT 1
    ');
    $stmt->execute([$child_id, $groupClubId]);
    $times = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $labels = ['present' => 'Присутствует', 'absent' => 'Отсутствует'];

    echo json_encode([
        'success' => true,
        'message' => 'Отметка сохранена',
        'status' => $status,
        'status_label' => $labels[$status],
        'arrival_time' => $times['arrival_time'] ?? null,
        'departure_time' => $times['departure_time'] ?? null,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
}
