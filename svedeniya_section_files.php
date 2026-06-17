<?php
/**
 * Список файлов раздела сведений (как на gosweb).
 * @var string $slug
 * @var PDO $pdo
 * @var string $base
 * @var string|null $category
 */
require_once __DIR__ . '/../org_documents.php';
require_once __DIR__ . '/../svedeniya.php';

$base = $base ?? '';
$category = $category ?? null;

if (!svedeniya_section_accepts_files($slug)) {
    return;
}

$catFilter = null;
if (svedeniya_section_is_documents($slug) && $category !== null && $category !== 'all') {
    $catFilter = $category;
}
$files = org_documents_for_section($pdo, $slug, true, $catFilter);

if ($files === []) {
    return;
}

if (svedeniya_section_is_documents($slug)) {
    $grouped = $category === null || $category === 'all'
        ? org_documents_group_by_category($files)
        : [$category => $files];
    $compact = false;
    require __DIR__ . '/org_documents_list.php';
    return;
}

$grouped = ['other' => $files];
$compact = true;
require __DIR__ . '/org_documents_list.php';
