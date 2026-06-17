<?php
require_once __DIR__ . '/includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $next     = $_POST['next'] ?? ($_GET['next'] ?? '');
    $allowedNext = ['change_password.php', 'chat/index.php'];
    $next     = in_array($next, $allowedNext, true) ? $next : '';
    $intent   = ($_POST['intent'] ?? '') === 'menu' ? 'menu' : '';

    if ($username === '' || $password === '') {
        $error = 'Введите логин и пароль';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, username, password, full_name, role_id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_id']   = (int)$user['role_id'];
                session_write_close();

                if ($next === 'change_password.php') {
                    header('Location: change_password.php');
                    exit;
                }
                if ($next === 'chat/index.php') {
                    header('Location: chat/index.php');
                    exit;
                }
                $roleId = (int)$user['role_id'];
                if ($intent === 'menu') {
                    if ($roleId === 3 || $roleId === 4) {
                        header('Location: admin/menu.php');
                    } elseif ($roleId === 2) {
                        header('Location: employee/dashboard.php#menu');
                    } elseif ($roleId === 1) {
                        header('Location: parent/dashboard.php#menu');
                    } else {
                        header('Location: index.php#menu');
                    }
                    exit;
                }

                if ($roleId === 3 || $roleId === 4) {
                    header('Location: admin/dashboard.php');
                } elseif ($roleId === 2) {
                    header('Location: employee/dashboard.php');
                } elseif ($roleId === 1) {
                    header('Location: parent/dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            }

            $error = 'Неверный логин или пароль';
        } catch (Exception $e) {
            $error = 'Ошибка сервера';
        }
    }
}

$next = $_GET['next'] ?? '';
$nextAllowed = in_array($next, ['change_password.php', 'chat/index.php'], true) ? $next : '';
$intentMenu = (($_GET['intent'] ?? '') === 'menu') || (($_POST['intent'] ?? '') === 'menu');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=9" rel="stylesheet">
</head>
<body class="login-page">

<div class="login-wrap">
    <a href="index.php" class="login-brand">
        <span class="brand-icon"><i class="bi bi-balloon-heart-fill"></i></span>
        <span><?= SITE_NAME ?></span>
    </a>
    <div class="login-card">
        <h1 class="login-title">Вход в систему</h1>

        <?php if ($nextAllowed === 'change_password.php'): ?>
            <div class="alert alert-info border-0 mb-3" role="alert">
                Войдите в систему — после входа откроется смена пароля.
            </div>
        <?php elseif ($intentMenu): ?>
            <div class="alert alert-info border-0 mb-3" role="alert">
                Войдите в систему — откроется меню питания в вашем кабинете.
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger border-0 mb-3" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php<?= $nextAllowed ? '?next=' . urlencode($nextAllowed) : ($intentMenu ? '?intent=menu' : '') ?>">
            <?php if ($nextAllowed): ?>
                <input type="hidden" name="next" value="<?= htmlspecialchars($nextAllowed) ?>">
            <?php endif; ?>
            <?php if ($intentMenu): ?>
                <input type="hidden" name="intent" value="menu">
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label" for="username">Логин</label>
                <input type="text" class="form-control login-input" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Пароль</label>
                <input type="password" class="form-control login-input" id="password" name="password"
                       required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-login w-100">Войти</button>
        </form>

        <div class="login-footer">
            <small>Для родителей, воспитателей и администрации</small>
            <a href="login.php?next=change_password.php" class="d-block mt-2">Сменить пароль</a>
            <a href="index.php" class="d-block mt-1">← На главную</a>
        </div>
    </div>
</div>

</body>
</html>
