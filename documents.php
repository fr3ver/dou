<?php
require_once __DIR__ . '/includes/config.php';

$category = $_GET['category'] ?? 'all';
$url = 'svedeniya_section.php?slug=documents';
if ($category !== '' && $category !== 'all') {
    $url .= '&category=' . urlencode($category);
}

header('Location: ' . $url, true, 301);
exit;
