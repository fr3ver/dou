<?php
require_once '_auth.php';
require_once '_password_generate.php';
require_once __DIR__ . '/../includes/roles.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, username, full_name, phone, role_id FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    admin_flash('error', 'Пользователь не найден');
    header('Location: users.php');
    exit;
}

admin_page_start('Редактировать пользователя', $user['full_name']);
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="fw-semibold mb-3">Данные пользователя</h5>
                <form action="update_user.php" method="POST">
                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">ФИО</label>
                            <input type="text" name="full_name" class="form-control" required
                                   value="<?= htmlspecialchars($user['full_name']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Логин</label>
                            <input type="text" name="username" class="form-control" required
                                   value="<?= htmlspecialchars($user['username']) ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Телефон</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Роль</label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach (role_labels() as $id => $label): ?>
                                    <option value="<?= (int)$id ?>" <?= (int)$user['role_id'] === (int)$id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ((int)$user['role_id'] === 2): ?>
                                <div class="form-text">Группу сотрудника меняйте в разделе «Группы»</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent">Сохранить</button>
                    <a href="users.php" class="btn btn-outline-secondary">Отмена</a>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-semibold mb-2"><i class="bi bi-key me-1"></i>Пароль</h5>
                <p class="text-muted small mb-3">Задайте новый пароль вручную или сгенерируйте.</p>
                <form action="reset_password.php" method="POST">
                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="new_password">Новый пароль</label>
                        <div class="input-group">
                            <input type="text" name="password" id="new_password" class="form-control"
                                   required minlength="4" autocomplete="new-password"
                                   placeholder="Введите или сгенерируйте">
                            <?php admin_render_password_generate_button('new_password'); ?>
                        </div>
                        <div class="form-text">Минимум 4 символа. При создании пользователя пароль задаётся в форме «Добавить».</div>
                    </div>
                    <button type="submit" class="btn btn-primary-dou"
                            onclick="return confirm('Сохранить новый пароль для этого пользователя?')">
                        Сохранить пароль
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
admin_append_password_generate_script();
admin_page_end();
?>
