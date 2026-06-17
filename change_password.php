<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth_helpers.php';

auth_require_login();

$roleId = (int)$_SESSION['role_id'];
$backUrl = auth_dashboard_url($roleId);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        $error = 'Заполните все поля';
    } elseif (strlen($new) < 4) {
        $error = 'Новый пароль — минимум 4 символа';
    } elseif ($new !== $confirm) {
        $error = 'Новый пароль и подтверждение не совпадают';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($current, $row['password'])) {
                $error = 'Текущий пароль неверный';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                    ->execute([$hash, $_SESSION['user_id']]);
                $success = 'Пароль успешно изменён';
            }
        } catch (Exception $e) {
            $error = 'Ошибка сервера';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Смена пароля — <?= SITE_NAME ?></title>
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
        <h1 class="login-title">Смена пароля</h1>
        <p class="login-subtitle"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></p>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success border-0 mb-3" role="alert"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger border-0 mb-3" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="change_password.php">
            <div class="mb-3">
                <label class="form-label" for="current_password">Текущий пароль</label>
                <input type="password" class="form-control login-input" id="current_password"
                       name="current_password" required autocomplete="current-password">
            </div>
            <div class="mb-3">
                <label class="form-label" for="new_password">Новый пароль</label>
                <input type="password" class="form-control login-input" id="new_password"
                       name="new_password" required minlength="4" autocomplete="new-password">
            </div>
            <div class="mb-4">
                <label class="form-label" for="confirm_password">Повторите новый пароль</label>
                <input type="password" class="form-control login-input" id="confirm_password"
                       name="confirm_password" required minlength="4" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-login w-100">Сохранить</button>
        </form>

        <div class="login-footer">
            <a href="<?= htmlspecialchars($backUrl) ?>">← Вернуться в кабинет</a>
            <a href="api/logout.php" class="d-block mt-1">Выйти</a>
        </div>
    </div>
</div>

</body>
</html>
