<?php
require_once 'includes/config.php';
require_once 'includes/public.php';

$id = (int)($_GET['id'] ?? 0);
$news = $id > 0 ? public_news_by_id($pdo, $id) : null;

if (!$news) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $news ? htmlspecialchars($news['title']) . ' — ' : 'Новость не найдена — ' ?><?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=6" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dou sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <span class="brand-icon"><i class="bi bi-balloon-heart-fill"></i></span>
            <span><?= SITE_NAME ?></span>
        </a>
        <div class="ms-auto">
            <a href="index.php#news" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Все новости
            </a>
        </div>
    </div>
</nav>

<main class="section-padding bg-white">
    <div class="container">
        <?php if (!$news): ?>
            <div class="text-center py-5">
                <i class="bi bi-newspaper display-4 text-muted mb-3 d-block"></i>
                <h1 class="h3 fw-semibold mb-3">Новость не найдена</h1>
                <p class="text-muted mb-4">Возможно, она ещё не опубликована или была удалена.</p>
                <a href="index.php" class="btn btn-primary-dou">На главную</a>
            </div>
        <?php else: ?>
            <article class="news-article mx-auto">
                <a href="index.php#news" class="link-more d-inline-flex align-items-center mb-3">
                    <i class="bi bi-arrow-left me-1"></i>Назад к новостям
                </a>
                <div class="news-date-pill mb-3">
                    <?= date('d.m.Y', strtotime($news['publish_date'])) ?>
                </div>
                <h1 class="news-article-title mb-4"><?= htmlspecialchars($news['title']) ?></h1>
                <?php if (!empty($news['image_url'])): ?>
                    <img src="<?= htmlspecialchars($news['image_url']) ?>"
                         class="news-article-image rounded-4 shadow-sm mb-4"
                         alt="<?= htmlspecialchars($news['title']) ?>">
                <?php endif; ?>
                <div class="news-article-body">
                    <?= nl2br(htmlspecialchars($news['content'])) ?>
                </div>
                <?php if (!empty($news['author_name'])): ?>
                    <p class="text-muted small mt-4 mb-0">
                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($news['author_name']) ?>
                    </p>
                <?php endif; ?>
            </article>
        <?php endif; ?>
    </div>
</main>

<footer class="footer-dou">
    <div class="container py-4">
        <p class="text-center footer-copy small mb-0">
            &copy; 2026 <?= SITE_NAME ?>
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
