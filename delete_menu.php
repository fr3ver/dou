<?php
require_once '_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$group_id = 0;

try {
    $stmt = $pdo->prepare('SELECT group_id FROM menu WHERE id = ? AND date IS NULL');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $group_id = (int)($row['group_id'] ?? 0);

    $pdo->prepare('DELETE FROM menu WHERE id = ? AND date IS NULL')->execute([$id]);
    admin_flash('success', 'Блюдо удалено');
} catch (Exception $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: menu.php' . ($group_id > 0 ? '?group_id=' . $group_id : ''));
exit;
