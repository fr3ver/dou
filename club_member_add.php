<?php
require_once '_auth.php';
require_once '_club_helpers.php';
require_once __DIR__ . '/../includes/club_enrollment.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: clubs.php');
    exit;
}

$clubId = (int) ($_POST['club_id'] ?? 0);
$childId = (int) ($_POST['child_id'] ?? 0);

if ($clubId <= 0 || $childId <= 0) {
    admin_flash('error', 'Укажите кружок и ребёнка');
    header('Location: clubs.php');
    exit;
}

$error = admin_club_add_member($pdo, $clubId, $childId);
if ($error) {
    admin_flash('error', $error);
} else {
    $stmt = $pdo->prepare('SELECT full_name, date_of_birth FROM children WHERE id = ?');
    $stmt->execute([$childId]);
    $child = $stmt->fetch(PDO::FETCH_ASSOC);
    $name = $child['full_name'] ?? 'Ребёнок';
    $club = admin_club_find($pdo, $clubId);
    $warning = club_enrollment_age_warning($child['date_of_birth'] ?? null, $club['age_category'] ?? null);
    if ($warning && !$warning['fits']) {
        admin_flash('warning', '«' . $name . '» записан. ' . $warning['message']);
    } else {
        admin_flash('success', '«' . $name . '» записан в кружок');
    }
}

header('Location: club_members.php?id=' . $clubId);
exit;
