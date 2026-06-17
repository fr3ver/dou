<?php
require_once '_auth.php';
require_once '_club_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: clubs.php');
    exit;
}

$clubId = (int) ($_POST['club_id'] ?? 0);
$childId = (int) ($_POST['child_id'] ?? 0);

if ($clubId <= 0 || $childId <= 0) {
    admin_flash('error', 'Некорректные данные');
    header('Location: clubs.php');
    exit;
}

$error = admin_club_remove_member($pdo, $clubId, $childId);
admin_flash($error ? 'error' : 'success', $error ?: 'Ребёнок исключён из кружка');

header('Location: club_members.php?id=' . $clubId);
exit;
