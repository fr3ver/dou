<?php
require_once '_auth.php';
require_once '_password_generate.php';
admin_page_start('Добавить пользователя', 'Родитель или администратор');
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="save_user.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">ФИО</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Логин</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Телефон</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Роль</label>
                            <select name="role_id" class="form-select" required>
                                <option value="">Выберите роль</option>
                                <option value="1">Родитель</option>
                                <option value="3">Администратор</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="new_user_password">Пароль</label>
                        <div class="input-group">
                            <input type="text" name="password" id="new_user_password" class="form-control"
                                   required minlength="4" autocomplete="new-password"
                                   placeholder="Введите или сгенерируйте">
                            <?php admin_render_password_generate_button('new_user_password'); ?>
                        </div>
                        <div class="form-text">Минимум 4 символа.</div>
                    </div>
                    <button type="submit" class="btn btn-accent">Создать</button>
                    <a href="users.php" class="btn btn-outline-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
admin_append_password_generate_script();
admin_page_end();
?>
