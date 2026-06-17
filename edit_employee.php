<?php

require_once '_auth.php';
require_once '_employee_helpers.php';
require_once '_photo_helpers.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT e.*, u.username, u.full_name, u.phone, u.photo_url
    FROM employees e
    JOIN users u ON e.user_id = u.id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    admin_flash('error', 'Сотрудник не найден');
    header('Location: employees.php');
    exit;
}

admin_page_start('Редактировать сотрудника', $emp['full_name']);
?>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
<form action="update_employee.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= (int)$emp['id'] ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">ФИО <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($emp['full_name']) ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Логин</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($emp['username']) ?>" disabled>
            <div class="form-text"><a href="edit_user.php?id=<?= (int)$emp['user_id'] ?>">Изменить логин / пароль</a></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Телефон</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($emp['phone'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Должность <span class="text-danger">*</span></label>
            <select name="position" class="form-select" required>
                <?php admin_render_employee_position_options($pdo, $emp['position']); ?>
            </select>
        </div>
    </div>
    <div class="mb-3" id="employee-group-wrap">
        <label class="form-label fw-semibold">Группа</label>
        <select name="group_id" id="employee-group-id" class="form-select-groups">
            <?php admin_render_employee_group_options($pdo, $emp['group_id'] ? (int)$emp['group_id'] : null); ?>
        </select>
        <div class="form-text" id="employee-group-hint">Для воспитателя и логопеда можно указать группу.</div>
    </div>
    <?php admin_render_employee_club_fields($pdo, (int)$emp['id']); ?>
    <?php admin_render_employee_profile_fields($emp); ?>
    <?php admin_render_photo_field($emp['photo_url'] ?? null); ?>
    <button type="submit" class="btn btn-accent btn-lg">Сохранить</button>
    <a href="employees.php" class="btn btn-outline-secondary btn-lg">Отмена</a>
</form>
</div></div></div></div>
<script>
(function () {
    var position = document.querySelector('select[name="position"]');
    var groupWrap = document.getElementById('employee-group-wrap');
    var group = document.getElementById('employee-group-id');
    var clubsWrap = document.getElementById('employee-clubs-wrap');
    if (!position) return;

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
    }

    position.addEventListener('change', syncSeniorFields);
    syncSeniorFields();
})();
</script>
<?php admin_page_end(); ?>