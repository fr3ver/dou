<?php
require_once '_auth.php';
require_once '_club_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: clubs.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$data = admin_club_collect_post();
$error = admin_club_validate($pdo, $data, $id);

if ($error || $id <= 0) {
    admin_flash('error', $error ?: 'Некорректный идентификатор');
    header('Location: ' . ($id > 0 ? 'edit_club.php?id=' . $id : 'clubs.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM clubs WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    admin_flash('error', 'Кружок не найден');
    header('Location: clubs.php');
    exit;
}

try {
    $stmt = $pdo->prepare('
        UPDATE clubs
        SET name = ?, description = ?, activities_features = ?, education_program = ?,
            age_category = ?, schedule = ?, max_participants = ?, teacher_id = ?
        WHERE id = ?
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
        $id,
    ]);
    admin_flash('success', 'Кружок «' . $data['name'] . '» обновлён');
    header('Location: clubs.php');
} catch (Exception $e) {
    admin_flash('error', $e->getMessage());
    header('Location: edit_club.php?id=' . $id);
}
exit;
