<?php
require_once '_auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: events.php'); exit; }
$id = (int)($_POST['id'] ?? 0);
try {
    $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
    admin_flash('success', 'Мероприятие удалено');
} catch (Exception $e) {
    admin_flash('error', $e->getMessage());
}
header('Location: events.php');
exit;
