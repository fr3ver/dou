<?php
require_once 'includes/config.php';
require_once 'includes/public.php';

$id = (int)($_GET['id'] ?? 0);
$event = $id > 0 ? public_event_by_id($pdo, $id) : null;

if (!$event) {
    http_response_code(404);
}

$displayDate = ($event && $event['status'] === 'postponed' && !empty($event['postponed_to']))
    ? $event['postponed_to']
    : ($event['event_date'] ?? '');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $event ? htmlspecialchars($event['title']) . ' — ' : 'Мероприятие не найдено — ' ?><?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=7" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dou sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <span class="brand-icon"><i class="bi bi-balloon-heart-fill"></i></span>
            <span><?= SITE_NAME ?></span>
        </a>
        <div class="ms-auto">
            <a href="index.php#events" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Все мероприятия
            </a>
        </div>
    </div>
</nav>

<main class="section-padding bg-white">
    <div class="container">
        <?php if (!$event): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-event display-4 text-muted mb-3 d-block"></i>
                <h1 class="h3 fw-semibold mb-3">Мероприятие не найдено</h1>
                <p class="text-muted mb-4">Возможно, оно завершено или было удалено.</p>
                <a href="index.php" class="btn btn-primary-dou">На главную</a>
            </div>
        <?php else: ?>
            <article class="news-article mx-auto">
                <a href="index.php#events" class="link-more d-inline-flex align-items-center mb-3">
                    <i class="bi bi-arrow-left me-1"></i>Назад к мероприятиям
                </a>
                <?php if ($event['status'] === 'postponed'): ?>
                    <span class="badge bg-soft-blue text-dark mb-2">Перенесено</span>
                <?php endif; ?>
                <div class="news-date-pill mb-3">
                    <?= date('d.m.Y', strtotime($displayDate)) ?>
                    <?php if ($event['event_time']): ?>
                        · <?= substr($event['event_time'], 0, 5) ?>
                    <?php endif; ?>
                </div>
                <h1 class="news-article-title mb-4"><?= htmlspecialchars($event['title']) ?></h1>
                <?php if (!empty($event['description'])): ?>
                    <div class="news-article-body mb-4">
                        <?= nl2br(htmlspecialchars($event['description'])) ?>
                    </div>
                <?php endif; ?>
                <ul class="list-unstyled event-meta">
                    <?php if (!empty($event['location'])): ?>
                    <li class="mb-2"><i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars($event['location']) ?></li>
                    <?php endif; ?>
                    <li class="mb-2">
                        <i class="bi bi-people me-2"></i>
                        <?= htmlspecialchars(entity_groups_format_event_groups($event, 'Для всех')) ?>
                    </li>
                    <?php if ($event['status'] === 'postponed' && !empty($event['postponed_to']) && $event['postponed_to'] !== $event['event_date']): ?>
                    <li class="text-muted small">
                        <i class="bi bi-calendar-x me-2"></i>Изначально: <?= date('d.m.Y', strtotime($event['event_date'])) ?>
                    </li>
                    <?php endif; ?>
                </ul>
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
