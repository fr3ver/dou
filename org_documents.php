<?php

function org_document_categories(): array
{
    return [
        'normative'   => 'Нормативные правовые акты',
        'supervisory' => 'Документы надзорных органов',
        'local'       => 'Локальные нормативные акты',
        'license'     => 'Лицензии и свидетельства',
        'other'       => 'Прочие документы',
    ];
}

function org_document_education_categories(): array
{
    return [
        'edu_program'    => 'Файл программы (в карточке)',
        'edu_docs'       => 'Документы',
        'edu_enrollment' => 'Численность обучающихся и языки образования',
        'edu_license'    => 'Лицензии на осуществление образовательной деятельности',
    ];
}

function org_document_nutrition_categories(): array
{
    return [
        'nutrition_sanpin' => 'СанПиН и санитарные нормы',
        'nutrition_local'  => 'Локальные акты по питанию',
        'nutrition_other'  => 'Прочие документы',
    ];
}

function org_document_categories_for_section(string $sectionSlug): array
{
    if ($sectionSlug === 'education') {
        return org_document_education_categories();
    }
    if ($sectionSlug === 'staff_materials') {
        require_once __DIR__ . '/staff_materials.php';

        return staff_materials_categories();
    }
    if ($sectionSlug === 'nutrition') {
        return org_document_nutrition_categories();
    }
    if ($sectionSlug === 'ktp') {
        require_once __DIR__ . '/ktp_catalog.php';
        $cats = [];
        foreach (ktp_catalog_items() as $item) {
            $cats[$item['key']] = $item['title'];
        }

        return $cats;
    }

    return org_document_categories();
}

function org_document_category_is_valid(string $category, string $sectionSlug = 'documents'): bool
{
    return isset(org_document_categories_for_section($sectionSlug)[$category]);
}

function org_document_category_exists(string $category): bool
{
    return isset(org_document_categories()[$category])
        || isset(org_document_education_categories()[$category])
        || isset(org_document_nutrition_categories()[$category]);
}

function org_document_category_label(string $category): string
{
    $category = trim($category);
    if ($category === '') {
        return '—';
    }

    if (!function_exists('ktp_catalog_item_by_key')) {
        require_once __DIR__ . '/ktp_catalog.php';
    }
    $ktpItem = ktp_catalog_item_by_key($category);
    if ($ktpItem !== null) {
        return (string)$ktpItem['title'];
    }

    return org_document_education_categories()[$category]
        ?? org_document_nutrition_categories()[$category]
        ?? org_document_categories()[$category]
        ?? $category;
}

function org_document_resolve_category(array $doc, string $sectionSlug = 'documents'): string
{
    $category = trim((string)($doc['category'] ?? ''));
    if ($category !== '' && org_document_category_is_valid($category, $sectionSlug)) {
        return $category;
    }
    if ($sectionSlug === 'education') {
        return 'edu_docs';
    }

    return 'other';
}

function org_document_allowed_extensions(): array
{
    return ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
}

function org_document_max_bytes(): int
{
    return 20 * 1024 * 1024;
}

function org_document_format_size(?int $bytes): string
{
    if ($bytes === null || $bytes <= 0) {
        return '—';
    }

    if ($bytes >= 1048576) {
        $mb = $bytes / 1048576;
        $formatted = $mb >= 10 ? (string)(int)round($mb) : number_format($mb, 1, ',', '');
        return $formatted . ' Мб';
    }

    return max(1, (int)round($bytes / 1024)) . ' Кб';
}

function org_document_mime_type(string $filePath): string
{
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    return match ($ext) {
        'pdf'        => 'application/pdf',
        'doc'        => 'application/msword',
        'docx'       => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg', 'jpeg'=> 'image/jpeg',
        'png'        => 'image/png',
        'webp'       => 'image/webp',
        default      => 'application/octet-stream',
    };
}

function org_document_is_inline(string $filePath): bool
{
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    return in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true);
}

function org_document_is_file(array $doc): bool
{
    return ($doc['item_type'] ?? 'file') === 'file';
}

function org_document_get(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM org_documents WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function org_document_text_get(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, section_slug AS slug, title, content, updated_at
        FROM org_documents
        WHERE item_type = 'text' AND section_slug = ?
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/** @return array<string, array<string, mixed>> */
function org_document_text_all_by_slug(PDO $pdo): array
{
    $rows = $pdo->query("
        SELECT section_slug AS slug, title, content, updated_at
        FROM org_documents
        WHERE item_type = 'text'
    ")->fetchAll(PDO::FETCH_ASSOC);

    $bySlug = [];
    foreach ($rows as $row) {
        $bySlug[$row['slug']] = $row;
    }

    return $bySlug;
}

function org_document_text_save(PDO $pdo, string $slug, string $title, string $content): void
{
    $slug = trim($slug);
    $title = trim($title);
    if ($slug === '') {
        throw InvalidArgumentException('Не указан раздел');
    }
    if ($title === '') {
        throw InvalidArgumentException('Укажите заголовок');
    }

    $existing = org_document_text_get($pdo, $slug);
    if ($existing && !empty($existing['id'])) {
        $stmt = $pdo->prepare("
            UPDATE org_documents
            SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP
            WHERE item_type = 'text' AND section_slug = ?
        ");
        $stmt->execute([$title, trim($content), $slug]);

        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO org_documents
            (item_type, section_slug, title, category, content, is_published, show_in_parent_lk, sort_order)
        VALUES ('text', ?, ?, 'other', ?, 1, 1, 0)
    ");
    $stmt->execute([$slug, $title, trim($content)]);
}

/** @return list<array<string, mixed>> */
function org_documents_for_section(
    PDO $pdo,
    string $sectionSlug,
    bool $publishedOnly = true,
    ?string $category = null
): array {
    $params = [$sectionSlug];
    $where = ["item_type = 'file'", 'section_slug = ?'];

    if ($publishedOnly) {
        $where[] = 'is_published = 1';
    }

    if ($category !== null && $category !== 'all' && org_document_category_is_valid($category, $sectionSlug)) {
        $where[] = 'category = ?';
        $params[] = $category;
    }

    $sql = "
        SELECT d.*, u.full_name AS uploader_name
        FROM org_documents d
        LEFT JOIN users u ON u.id = d.uploaded_by
        WHERE " . implode(' AND ', $where) . "
        ORDER BY d.sort_order ASC, d.title ASC, d.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function org_document_count_for_section(PDO $pdo, string $sectionSlug): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM org_documents
        WHERE item_type = 'file' AND section_slug = ?
    ");
    $stmt->execute([$sectionSlug]);

    return (int)$stmt->fetchColumn();
}

/** @return list<array<string, mixed>> */
function org_documents_list(
    PDO $pdo,
    ?string $category = null,
    bool $publishedOnly = true,
    bool $parentLkOnly = false
): array {
    $params = [];
    $where = ["item_type = 'file'"];

    if ($publishedOnly) {
        $where[] = 'is_published = 1';
    }

    if ($parentLkOnly) {
        $where[] = '(show_in_parent_lk = 1 OR category = \'license\')';
    }

    if ($category !== null && $category !== 'all' && org_document_category_exists($category)) {
        $where[] = 'category = ?';
        $params[] = $category;
    }

    $sql = "
        SELECT d.*, u.full_name AS uploader_name
        FROM org_documents d
        LEFT JOIN users u ON u.id = d.uploaded_by
        WHERE " . implode(' AND ', $where) . "
        ORDER BY d.sort_order ASC, d.title ASC, d.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * @param list<array<string, mixed>> $documents
 * @return array<string, list<array<string, mixed>>>
 */
function org_documents_group_by_category(array $documents): array
{
    $grouped = [];
    foreach (array_keys(org_document_categories()) as $cat) {
        $grouped[$cat] = [];
    }

    foreach ($documents as $doc) {
        $cat = $doc['category'] ?? 'other';
        if (!isset($grouped[$cat])) {
            $grouped[$cat] = [];
        }
        $grouped[$cat][] = $doc;
    }

    return array_filter($grouped, static fn(array $items): bool => $items !== []);
}

function org_document_user_can_download(array $doc, bool $isLoggedIn, int $roleId): bool
{
    if (($doc['section_slug'] ?? '') === 'staff_materials') {
        return $isLoggedIn && in_array($roleId, [2, 3, 4], true);
    }

    if (!empty($doc['is_published'])) {
        return true;
    }

    return $isLoggedIn && in_array($roleId, [3, 4], true);
}

function org_document_upload_file(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw InvalidArgumentException('Выберите файл');
    }

    $size = (int)($file['size'] ?? 0);
    if ($size > org_document_max_bytes()) {
        throw InvalidArgumentException('Файл слишком большой (максимум 20 МБ)');
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, org_document_allowed_extensions(), true)) {
        throw InvalidArgumentException('Допустимы: PDF, DOC, DOCX, JPG, PNG, WEBP');
    }

    $uploadDir = __DIR__ . '/../uploads/org_docs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw RuntimeException('Не удалось сохранить файл');
    }

    return [
        'file_path'     => 'uploads/org_docs/' . $storedName,
        'original_name' => basename((string)($file['name'] ?? $storedName)),
        'file_size'     => $size,
    ];
}

function org_document_remove_file(?string $filePath): void
{
    if (!$filePath || !str_starts_with($filePath, 'uploads/org_docs/')) {
        return;
    }

    $full = __DIR__ . '/../' . $filePath;
    if (is_file($full)) {
        unlink($full);
    }
}

function org_document_save(PDO $pdo, array $data, ?array $file, int $userId, ?int $id = null): int
{
    $title = trim($data['title'] ?? '');
    $sectionSlug = trim($data['section_slug'] ?? 'documents');
    $category = $data['category'] ?? 'other';
    $sortOrder = (int)($data['sort_order'] ?? 0);
    $isPublished = !empty($data['is_published']) ? 1 : 0;
    $showInParent = !empty($data['show_in_parent_lk']) ? 1 : 0;

    if ($title === '') {
        throw InvalidArgumentException('Укажите название документа');
    }
    if (!org_document_category_is_valid($category, $sectionSlug)) {
        $category = $sectionSlug === 'education' ? 'edu_docs' : 'other';
    }
    if ($category === 'license') {
        $showInParent = 1;
    }

    if ($id === null) {
        if ($sectionSlug === '') {
            $sectionSlug = 'documents';
        }
        if ($file === null) {
            throw InvalidArgumentException('Прикрепите файл');
        }
        $uploaded = org_document_upload_file($file);
        $stmt = $pdo->prepare("
            INSERT INTO org_documents
                (item_type, section_slug, title, category, file_path, original_name, file_size, sort_order, is_published, show_in_parent_lk, uploaded_by)
            VALUES ('file', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $sectionSlug, $title, $category, $uploaded['file_path'], $uploaded['original_name'], $uploaded['file_size'],
            $sortOrder, $isPublished, $showInParent, $userId,
        ]);

        return (int)$pdo->lastInsertId();
    }

    $existing = org_document_get($pdo, $id);
    if (!$existing) {
        throw RuntimeException('Документ не найден');
    }
    if (!org_document_is_file($existing)) {
        throw RuntimeException('Запись не является файлом');
    }
    if ($sectionSlug === '') {
        $sectionSlug = (string)($existing['section_slug'] ?? 'documents');
    }

    $filePath = $existing['file_path'];
    $originalName = $existing['original_name'];
    $fileSize = $existing['file_size'];

    if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $uploaded = org_document_upload_file($file);
        org_document_remove_file($filePath);
        $filePath = $uploaded['file_path'];
        $originalName = $uploaded['original_name'];
        $fileSize = $uploaded['file_size'];
    }

    $stmt = $pdo->prepare("
        UPDATE org_documents SET
            section_slug = ?, title = ?, category = ?, file_path = ?, original_name = ?, file_size = ?,
            sort_order = ?, is_published = ?, show_in_parent_lk = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $sectionSlug, $title, $category, $filePath, $originalName, $fileSize,
        $sortOrder, $isPublished, $showInParent, $id,
    ]);

    return $id;
}

function org_document_delete(PDO $pdo, int $id): void
{
    $doc = org_document_get($pdo, $id);
    if (!$doc) {
        throw RuntimeException('Документ не найден');
    }
    if (!org_document_is_file($doc)) {
        throw RuntimeException('Текстовые разделы удаляются только из раздела «Сведения»');
    }

    org_document_remove_file($doc['file_path'] ?? '');
    $pdo->prepare('DELETE FROM org_documents WHERE id = ?')->execute([$id]);
}

function org_document_download_url(int $id, bool $inline = false): string
{
    $url = 'download_org_document.php?id=' . $id;
    if ($inline) {
        $url .= '&inline=1';
    }

    return $url;
}

/** @param list<array<string, mixed>> $files @return array<int, array<string, mixed>> */
function org_documents_index_by_id(array $files): array
{
    $indexed = [];
    foreach ($files as $file) {
        $indexed[(int)$file['id']] = $file;
    }

    return $indexed;
}

function org_document_render_file_actions(array $doc, string $base = '', string $linkClass = 'gos-edu-file-bar-link'): void
{
    $filePath = (string)($doc['file_path'] ?? '');
    $id = (int)$doc['id'];
    ?>
    <div class="doc-file-actions">
        <?php if (org_document_is_inline($filePath)): ?>
        <a href="<?= htmlspecialchars($base . org_document_download_url($id, true)) ?>"
           class="<?= htmlspecialchars($linkClass) ?>"
           target="_blank"
           rel="noopener noreferrer">Открыть в браузере</a>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($base . org_document_download_url($id, false)) ?>"
           class="<?= htmlspecialchars($linkClass) ?>">Скачать</a>
    </div>
    <?php
}

function org_document_render_file_bar(array $doc, string $base = ''): void
{
    $ext = strtolower(pathinfo((string)($doc['file_path'] ?? ''), PATHINFO_EXTENSION));
    $iconClass = match ($ext) {
        'pdf' => 'org-doc-file-icon-pdf',
        'doc', 'docx' => 'org-doc-file-icon-doc',
        'jpg', 'jpeg', 'png', 'webp' => 'org-doc-file-icon-img',
        default => 'org-doc-file-icon-file',
    };
    $iconLabel = match ($ext) {
        'pdf' => 'PDF',
        'doc', 'docx' => 'DOC',
        'jpg', 'jpeg', 'png', 'webp' => 'IMG',
        default => strtoupper($ext !== '' ? $ext : 'FILE'),
    };
    ?>
    <div class="gos-edu-file-bar">
        <span class="org-doc-file-icon <?= $iconClass ?>" aria-hidden="true"><?= htmlspecialchars($iconLabel) ?></span>
        <div class="gos-edu-file-bar-main">
            <span class="gos-edu-file-bar-title"><?= htmlspecialchars($doc['title']) ?></span>
            <span class="gos-edu-file-bar-size text-muted">
                <?= org_document_format_size(isset($doc['file_size']) ? (int)$doc['file_size'] : null) ?>
            </span>
        </div>
        <?php org_document_render_file_actions($doc, $base); ?>
    </div>
    <?php
}

/** @param list<array<string, mixed>> $files */
function org_document_render_file_list(array $files, string $base = ''): void
{
    if ($files === []) {
        return;
    }
    ?>
    <div class="gos-edu-file-list">
        <?php foreach ($files as $file): ?>
            <?php org_document_render_file_bar($file, $base); ?>
        <?php endforeach; ?>
    </div>
    <?php
}
