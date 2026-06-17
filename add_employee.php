<?php
require_once '_auth.php';
require_once '_employee_helpers.php';
require_once '_photo_helpers.php';
require_once '_password_generate.php';

admin_page_start('Добавить сотрудника');
?>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
<form action="save_employee.php" method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">ФИО <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Логин <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Телефон</label>
            <input type="text" name="phone" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Должность <span class="text-danger">*</span></label>
            <select name="position" class="form-select" required>
                <option value="">Выберите...</option>
                <?php admin_render_employee_position_options($pdo); ?>
            </select>
        </div>
    </div>
    <div class="mb-3" id="employee-group-wrap">
        <label class="form-label fw-semibold">Группа</label>
        <select name="group_id" id="employee-group-id" class="form-select-groups">
            <?php admin_render_employee_group_options($pdo); ?>
        </select>
        <div class="form-text" id="employee-group-hint">Для воспитателя и логопеда можно указать группу.</div>
    </div>
    <?php admin_render_employee_club_fields($pdo); ?>
    <?php admin_render_employee_profile_fields(); ?>
    <?php admin_render_photo_field(null); ?>
    <div class="mb-4">
        <label class="form-label fw-semibold" for="new_employee_password">Пароль <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="text" name="password" id="new_employee_password" class="form-control"
                   required minlength="4" autocomplete="new-password"
                   placeholder="Введите или сгенерируйте">
            <?php admin_render_password_generate_button('new_employee_password'); ?>
        </div>
        <div class="form-text">Минимум 4 символа.</div>
    </div>
    <button type="submit" class="btn btn-accent btn-lg">Создать</button>
    <a href="employees.php" class="btn btn-outline-secondary btn-lg">Отмена</a>
</form>
</div></div></div></div>
<script>
(function () {
    var position = document.querySelector('select[name="position"]');
    var groupWrap = document.getElementById('employee-group-wrap');
    var group = document.getElementById('employee-group-id');
    var clubsWrap = document.getElementById('employee-clubs-wrap');
    var profileWrap = document.querySelector('.mb-4.p-4.rounded-3.border.bg-light-subtle');
    if (!position) return;

    var seniorProfile = <?= json_encode(admin_default_employee_profile('Старший воспитатель'), JSON_UNESCAPED_UNICODE) ?>;

    function setField(name, value) {
        var el = document.querySelector('[name="' + name + '"]');
        if (!el || value === null || value === undefined || value === '') return;
        if (el.type === 'checkbox') {
            el.checked = !!value;
        } else {
            el.value = value;
        }
    }

    function syncSeniorFields() {
        var skip = position.value === 'Старший воспитатель';
        if (groupWrap) groupWrap.style.display = skip ? 'none' : '';
        if (group && skip) group.value = '0';
        if (clubsWrap) {
            clubsWrap.style.display = skip ? 'none' : '';
            if (skip) {
                clubsWrap.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                    cb.checked = false;
                });
            }
        }
        if (profileWrap) profileWrap.style.display = '';
        if (skip) {
            setField('education', seniorProfile.education);
            setField('retraining', seniorProfile.retraining);
            setField('qualification_upgrades', seniorProfile.qualification_upgrades);
            setField('experience_total_years', seniorProfile.experience_total_years);
            setField('experience_pedagogical_years', seniorProfile.experience_pedagogical_years);
            setField('experience_specialty_note', seniorProfile.experience_specialty_note);
            setField('show_on_public', seniorProfile.show_on_public);
        }
    }

    position.addEventListener('change', syncSeniorFields);
    syncSeniorFields();
})();
</script>
<?php
admin_append_password_generate_script();
admin_page_end();
?>
