<?php

function admin_photo_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new Exception('Ошибка загрузки файла');
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new Exception('Допустимы только изображения: jpg, png, gif, webp');
    }

    $upload_dir = __DIR__ . '/../uploads/teachers/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('Не удалось сохранить фото');
    }

    return 'uploads/teachers/' . $filename;
}

function admin_photo_delete(?string $photo_url): void
{
    if (!$photo_url || !str_starts_with($photo_url, 'uploads/teachers/')) {
        return;
    }

    $path = __DIR__ . '/../' . $photo_url;
    if (is_file($path)) {
        unlink($path);
    }
}

function admin_photo_update(PDO $pdo, int $userId, ?string $currentUrl, array $file, bool $remove): ?string
{
    unset($pdo, $userId);

    if ($remove) {
        return null;
    }

    if (!empty($file['name'])) {
        return admin_photo_upload($file);
    }

    return $currentUrl;
}

function admin_photo_finalize(?string $oldUrl, ?string $newUrl, bool $remove): void
{
    if ($oldUrl === $newUrl) {
        return;
    }

    if ($remove || ($newUrl && $oldUrl && $newUrl !== $oldUrl)) {
        admin_photo_delete($oldUrl);
    }
}

function admin_render_photo_field(?string $photoUrl, string $name = 'photo'): void
{
    ?>
    <div class="mb-4">
        <label class="form-label fw-semibold">Фото</label>
        <?php if ($photoUrl): ?>
            <div class="mb-2">
                <img src="../<?= htmlspecialchars($photoUrl) ?>" alt=""
                     class="rounded border" style="width: 120px; height: 120px; object-fit: cover;">
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" name="remove_photo" value="1" id="remove_photo" class="form-check-input">
                <label class="form-check-label" for="remove_photo">Удалить текущее фото</label>
            </div>
        <?php endif; ?>
        <input type="file" name="<?= htmlspecialchars($name) ?>" class="form-control" accept="image/*">
    </div>
    <?php
}
