<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/public.php';

$isLoggedIn = isset($_SESSION['user_id']);
$role_id = $isLoggedIn ? $_SESSION['role_id'] : 0;

$news_list = public_news_list($pdo, 3);
$events_list = public_upcoming_events($pdo, 6);
$clubs_list = public_clubs($pdo);
$groups_list = public_groups($pdo);
$staff_list = public_staff_list($pdo);
$reviews_list = public_approved_reviews($pdo, 6);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> — Главная</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="assets/css/style.css?v=25" rel="stylesheet">
</head>
<body>

<!-- Шапка -->
<nav class="navbar navbar-expand-lg navbar-dou sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <span class="brand-icon"><i class="bi bi-balloon-heart-fill"></i></span>
            <span><?= SITE_NAME ?></span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link active" href="#">Главная</a></li>
                <li class="nav-item"><a class="nav-link" href="#news">Новости</a></li>
                <li class="nav-item"><a class="nav-link" href="#events">Мероприятия</a></li>
                <li class="nav-item"><a class="nav-link" href="#menu">Меню</a></li>
                <?php if (count($groups_list) > 0 || count($clubs_list) > 0): ?>
                <li class="nav-item"><a class="nav-link" href="#groups">Группы</a></li>
                <?php endif; ?>
                <?php if (count($staff_list) > 0): ?>
                <li class="nav-item"><a class="nav-link" href="#team">Коллектив</a></li>
                <?php endif; ?>
                <?php if (count($reviews_list) > 0): ?>
                <li class="nav-item"><a class="nav-link" href="#reviews">Отзывы</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="svedeniya.php">Сведения</a></li>
                <li class="nav-item"><a class="nav-link" href="#contacts">Контакты</a></li>
                
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-outline-light" href="<?php 
                            if ($role_id == 3 || $role_id == 4) echo 'admin/dashboard.php';
                            elseif ($role_id == 2) echo 'employee/dashboard.php';
                            else echo 'parent/dashboard.php';
                        ?>">Мой кабинет</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light" href="api/logout.php">Выйти</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-accent px-4" href="login.php">Войти</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

    <!-- Героический блок -->
    <section class="hero">
        <div class="hero-shapes" aria-hidden="true">
            <span class="shape shape-1"></span>
            <span class="shape shape-2"></span>
            <span class="shape shape-3"></span>
        </div>
        <div class="container position-relative">
            <div class="row align-items-center min-vh-75 py-5">
                <div class="col-lg-7">
                    <span class="hero-badge mb-3 d-inline-block">Дошкольное образование</span>
                    <h1 class="hero-title display-4 fw-bold mb-4">
                        Добро пожаловать в детский сад «Радуга»!
                    </h1>
                    <p class="hero-lead lead mb-4">
                        Мы создаём тёплую, безопасную и развивающую среду, где каждый ребёнок
                        растёт счастливым, любознательным и уверенным в себе.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= htmlspecialchars(ENROLLMENT_URL) ?>" class="btn btn-accent btn-lg px-4" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-pencil-square me-2"></i>Записать ребёнка в сад
                        </a>
                        <a href="login.php" class="btn btn-outline-hero btn-lg px-4">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Войти в личный кабинет
                        </a>
                        <a href="#about" class="btn btn-outline-hero btn-lg px-4">Узнать больше</a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-center">
                    <div class="hero-illustration">
                        <i class="bi bi-emoji-smile"></i>
                        <i class="bi bi-book"></i>
                        <i class="bi bi-palette"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- О нашем саде -->
    <section id="about" class="section-padding bg-section-soft">
        <div class="container">
            <div class="text-center mb-5 section-header">
                <h2 class="section-title">О нашем саде</h2>
                <p class="section-subtitle mx-auto">
                    Мы заботимся о развитии, здоровье и радости каждого малыша каждый день.
                </p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card card-hover h-100 border-0 shadow-sm text-center p-4">
                        <div class="feature-icon feature-icon-blue mx-auto mb-3">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h5 class="fw-semibold">Безопасность</h5>
                        <p class="text-muted mb-0 small">Охраняемая территория, видеонаблюдение и внимательный персонал.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-hover h-100 border-0 shadow-sm text-center p-4">
                        <div class="feature-icon feature-icon-green mx-auto mb-3">
                            <i class="bi bi-lightbulb"></i>
                        </div>
                        <h5 class="fw-semibold">Развитие</h5>
                        <p class="text-muted mb-0 small">Современные программы, творческие занятия и подготовка к школе.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-hover h-100 border-0 shadow-sm text-center p-4">
                        <div class="feature-icon feature-icon-yellow mx-auto mb-3">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                        <h5 class="fw-semibold">Здоровье</h5>
                        <p class="text-muted mb-0 small">Сбалансированное питание, прогулки на свежем воздухе и медицинский контроль.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-hover h-100 border-0 shadow-sm text-center p-4">
                        <div class="feature-icon feature-icon-orange mx-auto mb-3">
                            <i class="bi bi-people"></i>
                        </div>
                        <h5 class="fw-semibold">Забота</h5>
                        <p class="text-muted mb-0 small">Опытные воспитатели и тёплая атмосфера для детей и родителей.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Новости -->
    <section id="news" class="section-padding bg-section-sky">
        <div class="container">
            <div class="text-center mb-5 section-header">
                <h2 class="section-title">Новости</h2>
                <p class="section-subtitle mx-auto">Последние события и объявления нашего сада</p>
            </div>

            <div class="row g-4">
                <?php if (count($news_list) > 0): ?>
                    <?php foreach ($news_list as $item):
                        $short_text = public_excerpt($item['content']);
                        $has_more = mb_strlen(strip_tags($item['content'])) > 120;
                    ?>
                    <div class="col-md-4">
                        <a href="news.php?id=<?= (int)$item['id'] ?>" class="clickable-card h-100">
                        <article class="card card-hover h-100 border-0 shadow-sm overflow-hidden news-card">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                     class="card-img-top news-card-img"
                                     alt="<?= htmlspecialchars($item['title']) ?>">
                            <?php else: ?>
                                <div class="news-card-placeholder">
                                    <i class="bi bi-newspaper"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body p-4 d-flex flex-column">
                                <div class="news-date-pill mb-2">
                                    <?= date('d.m.Y', strtotime($item['publish_date'])) ?>
                                </div>
                                <h5 class="card-title fw-semibold"><?= htmlspecialchars($item['title']) ?></h5>
                                <?php if ($short_text !== ''): ?>
                                <p class="card-text text-muted small flex-grow-1">
                                    <?= htmlspecialchars($short_text) ?>
                                </p>
                                <?php endif; ?>
                                <span class="link-more mt-auto d-inline-block">
                                    <?= $has_more ? 'Подробнее' : 'Читать' ?> <i class="bi bi-arrow-right"></i>
                                </span>
                            </div>
                        </article>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-newspaper display-4 text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">Пока нет новостей. Скоро здесь появятся объявления нашего сада.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Мероприятия -->
    <section id="events" class="section-padding bg-section-white">
        <div class="container">
            <div class="text-center mb-5 section-header">
                <h2 class="section-title">Ближайшие мероприятия</h2>
                <p class="section-subtitle mx-auto">Праздники, концерты и события для детей и родителей</p>
            </div>

            <div class="row g-4">
                <?php if (count($events_list) > 0): ?>
                    <?php foreach ($events_list as $event):
                        $dm = public_event_day_month($event);
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <a href="event.php?id=<?= (int)$event['id'] ?>" class="clickable-card h-100">
                        <article class="card card-hover h-100 border-0 shadow-sm event-card">
                            <div class="card-body p-4 d-flex gap-3">
                                <div class="event-date-badge text-center flex-shrink-0">
                                    <span class="event-date-day"><?= $dm['day'] ?></span>
                                    <span class="event-date-month"><?= $dm['month'] ?></span>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <?php if ($event['status'] === 'postponed'): ?>
                                        <span class="badge bg-soft-blue text-dark mb-2">Перенесено</span>
                                    <?php endif; ?>
                                    <h5 class="fw-semibold mb-2"><?= htmlspecialchars($event['title']) ?></h5>
                                    <?php if (!empty($event['description'])): ?>
                                        <p class="text-muted small mb-2"><?= htmlspecialchars(public_excerpt($event['description'], 100)) ?></p>
                                    <?php endif; ?>
                                    <ul class="list-unstyled small text-muted mb-2 event-meta">
                                        <?php if ($event['event_time']): ?>
                                        <li><i class="bi bi-clock me-1"></i><?= substr($event['event_time'], 0, 5) ?></li>
                                        <?php endif; ?>
                                        <?php if (!empty($event['location'])): ?>
                                        <li><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($event['location']) ?></li>
                                        <?php endif; ?>
                                        <li>
                                            <i class="bi bi-people me-1"></i>
                                            <?= htmlspecialchars(entity_groups_format_event_groups($event, 'Для всех')) ?>
                                        </li>
                                    </ul>
                                    <span class="link-more small">Подробнее <i class="bi bi-arrow-right"></i></span>
                                </div>
                            </div>
                        </article>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-calendar-event display-4 text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">Ближайших мероприятий пока нет. Следите за обновлениями!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Меню -->
    <?php $menuLkHref = public_menu_lk_href($isLoggedIn, (int)$role_id); ?>
    <section id="menu" class="section-padding bg-section-mint">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="section-header section-header--start mb-4">
                        <h2 class="section-title">Наше меню</h2>
                    </div>
                    <p class="text-muted mb-4">
                        Четырёхразовое питание на собственной кухне: завтрак, второй завтрак, обед и полдник.
                        Меню согласовано по возрасту группы, в блюдах указываются аллергены.
                    </p>
                    <ul class="list-unstyled menu-list">
                        <li><i class="bi bi-shield-check text-success me-2"></i>Учёт аллергий каждого ребёнка в группе</li>
                        <li><i class="bi bi-shield-check text-success me-2"></i>Контроль аллерголога и маркировка блюд</li>
                        <li><i class="bi bi-shield-check text-success me-2"></i>Свежие продукты и приготовление по СанПиН</li>
                    </ul>
                    <a href="<?= htmlspecialchars($menuLkHref) ?>" class="btn btn-primary-dou mt-2">
                        <i class="bi bi-calendar-week me-1"></i>
                        <?= $isLoggedIn ? 'Открыть меню в кабинете' : 'Меню в личном кабинете' ?>
                    </a>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="menu-card card-hover p-4 h-100 bg-soft-blue">
                                <i class="bi bi-sunrise menu-card-icon d-block text-center"></i>
                                <h6 class="fw-semibold mt-2 mb-2 text-center">Завтрак</h6>
                                <p class="small text-muted mb-0">Тёплая каша и бутерброд — мягкий старт дня, чтобы ребёнок был бодрым к занятиям.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="menu-card card-hover p-4 h-100 bg-soft-green">
                                <i class="bi bi-egg-fried menu-card-icon d-block text-center"></i>
                                <h6 class="fw-semibold mt-2 mb-2 text-center">Второй завтрак</h6>
                                <p class="small text-muted mb-0">Фрукты или запеканка — лёгкий перекус между занятиями без тяжести в желудке.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="menu-card card-hover p-4 h-100 bg-soft-yellow">
                                <i class="bi bi-cup-hot menu-card-icon d-block text-center"></i>
                                <h6 class="fw-semibold mt-2 mb-2 text-center">Обед</h6>
                                <p class="small text-muted mb-0">Суп, второе и напиток — сытный обед по возрасту, сил хватит на прогулку и игры.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="menu-card card-hover p-4 h-100" style="background: rgba(255, 214, 186, 0.45);">
                                <i class="bi bi-apple menu-card-icon d-block text-center"></i>
                                <h6 class="fw-semibold mt-2 mb-2 text-center">Полдник</h6>
                                <p class="small text-muted mb-0">Фрукты, кефир или творожное блюдо — чтобы ребёнок чувствовал себя хорошо до вечера.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/includes/partials/groups_section.php'; ?>

    <?php if (count($staff_list) > 0): ?>
    <!-- Коллектив -->
    <section id="team" class="section-padding bg-section-white">
        <div class="container">
            <div class="text-center mb-5 section-header">
                <h2 class="section-title">Наш коллектив</h2>
            </div>
            <div class="row g-4">
                <?php foreach ($staff_list as $staff):
                    $exp = public_staff_experience($staff);
                    $collapseId = 'staff-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$staff['id']);
                ?>
                <div class="col-md-6 col-lg-4">
                    <article class="card staff-card h-100 border-0 shadow-sm<?= !empty($staff['is_director']) ? ' staff-card-director' : '' ?>">
                        <div class="staff-card-photo-wrap">
                            <?php if (!empty($staff['photo_url'])): ?>
                                <img src="<?= htmlspecialchars($staff['photo_url']) ?>" alt=""
                                     class="staff-card-photo">
                            <?php else: ?>
                                <span class="staff-card-photo staff-card-photo-placeholder">
                                    <?= htmlspecialchars(public_teacher_initials($staff['full_name'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <h5 class="fw-semibold mb-1"><?= htmlspecialchars($staff['full_name']) ?></h5>
                            <p class="text-primary small fw-semibold mb-1"><?= htmlspecialchars($staff['position']) ?></p>
                            <?php if (!empty($staff['group_name'])): ?>
                                <p class="text-muted small mb-3"><?= htmlspecialchars($staff['group_name']) ?></p>
                            <?php else: ?>
                                <div class="mb-3"></div>
                            <?php endif; ?>

                            <?php if (!empty($staff['education'])): ?>
                                <p class="staff-card-line small mb-2">
                                    <span class="staff-card-label">Образование:</span>
                                    <?= htmlspecialchars(public_excerpt($staff['education'], 120)) ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($exp !== ''): ?>
                                <p class="staff-card-line small mb-3">
                                    <span class="staff-card-label">Стаж:</span> <?= htmlspecialchars($exp) ?>
                                </p>
                            <?php endif; ?>

                            <button class="btn btn-sm btn-outline-primary mt-auto align-self-start"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#<?= $collapseId ?>" aria-expanded="false">
                                Подробнее
                            </button>

                            <div class="collapse mt-3" id="<?= $collapseId ?>">
                                <div class="staff-card-details small text-muted">
                                    <?php if (!empty($staff['education'])): ?>
                                        <p class="mb-2"><strong class="text-dark">Образование</strong><br><?= nl2br(htmlspecialchars($staff['education'])) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($staff['retraining'])): ?>
                                        <p class="mb-2"><strong class="text-dark">Переподготовка</strong><br><?= nl2br(htmlspecialchars($staff['retraining'])) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($staff['qualification_upgrades'])): ?>
                                        <p class="mb-2"><strong class="text-dark">Повышение квалификации</strong><br><?= nl2br(htmlspecialchars($staff['qualification_upgrades'])) ?></p>
                                    <?php else: ?>
                                        <p class="mb-2"><strong class="text-dark">Повышение квалификации</strong><br>Нет</p>
                                    <?php endif; ?>
                                    <?php if ($exp !== ''): ?>
                                        <p class="mb-0"><strong class="text-dark">Опыт работы</strong><br><?= htmlspecialchars($exp) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (count($reviews_list) > 0): ?>
    <!-- Отзывы -->
    <section id="reviews" class="section-padding bg-section-lilac">
        <div class="container">
            <div class="text-center mb-5 section-header">
                <h2 class="section-title">Отзывы</h2>
                <p class="section-subtitle mx-auto">Что говорят семьи наших воспитанников</p>
            </div>
            <div class="row g-4">
                <?php foreach ($reviews_list as $review): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="card review-card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="review-card-stars mb-3" aria-label="Оценка: <?= (int)$review['rating'] ?> из 5">
                                <?= public_review_stars((int)$review['rating']) ?>
                            </div>
                            <blockquote class="review-card-text flex-grow-1 mb-3">
                                «<?= nl2br(htmlspecialchars($review['text'])) ?>»
                            </blockquote>
                            <footer class="review-card-footer mt-auto">
                                <div class="review-card-author"><?= htmlspecialchars($review['parent_name']) ?></div>
                                <div class="review-card-date text-muted small">
                                    <?= date('d.m.Y', strtotime($review['created_at'])) ?>
                                </div>
                            </footer>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$isLoggedIn || $role_id !== 1): ?>
            <p class="text-center text-muted small mt-4 mb-0">
                Хотите поделиться впечатлениями? Войдите в
                <a href="login.php">личный кабинет родителя</a> и оставьте отзыв.
            </p>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php require __DIR__ . '/includes/partials/footer_map.php'; ?>

    <!-- Футер -->
    <footer class="footer-dou">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="footer-brand mb-3">
                        <a href="index.php" class="footer-brand-link">
                            <i class="bi bi-balloon-heart-fill me-2"></i><?= SITE_NAME ?>
                        </a>
                    </h5>
                    <p class="footer-text small mb-0">
                        Муниципальное дошкольное образовательное учреждение «Радуга».
                        Забота, развитие и радость для ваших детей.
                    </p>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <h6 class="footer-heading">Контактная информация</h6>
                    <ul class="list-unstyled footer-text small mb-0">
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i><a href="tel:<?= SITE_PHONE_TEL ?>"><?= SITE_PHONE ?></a></li>
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i><a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a></li>
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i><?= SITE_ADDRESS ?></li>
                        <li><i class="bi bi-clock me-2"></i>Пн–Пт: 7:00 – 19:00</li>
                    </ul>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <h6 class="footer-heading">Быстрые ссылки</h6>
                    <ul class="list-unstyled footer-text small mb-0">
                        <li class="mb-2"><a href="#news">Новости</a></li>
                        <li class="mb-2"><a href="#events">Мероприятия</a></li>
                        <li class="mb-2"><a href="#menu">Меню</a></li>
                        <?php if (count($groups_list) > 0 || count($clubs_list) > 0): ?>
                        <li class="mb-2"><a href="#groups">Группы</a></li>
                        <?php endif; ?>
                        <?php if (count($staff_list) > 0): ?>
                        <li class="mb-2"><a href="#team">Коллектив</a></li>
                        <?php endif; ?>
                        <?php if (count($reviews_list) > 0): ?>
                        <li class="mb-2"><a href="#reviews">Отзывы</a></li>
                        <?php endif; ?>
                        <li class="mb-2"><a href="svedeniya.php">Сведения об организации</a></li>
                        <li><a href="login.php">Личный кабинет</a></li>
                    </ul>
                </div>
            </div>

            <hr class="footer-divider my-4">
            <p class="text-center footer-copy small mb-0">
                &copy; 2026 <?= SITE_NAME ?>. Все права защищены.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
