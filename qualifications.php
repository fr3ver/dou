<?php
require_once '_auth.php';
require_once '_qualification_helpers.php';

if (!role_can_access_qualifications((int)$_SESSION['role_id'])) {
    admin_flash('danger', 'Нет доступа к учёту повышения квалификации.');
    header('Location: dashboard.php');
    exit;
}

$reminders = admin_qualification_reminders($pdo);
$employees = admin_qualification_employees_overview($pdo);

admin_page_start(
    'Повышение квалификации',
    'Учёт курсов, сроки и напоминания (цикл ' . QUALIFICATION_CYCLE_YEARS . ' года)'
);
?>

<?php if ($reminders !== []): ?>
<div class="alert alert-warning border-0 shadow-sm mb-4">
    <h2 class="h6 fw-bold mb-2"><i class="bi bi-bell me-1"></i>Требует внимания</h2>
    <ul class="mb-0 ps-3">
        <?php foreach ($reminders as $row): ?>
        <li class="mb-1">
            <strong><?= htmlspecialchars($row['full_name']) ?></strong>
            (<?= htmlspecialchars($row['position']) ?>)
            — следующее повышение до <strong><?= admin_qualification_format_date($row['next_due_on']) ?></strong>
            <?= admin_qualification_status_badge($row['status']) ?>
            <a href="employee_qualifications.php?id=<?= (int)$row['employee_id'] ?>" class="ms-1">Открыть</a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6 fw-semibold mb-2">Как это работает</h2>
        <p class="small text-muted mb-0">
            После каждого курса система считает срок следующего повышения квалификации
            (+<?= QUALIFICATION_CYCLE_YEARS ?> года от даты удостоверения).
            Напоминание появляется за <?= QUALIFICATION_REMINDER_DAYS ?> дней до срока.
            Текст для раздела «Наш коллектив» на сайте обновляется автоматически.
        </p>
    </div>
</div>

<?php
$toolbar = '';
admin_collapse_toolbar(
    'qualifications-list',
    'Сотрудники и сроки',
    $reminders !== [],
    (string)count($employees),
    $toolbar,
    false
);
?>
<?php admin_render_table_search('Поиск по ФИО, должности...'); ?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 admin-table">
        <thead class="table-light">
            <tr>
                <th>Сотрудник</th>
                <th>Должность</th>
                <th>Последний курс</th>
                <th>Окончание</th>
                <th>Следующий срок</th>
                <th>Статус</th>
                <th class="text-end">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($employees === []): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Сотрудников нет</td></tr>
            <?php endif; ?>
            <?php foreach ($employees as $row): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['position']) ?></td>
                <td><?= !empty($row['last_course']) ? htmlspecialchars($row['last_course']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= admin_qualification_format_date($row['completed_on'] ?? null) ?></td>
                <td><?= admin_qualification_format_date($row['next_due_on'] ?? null) ?></td>
                <td><?= admin_qualification_status_badge($row['status']) ?></td>
                <td class="text-end">
                    <a href="employee_qualifications.php?id=<?= (int)$row['employee_id'] ?>"
                       class="btn btn-sm btn-primary-dou">
                        <i class="bi bi-journal-plus"></i> Курс
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
admin_render_table_search_end();
admin_collapse_toolbar_end(false);
admin_page_end();
