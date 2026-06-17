<?php

require_once __DIR__ . '/../includes/entity_groups.php';

function admin_news_upload_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new Exception('Допустимы только изображения: jpg, png, gif, webp');
    }

    $upload_dir = __DIR__ . '/../uploads/news/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('Не удалось загрузить изображение');
    }

    return 'uploads/news/' . $filename;
}

function admin_news_delete_image(?string $image_url): void
{
    if (!$image_url || !str_starts_with($image_url, 'uploads/news/')) {
        return;
    }

    $path = __DIR__ . '/../' . $image_url;
    if (is_file($path)) {
        unlink($path);
    }
}

function admin_news_target_roles(): array
{
    return [
        'all'      => 'Все',
        'parent'   => 'Родители',
        'employee' => 'Сотрудники',
    ];
}

function admin_news_collect_post(PDO $pdo): array
{
    $target_role = $_POST['target_role'] ?? 'all';
    $roles = admin_news_target_roles();
    if (!isset($roles[$target_role])) {
        $target_role = 'all';
    }

    $for_all_groups = isset($_POST['for_all_groups']);
    $group_ids = $for_all_groups ? [] : entity_groups_collect_post_ids();

    return [
        'title'          => trim($_POST['title'] ?? ''),
        'content'        => trim($_POST['content'] ?? ''),
        'publish_date'   => $_POST['publish_date'] ?? date('Y-m-d'),
        'target_role'    => $target_role,
        'for_all_groups' => $for_all_groups,
        'group_ids'      => $group_ids,
    ];
}

function admin_news_validate(array $data): ?string
{
    if ($data['title'] === '') {
        return 'Укажите заголовок';
    }
    if ($data['content'] === '') {
        return 'Укажите текст новости';
    }
    if (empty($data['publish_date'])) {
        return 'Укажите дату публикации';
    }
    return null;
}
