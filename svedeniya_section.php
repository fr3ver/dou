<?php

require_once __DIR__ . '/includes/config.php';

require_once __DIR__ . '/includes/svedeniya.php';



$slug = trim((string)($_GET['slug'] ?? ''));

$category = $_GET['category'] ?? 'all';

$meta = svedeniya_section_meta($slug);



if (!$meta) {

    http_response_code(404);

    $pageTitle = 'Раздел не найден';

    $breadcrumbs = [

        ['label' => 'Главная', 'href' => 'index.php'],

        ['label' => svedeniya_hub_title(), 'href' => 'svedeniya.php'],

        ['label' => $pageTitle],

    ];

    require __DIR__ . '/includes/partials/svedeniya_public_shell_start.php';

    echo '<div class="gos-sved-panel p-5 text-center text-muted">Раздел не найден.</div>';

    require __DIR__ . '/includes/partials/svedeniya_public_shell_end.php';

    exit;

}

if (($meta['type'] ?? '') === 'link' && !empty($meta['href'])) {
    header('Location: ' . $meta['href'], true, 302);
    exit;
}

$section = svedeniya_get_section($pdo, $slug);

if (!$section) {

    http_response_code(404);

    exit('Раздел не найден');

}



$pageTitle = $section['title'];

$breadcrumbs = [

    ['label' => 'Главная', 'href' => 'index.php'],

    ['label' => svedeniya_hub_title(), 'href' => 'svedeniya.php'],

    ['label' => $pageTitle],

];



$categories = org_document_categories();

if ($category !== 'all' && !isset($categories[$category])) {

    $category = 'all';

}



$hasAnyFiles = org_documents_for_section($pdo, $slug, true) !== [];

$displayFiles = org_documents_for_section(

    $pdo,

    $slug,

    true,

    svedeniya_section_is_documents($slug) && $category !== 'all' ? $category : null

);



require __DIR__ . '/includes/partials/svedeniya_public_shell_start.php';

if ($slug === 'education') {
    require __DIR__ . '/includes/partials/svedeniya_education_page.php';
} elseif ($slug === 'ktp') {
    require __DIR__ . '/includes/partials/svedeniya_ktp_page.php';
} elseif (svedeniya_section_uses_grid_layout($slug)) {
    require __DIR__ . '/includes/partials/svedeniya_section_page.php';
} else {
    require __DIR__ . '/includes/partials/svedeniya_accordion_page.php';
}

require __DIR__ . '/includes/partials/svedeniya_public_shell_end.php';


