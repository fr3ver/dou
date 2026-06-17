<?php

require_once 'includes/config.php';

require_once 'includes/public.php';



$id = (int) ($_GET['id'] ?? 0);

$club = $id > 0 ? public_club_by_id($pdo, $id) : null;

$icon = $club ? public_club_icon((int) $club['id']) : 'bi-palette';

?>

<!DOCTYPE html>

<html lang="ru">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $club ? htmlspecialchars($club['name']) . ' — ' : 'Кружок не найден — ' ?><?= SITE_NAME ?></title>

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

            <a href="index.php#clubs" class="btn btn-outline-light btn-sm">

                <i class="bi bi-arrow-left me-1"></i>Все кружки

            </a>

        </div>

    </div>

</nav>



<main class="section-padding bg-white">

    <div class="container">

        <?php if (!$club): ?>

            <div class="text-center py-5">

                <i class="bi bi-palette display-4 text-muted mb-3 d-block"></i>

                <h1 class="h3 fw-semibold mb-3">Кружок не найден</h1>

                <a href="index.php" class="btn btn-primary-dou">На главную</a>

            </div>

        <?php else: ?>

            <article class="group-detail mx-auto">

                <a href="index.php#clubs" class="link-more d-inline-flex align-items-center mb-4">

                    <i class="bi bi-arrow-left me-1"></i>Назад к кружкам

                </a>



                <div class="group-detail-hero card border-0 shadow-sm overflow-hidden mb-4">

                    <div class="group-card-header bg-group-senior">

                        <i class="bi <?= htmlspecialchars($icon) ?>"></i>

                    </div>

                    <div class="card-body p-4 p-md-5">

                        <?php if (!empty($club['age_category'])): ?>

                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

                                <span class="news-date-pill"><?= htmlspecialchars($club['age_category']) ?></span>

                            </div>

                        <?php endif; ?>

                        <h1 class="group-detail-title mb-2"><?= htmlspecialchars($club['name']) ?></h1>

                        <?php if (!empty($club['description'])): ?>

                            <p class="text-muted mb-4 lead-sm"><?= htmlspecialchars($club['description']) ?></p>

                        <?php endif; ?>

                        <div class="row g-3">

                            <?php if (!empty($club['schedule'])): ?>

                            <div class="col-sm-4">

                                <div class="group-stat-box">

                                    <i class="bi bi-clock"></i>

                                    <div>

                                        <strong><?= htmlspecialchars($club['schedule']) ?></strong>

                                        <span>расписание</span>

                                    </div>

                                </div>

                            </div>

                            <?php endif; ?>

                            <div class="col-sm-4">

                                <div class="group-stat-box">

                                    <i class="bi bi-people"></i>

                                    <div>

                                        <strong><?= (int) $club['member_count'] ?><?= !empty($club['max_participants']) ? ' / ' . (int) $club['max_participants'] : '' ?></strong>

                                        <span>участников</span>

                                    </div>

                                </div>

                            </div>

                            <?php if (!empty($club['age_category'])): ?>

                            <div class="col-sm-4">

                                <div class="group-stat-box">

                                    <i class="bi bi-calendar3"></i>

                                    <div>

                                        <strong><?= htmlspecialchars($club['age_category']) ?></strong>

                                        <span>возраст</span>

                                    </div>

                                </div>

                            </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>



                <?php if (!empty($club['activities_features'])): ?>

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-body p-4 p-md-5">

                        <h2 class="h4 fw-semibold mb-3"><i class="bi bi-stars me-2"></i>Особенности занятий</h2>

                        <div class="text-muted group-detail-text"><?= nl2br(htmlspecialchars($club['activities_features'])) ?></div>

                    </div>

                </div>

                <?php endif; ?>



                <?php if (!empty($club['education_program'])): ?>

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-body p-4 p-md-5">

                        <h2 class="h4 fw-semibold mb-3"><i class="bi bi-journal-text me-2"></i>Образовательная программа</h2>

                        <div class="text-muted group-detail-text"><?= nl2br(htmlspecialchars($club['education_program'])) ?></div>

                    </div>

                </div>

                <?php endif; ?>



                <div class="card border-0 shadow-sm">

                    <div class="card-body p-4 p-md-5">

                        <h2 class="h4 fw-semibold mb-4">Руководитель кружка</h2>

                        <?php if (!empty($club['teacher_name'])): ?>

                            <div class="group-teacher d-flex align-items-start gap-3">

                                <div class="group-teacher-photo-wrap flex-shrink-0">

                                    <?php if (!empty($club['teacher_photo'])): ?>

                                        <img src="<?= htmlspecialchars($club['teacher_photo']) ?>"

                                             alt="<?= htmlspecialchars($club['teacher_name']) ?>"

                                             class="group-teacher-photo">

                                    <?php else: ?>

                                        <div class="group-teacher-photo group-teacher-photo-placeholder">

                                            <?= htmlspecialchars(public_teacher_initials($club['teacher_name'])) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div>

                                    <h3 class="h6 fw-semibold mb-1"><?= htmlspecialchars($club['teacher_name']) ?></h3>

                                    <p class="text-muted small mb-1"><?= htmlspecialchars($club['teacher_position'] ?? 'Руководитель') ?></p>

                                    <?php if (!empty($club['teacher_phone'])): ?>

                                        <a href="tel:<?= preg_replace('/\D+/', '', $club['teacher_phone']) ?>" class="link-more small">

                                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($club['teacher_phone']) ?>

                                        </a>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php else: ?>

                            <p class="text-muted mb-0">Руководитель будет назначен администрацией.</p>

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


