<?php
require_once '_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: groups.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM children WHERE group_id = ?');
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        throw new Exception('Нельзя удалить группу с детьми');
    }

    $pdo->prepare("UPDATE employees SET group_id = NULL WHERE group_id = ?")->execute([$id]);
    $pdo->prepare('DELETE FROM `groups` WHERE id = ?')->execute([$id]);
    admin_flash('success', 'Группа удалена');
} catch (Exception $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: groups.php');
exit;
