<?php
require_once '../includes/config.php';
require_once '../includes/auth_helpers.php';
require_once '../includes/lk_helpers.php';
require_once '../includes/club_enrollment.php';

lk_require_role(1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php#clubs');
    exit;
}

$parentId = (int) $_SESSION['user_id'];
$clubId = (int) ($_POST['club_id'] ?? 0);
$childId = (int) ($_POST['child_id'] ?? 0);

if ($clubId <= 0 || $childId <= 0) {
    auth_set_flash('error', 'Укажите кружок и ребёнка');
    header('Location: dashboard.php#clubs');
    exit;
}

try {
    $error = club_enrollment_apply($pdo, $clubId, $childId, $parentId);
    if ($error !== null) {
        auth_set_flash('error', $error);
    } else {
        auth_set_flash('success', 'Заявка отправлена. После одобрения администратором ребёнок появится в составе кружка.');
    }
} catch (Exception $e) {
    auth_set_flash('error', 'Не удалось отправить заявку. Обратитесь к администратору.');
}

header('Location: dashboard.php#clubs');
exit;
