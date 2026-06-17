<?php
require_once '_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: clubs.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    admin_flash('error', 'Некорректный идентификатор');
    header('Location: clubs.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT name FROM clubs WHERE id = ?');
    $stmt->execute([$id]);
    $club = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$club) {
        admin_flash('error', 'Кружок не найден');
        header('Location: clubs.php');
        exit;
    }

    $pdo->prepare('DELETE FROM clubs WHERE id = ?')->execute([$id]);
    admin_flash('success', 'Кружок «' . $club['name'] . '» удалён');
} catch (Exception $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: clubs.php');
exit;
