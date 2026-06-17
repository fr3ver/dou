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
    $error = club_enrollment_approve($pdo, $clubId, $childId, (int) $_SESSION['user_id']);
    if ($error !== null) {
        admin_flash('error', $error);
    } else {
        $childDob = $pdo->prepare('SELECT date_of_birth FROM children WHERE id = ?');
        $childDob->execute([$childId]);
        $dob = $childDob->fetchColumn() ?: null;
        $club = admin_club_find($pdo, $clubId);
        $warning = club_enrollment_age_warning($dob ?: null, $club['age_category'] ?? null);
        if ($warning && !$warning['fits']) {
            admin_flash('warning', 'Заявка одобрена, ребёнок записан. ' . $warning['message']);
        } else {
            admin_flash('success', 'Заявка одобрена, ребёнок записан в кружок');
        }
    }
} catch (Exception $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: club_applications.php?filter=pending');
exit;
