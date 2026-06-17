<?php
require_once '_auth.php';
require_once '_club_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_club.php');
    exit;
}

$data = admin_club_collect_post();
$error = admin_club_validate($pdo, $data);

if ($error) {
    admin_flash('error', $error);
    header('Location: add_club.php');
    exit;
}

try {
    $stmt = $pdo->prepare('
        INSERT INTO clubs (name, description, activities_features, education_program, age_category, schedule, max_participants, teacher_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $data['name'],
        $data['description'] ?: null,
        $data['activities_features'] ?: null,
        $data['education_program'] ?: null,
        $data['age_category'] ?: null,
        $data['schedule'] ?: null,
        $data['max_participants'],
        $data['teacher_id'],
    ]);
    admin_flash('success', 'Кружок «' . $data['name'] . '» создан');
    header('Location: clubs.php');
} catch (Exception $e) {
    admin_flash('error', $e->getMessage());
    header('Location: add_club.php');
}
exit;
