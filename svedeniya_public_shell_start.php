<?php
/**
 * @var string $pageTitle
 * @var list<array{label: string, href?: string}> $breadcrumbs
 */
$isLoggedIn = isset($_SESSION['user_id']);
$roleId = $isLoggedIn ? (int)$_SESSION['role_id'] : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=42" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dou sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <span class="brand-icon"><i class="bi bi-balloon-heart-fill"></i></span>
            <span><?= SITE_NAME ?></span>
        </a>
        <div class="ms-auto d-flex gap-2">
            <a href="svedeniya.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-journal-text me-1"></i>Сведения
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-house me-1"></i>Главная
            </a>
            <?php if ($isLoggedIn && $roleId === 1): ?>
            <a href="parent/dashboard.php" class="btn btn-accent btn-sm">Кабинет</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="section-padding bg-svedeniya-gos">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small mb-0">
                <?php foreach ($breadcrumbs as $i => $crumb): ?>
                    <?php if ($i === count($breadcrumbs) - 1): ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($crumb['label']) ?></li>
                    <?php else: ?>
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars($crumb['href'] ?? '#') ?>"><?= htmlspecialchars($crumb['label']) ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
