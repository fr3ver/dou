<?php

require_once __DIR__ . '/roles.php';

function child_document_types(): array
{
    return [
        'tnr'     => 'Справка о ТНР',
        'allergy' => 'Справка об аллергиях',
    ];
}

function child_document_type_label(string $type): string
{
    return child_document_types()[$type] ?? $type;
}

/** @return array<string, mixed> */
function child_document_map_row(array $row): array
{
    return [
        'id'            => (int) ($row['id'] ?? 0),
        'child_id'      => (int) ($row['child_id'] ?? 0),
        'doc_type'      => (string) ($row['doc_type'] ?? ''),
        'file_path'     => (string) ($row['file_path'] ?? ''),
        'original_name' => (string) ($row['original_name'] ?? ''),
        'uploaded_by'   => (int) ($row['user_id'] ?? 0),
        'created_at'    => (string) ($row['uploaded_at'] ?? ''),
    ];
}

function child_document_parent_owns(PDO $pdo, int $parentUserId, int $childId): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM children WHERE id = ? AND parent_id = ? LIMIT 1');
    $stmt->execute([$childId, $parentUserId]);

    return (bool) $stmt->fetchColumn();
}

function child_document_allowed_extensions(): array
{
    return ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
}

function child_document_max_bytes(): int
{
    return 10 * 1024 * 1024;
}

/** @return list<array<string, mixed>> */
function child_documents_for_child(PDO $pdo, int $childId, ?string $docType = null): array
{
    $sql = 'SELECT * FROM child_documents WHERE child_id = ?';
    $params = [$childId];

    if ($docType !== null && isset(child_document_types()[$docType])) {
        $sql .= ' AND doc_type = ?';
        $params[] = $docType;
    }

    $sql .= ' ORDER BY doc_type ASC, uploaded_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $docs = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $docs[] = child_document_map_row($row);
    }

    return $docs;
}

/**
 * @param list<int> $childIds
 * @return array<int, array<string, list<array<string, mixed>>>>
 */
function child_documents_grouped(PDO $pdo, array $childIds): array
{
    $grouped = [];
    foreach ($childIds as $childId) {
        $grouped[$childId] = ['tnr' => [], 'allergy' => []];
    }

    if ($childIds === []) {
        return $grouped;
    }

    $placeholders = implode(',', array_fill(0, count($childIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM child_documents WHERE child_id IN ($placeholders) ORDER BY uploaded_at DESC");
    $stmt->execute($childIds);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $doc = child_document_map_row($row);
        $type = (string) $doc['doc_type'];
        $cid = (int) $doc['child_id'];
        if (isset($grouped[$cid][$type])) {
            $grouped[$cid][$type][] = $doc;
        }
    }

    return $grouped;
}

function child_document_get(PDO $pdo, int $childId, string $docType): ?array
{
    if (!isset(child_document_types()[$docType])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM child_documents WHERE child_id = ? AND doc_type = ? LIMIT 1');
    $stmt->execute([$childId, $docType]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? child_document_map_row($row) : null;
}

function child_document_user_can_view(PDO $pdo, array $doc, int $userId, int $roleId): bool
{
    if (child_document_parent_owns($pdo, $userId, (int) $doc['child_id'])) {
        return true;
    }

    if (role_is_admin_panel($roleId)) {
        return true;
    }

    if ($roleId !== 2) {
        return false;
    }

    $stmt = $pdo->prepare("
        SELECT 1 FROM children c
        JOIN employees e ON e.group_id = c.group_id
        WHERE c.id = ? AND e.user_id = ?
          AND e.position IN ('Воспитатель', 'Логопед')
        LIMIT 1
    ");
    $stmt->execute([(int) $doc['child_id'], $userId]);

    return (bool) $stmt->fetchColumn();
}

function child_document_upload(PDO $pdo, int $childId, int $parentUserId, string $docType, array $file): int
{
    if (!isset(child_document_types()[$docType])) {
        throw new InvalidArgumentException('Некорректный тип документа');
    }

    if (!child_document_parent_owns($pdo, $parentUserId, $childId)) {
        throw new RuntimeException('Нет доступа к этому ребёнку');
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Выберите файл для загрузки');
    }

    if (($file['size'] ?? 0) > child_document_max_bytes()) {
        throw new InvalidArgumentException('Файл слишком большой (максимум 10 МБ)');
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, child_document_allowed_extensions(), true)) {
        throw new InvalidArgumentException('Допустимы файлы: PDF, JPG, PNG, WEBP');
    }

    $existing = child_document_get($pdo, $childId, $docType);
    if ($existing) {
        child_document_remove_file($existing['file_path'] ?? '');
    }

    $uploadDir = __DIR__ . '/../uploads/child_docs/' . $childId . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Не удалось сохранить файл');
    }

    $filePath = 'uploads/child_docs/' . $childId . '/' . $storedName;
    $originalName = basename((string) ($file['name'] ?? $storedName));

    $stmt = $pdo->prepare('
        INSERT INTO child_documents (child_id, doc_type, file_path, original_name, user_id, uploaded_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            file_path = VALUES(file_path),
            original_name = VALUES(original_name),
            user_id = VALUES(user_id),
            uploaded_at = NOW()
    ');
    $stmt->execute([$childId, $docType, $filePath, $originalName, $parentUserId]);

    return $childId;
}

function child_document_delete(PDO $pdo, int $childId, string $docType, int $parentUserId): void
{
    if (!isset(child_document_types()[$docType])) {
        throw new InvalidArgumentException('Некорректный тип документа');
    }

    if (!child_document_parent_owns($pdo, $parentUserId, $childId)) {
        throw new RuntimeException('Нет доступа');
    }

    $doc = child_document_get($pdo, $childId, $docType);
    if (!$doc) {
        throw new RuntimeException('Документ не найден');
    }

    child_document_remove_file($doc['file_path'] ?? '');
    $pdo->prepare('DELETE FROM child_documents WHERE child_id = ? AND doc_type = ?')
        ->execute([$childId, $docType]);
}

function child_document_remove_file(?string $filePath): void
{
    if (!$filePath || !str_starts_with($filePath, 'uploads/child_docs/')) {
        return;
    }

    $full = __DIR__ . '/../' . $filePath;
    if (is_file($full)) {
        unlink($full);
    }
}

function child_document_mime_type(string $filePath): string
{
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    return match ($ext) {
        'pdf'         => 'application/pdf',
        'jpg', 'jpeg' => 'image/jpeg',
        'png'         => 'image/png',
        'webp'        => 'image/webp',
        default       => 'application/octet-stream',
    };
}

function child_document_format_size(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' МБ';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024) . ' КБ';
    }

    return $bytes . ' Б';
}

function child_document_file_size(?string $filePath): ?int
{
    if (!$filePath || !str_starts_with($filePath, 'uploads/child_docs/')) {
        return null;
    }

    $full = __DIR__ . '/../' . $filePath;

    return is_file($full) ? (int) filesize($full) : null;
}

function child_document_is_image(string $filePath): bool
{
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
}

function child_document_is_inline(string $filePath): bool
{
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    return in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true);
}

function child_document_download_url(int $childId, string $docType, bool $inline = false): string
{
    $url = 'download_child_document.php?child_id=' . $childId . '&type=' . rawurlencode($docType);
    if ($inline) {
        $url .= '&inline=1';
    }

    return $url;
}

function child_document_render_file_actions(array $doc, string $base = '', string $linkClass = 'child-doc-action-link'): void
{
    $filePath = (string) ($doc['file_path'] ?? '');
    $childId = (int) $doc['child_id'];
    $docType = (string) $doc['doc_type'];
    ?>
    <div class="doc-file-actions">
        <?php if (child_document_is_inline($filePath)): ?>
        <a href="<?= htmlspecialchars($base . child_document_download_url($childId, $docType, true)) ?>"
           class="<?= htmlspecialchars($linkClass) ?>"
           target="_blank"
           rel="noopener noreferrer">Открыть в браузере</a>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($base . child_document_download_url($childId, $docType, false)) ?>"
           class="<?= htmlspecialchars($linkClass) ?>">Скачать</a>
    </div>
    <?php
}

/** @return list<array<string, mixed>> */
function child_documents_admin_list(PDO $pdo, ?string $docType = null, int $childId = 0): array
{
    $sql = "
        SELECT cd.*,
               c.full_name AS child_name, c.has_tnr,
               g.name AS group_name, g.age_category,
               p.full_name AS parent_name,
               u.full_name AS uploader_name
        FROM child_documents cd
        JOIN children c ON c.id = cd.child_id
        JOIN `groups` g ON g.id = c.group_id
        JOIN users p ON p.id = c.parent_id
        LEFT JOIN users u ON u.id = cd.user_id
        WHERE 1=1
    ";
    $params = [];

    if ($docType !== null && isset(child_document_types()[$docType])) {
        $sql .= ' AND cd.doc_type = ?';
        $params[] = $docType;
    }
    if ($childId > 0) {
        $sql .= ' AND cd.child_id = ?';
        $params[] = $childId;
    }

    $sql .= ' ORDER BY cd.uploaded_at DESC, cd.child_id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $rows = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $doc = child_document_map_row($row);
        $doc['child_name'] = $row['child_name'] ?? '';
        $doc['has_tnr'] = $row['has_tnr'] ?? 0;
        $doc['group_name'] = $row['group_name'] ?? '';
        $doc['age_category'] = $row['age_category'] ?? '';
        $doc['parent_name'] = $row['parent_name'] ?? '';
        $doc['uploader_name'] = $row['uploader_name'] ?? '';
        $rows[] = $doc;
    }

    return $rows;
}

function child_documents_admin_stats(PDO $pdo): array
{
    $tnr = (int) $pdo->query("SELECT COUNT(*) FROM child_documents WHERE doc_type = 'tnr'")->fetchColumn();
    $allergy = (int) $pdo->query("SELECT COUNT(*) FROM child_documents WHERE doc_type = 'allergy'")->fetchColumn();
    $children = (int) $pdo->query('SELECT COUNT(DISTINCT child_id) FROM child_documents')->fetchColumn();

    return [
        'total'    => $tnr + $allergy,
        'tnr'      => $tnr,
        'allergy'  => $allergy,
        'children' => $children,
    ];
}
