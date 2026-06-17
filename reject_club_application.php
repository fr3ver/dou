<?php
require_once '_auth.php';
require_once __DIR__ . '/../includes/club_enrollment.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: club_applications.php');
    exit;
}

$clubId = (int) ($_POST['club_id'] ?? 0);
$childId = (int) ($_POST['child_id'] ?? 0);

if ($clubId <= 0 || $childId <= 0) {
    header('Location: club_applications.php');
    exit;
}

try {
    $error = club_enrollment_reject($pdo, $clubId, $childId, (int) $_SESSION['user_id']);
    if ($error !== null) {
        admin_flash('error', $error);
    } else {
        admin_flash('success', 'Заявка отклонена');
    }
} catch (Exception $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: club_applications.php?filter=pending');
exit;
