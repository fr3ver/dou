<?php

require_once '_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: reviews.php'); exit; }

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) { header('Location: reviews.php'); exit; }

try {

    $pdo->prepare("UPDATE reviews SET status = 'rejected', moderated_at = NOW(), moderated_by = ? WHERE id = ?")

        ->execute([$_SESSION['user_id'], $id]);

    admin_flash('success', 'Отзыв отклонён');

} catch (Exception $e) {

    admin_flash('error', $e->getMessage());

}

header('Location: reviews.php?filter=pending');

exit;

