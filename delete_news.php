<?php

require_once '_auth.php';

require_once '_news_helpers.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: news.php');

    exit;

}



$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {

    header('Location: news.php');

    exit;

}



try {

    $stmt = $pdo->prepare('SELECT image_url FROM news WHERE id = ?');

    $stmt->execute([$id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);



    if ($row) {

        admin_news_delete_image($row['image_url']);

        $pdo->prepare('DELETE FROM news WHERE id = ?')->execute([$id]);

        admin_flash('success', 'Новость удалена');

    }

} catch (Exception $e) {

    admin_flash('error', $e->getMessage());

}



header('Location: news.php');

exit;

