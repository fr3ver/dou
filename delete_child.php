<?php
require_once '_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: children.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    $pdo->prepare('DELETE FROM children WHERE id = ?')->execute([$id]);
    admin_flash('success', 'Ребёнок удалён');
} catch (Exception $e) {
    admin_flash('error', 'Ошибка: ' . $e->getMessage());
}

header('Location: children.php');
exit;
