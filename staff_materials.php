<?php

require_once __DIR__ . '/org_documents.php';

function staff_materials_section_slug(): string
{
    return 'staff_materials';
}

/** @return array<string, string> */
function staff_materials_categories(): array
{
    return [
        'methodical' => 'Методические материалы',
    ];
}

function staff_materials_user_can_access(int $roleId): bool
{
    return in_array($roleId, [2, 3, 4], true);
}

/** @return list<array<string, mixed>> */
function staff_materials_documents(PDO $pdo, string $category = 'methodical'): array
{
    $slug = staff_materials_section_slug();
    $params = [$slug];
    $where = ["item_type = 'file'", 'section_slug = ?'];

    if ($category !== 'all' && isset(staff_materials_categories()[$category])) {
        $where[] = 'category = ?';
        $params[] = $category;
    }

    $sql = "
        SELECT d.*, u.full_name AS uploader_name
        FROM org_documents d
        LEFT JOIN users u ON u.id = d.uploaded_by
        WHERE " . implode(' AND ', $where) . "
        ORDER BY d.sort_order ASC, d.title ASC, d.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** @param list<array<string, mixed>> $files */
function staff_materials_render_file_list(array $files, string $base = '../'): void
{
    if ($files === []) {
        echo '<p class="text-muted mb-0">Документы пока не опубликованы.</p>';

        return;
    }
    ?>
    <div class="gos-edu-file-list staff-materials-file-list">
        <?php foreach ($files as $file): ?>
            <?php org_document_render_file_bar($file, $base); ?>
        <?php endforeach; ?>
    </div>
    <?php
}
