<?php
require_once '_auth.php';
require_once '_qualification_helpers.php';

if (!role_can_access_qualifications((int)$_SESSION['role_id'])) {
    admin_flash('danger', 'Нет доступа.');
    header('Location: dashboard.php');
    exit;
}

$employeeId = (int)($_GET['id'] ?? 0);
$emp = admin_qualification_employee_exists($pdo, $employeeId);

if (!$emp) {
    admin_flash('danger', 'Сотрудник не найден.');
    header('Location: qualifications.php');
    exit;
}

$courses = admin_qualification_upgrades_for_employee($pdo, $employeeId);
$course = $courses[0] ?? null;
$latest = admin_qualification_latest_due($pdo, $employeeId);
$status = admin_qualification_status($latest['next_due_on'] ?? null);

admin_page_start('Повышение квалификации', $emp['full_name']);
?>
<a href="qualifications.php" class="admin-back-link d-inline-block mb-3">
    <i class="bi bi-arrow-left"></i> К списку сотрудников
</a>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <div>
            <div class="text-muted small">Должность</div>
            <div class="fw-semibold"><?= htmlspecialchars($emp['position']) ?></div>
        </div>
        <div>
            <div class="text-muted small">Следующий срок</div>
            <div class="fw-semibold"><?= admin_qualification_format_date($latest['next_due_on'] ?? null) ?></div>
        </div>
        <div><?= admin_qualification_status_badge($status) ?></div>
        <div class="ms-auto">
            <a href="edit_employee.php?id=<?= $employeeId ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-person"></i> Карточка сотрудника
            </a>
        </div>
    </div>
</div>

<?php admin_collapse_start('qual-add', $course ? 'Редактировать курс' : 'Добавить курс', true); ?>
<form action="save_qualification.php" method="POST" class="p-3">
    <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Название программы <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required maxlength="255"
                   value="<?= htmlspecialchars($course['title'] ?? '') ?>"
                   placeholder="«Современные образовательные программы ДОУ»">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Учебное заведение / центр</label>
            <input type="text" name="organization" class="form-control" maxlength="255"
                   value="<?= htmlspecialchars($course['organization'] ?? '') ?>"
                   placeholder="ИРО, вуз, учебный центр">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Дата окончания <span class="text-danger">*</span></label>
            <input type="date" name="completed_on" class="form-control" required
                   value="<?= htmlspecialchars($course['completed_on'] ?? '') ?>">
            <div class="form-text">Дата на удостоверении</div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Следующий срок</label>
            <input type="date" name="next_due_on" class="form-control"
                   value="<?= htmlspecialchars($course['next_due_on'] ?? '') ?>">
            <div class="form-text">Пусто — +<?= QUALIFICATION_CYCLE_YEARS ?> года от окончания</div>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Часов</label>
            <input type="number" name="hours" class="form-control" min="1" max="1000"
                   value="<?= htmlspecialchars((string)($course['hours'] ?? '')) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">№ удостоверения</label>
            <input type="text" name="certificate_no" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($course['certificate_no'] ?? '') ?>"
                   placeholder="24-03-001">
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Примечание</label>
            <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($course['notes'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-accent">
                <?= $course ? 'Сохранить изменения' : 'Добавить курс' ?>
            </button>
        </div>
    </div>
</form>
<?php if ($course): ?>
<form action="delete_qualification.php" method="POST" class="px-3 pb-3"
      onsubmit="return confirm('Удалить данные о курсе?')">
    <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
    <button type="submit" class="btn btn-outline-danger">
        <i class="bi bi-trash me-1"></i>Удалить курс
    </button>
</form>
<?php endif; ?>
<?php admin_collapse_end(); ?>

<?php if (!$course): ?>
<div class="alert alert-light border small mb-0">
    Курс повышения квалификации ещё не указан. Заполните форму выше — текст для «Наш коллектив» обновится автоматически.
</div>
<?php endif; ?>

<?php admin_page_end(); ?>
