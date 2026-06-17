<?php

require_once '_auth.php';

require_once '_news_helpers.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: news.php');

    exit;

}



$id = (int)($_POST['id'] ?? 0);

$data = admin_news_collect_post($pdo);

$error = admin_news_validate($data);



if ($id <= 0 || $error) {

    admin_flash('error', $error ?: 'Некорректный запрос');

    header('Location: edit_news.php?id=' . $id);

    exit;

}



$stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');

$stmt->execute([$id]);

$existing = $stmt->fetch(PDO::FETCH_ASSOC);



if (!$existing) {

    admin_flash('error', 'Новость не найдена');

    header('Location: news.php');

    exit;

}



try {

    $image_url = $existing['image_url'];



    if (!empty($_POST['remove_image'])) {

        admin_news_delete_image($image_url);

        $image_url = null;

    }



    if (!empty($_FILES['image']['name'])) {

        admin_news_delete_image($image_url);

        $image_url = admin_news_upload_image($_FILES['image']);

    }



    $targetGroupIds = $data['for_all_groups'] ? null : entity_groups_json($data['group_ids']);

    $stmt = $pdo->prepare('UPDATE news SET title = ?, content = ?, image_url = ?, publish_date = ?,
        target_role = ?, target_group_ids = ? WHERE id = ?');

    $stmt->execute([
        $data['title'], $data['content'], $image_url, $data['publish_date'],
        $data['target_role'], $targetGroupIds, $id,
    ]);



    admin_flash('success', 'Новость обновлена');

    header('Location: news.php');

} catch (Exception $e) {

    admin_flash('error', $e->getMessage());

    header('Location: edit_news.php?id=' . $id);

}

exit;

