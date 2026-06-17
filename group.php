<?php
require_once 'includes/config.php';
require_once 'includes/public.php';

$id = (int)($_GET['id'] ?? 0);
$group = $id > 0 ? public_group_by_id($pdo, $id) : null;

if (!$group) {
    http_response_code(404);
}

$headerClass = $group ? public_group_header_class($group) : '';
$icon = $group ? public_group_icon($group) : 'bi-people';
$groupStaff = $group ? public_group_staff($pdo, (int)$group['id']) : [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $group ? htmlspecialchars($group['name']) . ' — ' : 'Группа не найдена — ' ?><?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=8" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dou sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <span class="brand-icon"><i class="bi bi-balloon-heart-fill"></i></span>
            <span><?= SITE_NAME ?></span>
        </a>
        <div class="ms-auto">
            <a href="index.php#groups" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Все группы
            </a>
        </div>
    </div>
</nav>

<main class="section-padding bg-white">
    <div class="container">
        <?php if (!$group): ?>
            <div class="text-center py-5">
                <i class="bi bi-people display-4 text-muted mb-3 d-block"></i>
                <h1 class="h3 fw-semibold mb-3">Группа не найдена</h1>
                <a href="index.php" class="btn btn-primary-dou">На главную</a>
            </div>
        <?php else: ?>
            <article class="group-detail mx-auto">
                <a href="index.php#groups" class="link-more d-inline-flex align-items-center mb-4">
                    <i class="bi bi-arrow-left me-1"></i>Назад к группам
                </a>

                <div class="group-detail-hero card border-0 shadow-sm overflow-hidden mb-4">
                    <div class="group-card-header <?= $headerClass ?>">
                        <i class="bi <?= $icon ?>"></i>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <span class="news-date-pill"><?= htmlspecialchars(public_age_display($group['age_category'] ?? null)) ?></span>
                        </div>
                        <h1 class="group-detail-title mb-2"><?= htmlspecialchars($group['name']) ?></h1>
                        <?php if (!empty($group['description'])): ?>
                            <p class="text-muted mb-4 lead-sm"><?= htmlspecialchars($group['description']) ?></p>
                        <?php endif; ?>
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="group-stat-box">
                                    <i class="bi bi-people"></i>
                                    <div>
                                        <strong><?= (int)$group['children_count'] ?></strong>
                                        <span>детей в группе</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="group-stat-box">
                                    <i class="bi bi-door-open"></i>
                                    <div>
                                        <strong><?= (int)$group['capacity'] ?></strong>
                                        <span>мест</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="group-stat-box">
                                    <i class="bi bi-calendar3"></i>
                                    <div>
                                        <strong><?= htmlspecialchars(public_age_display($group['age_category'] ?? null)) ?></strong>
                                        <span>возраст</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($group['activities_features'])): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h4 fw-semibold mb-3"><i class="bi bi-stars me-2"></i>Особенности занятий</h2>
                        <div class="text-muted group-detail-text"><?= nl2br(htmlspecialchars($group['activities_features'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($group['education_program'])): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h4 fw-semibold mb-3"><i class="bi bi-journal-text me-2"></i>Образовательная программа</h2>
                        <div class="text-muted group-detail-text"><?= nl2br(htmlspecialchars($group['education_program'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h4 fw-semibold mb-4"><?= count($groupStaff) > 1 ? 'Педагоги группы' : 'Воспитатель группы' ?></h2>
                        <?php if ($groupStaff !== []): ?>
                            <div class="row g-4">
                                <?php foreach ($groupStaff as $staff): ?>
                                <div class="col-md-6">
                                    <div class="group-teacher d-flex align-items-start gap-3 h-100">
                                        <div class="group-teacher-photo-wrap flex-shrink-0">
                                            <?php if (!empty($staff['photo_url'])): ?>
                                                <img src="<?= htmlspecialchars($staff['photo_url']) ?>"
                                                     alt="<?= htmlspecialchars($staff['full_name']) ?>"
                                                     class="group-teacher-photo">
                                            <?php else: ?>
                                                <div class="group-teacher-photo group-teacher-photo-placeholder">
                                                    <?= htmlspecialchars(public_teacher_initials($staff['full_name'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h3 class="h6 fw-semibold mb-1"><?= htmlspecialchars($staff['full_name']) ?></h3>
                                            <p class="text-muted small mb-1"><?= htmlspecialchars($staff['position']) ?></p>
                                            <?php if (!empty($staff['phone'])): ?>
                                                <a href="tel:<?= preg_replace('/\D+/', '', $staff['phone']) ?>" class="link-more small">
                                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($staff['phone']) ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">Педагоги будут назначены администрацией.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endif; ?>
    </div>
</main>

<footer class="footer-dou">
    <div class="container py-4">
        <p class="text-center footer-copy small mb-0">&copy; 2026 <?= SITE_NAME ?></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
