<?php
require_once '_auth.php';
require_once '_employee_helpers.php';

$employees = $pdo->query("
    SELECT e.id, e.position, e.group_id,
           u.id AS user_id, u.username, u.full_name, u.phone, u.photo_url,
           g.name AS group_name,
           (SELECT GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ')
            FROM clubs c WHERE c.teacher_id = e.id) AS club_names
    FROM employees e
    JOIN users u ON e.user_id = u.id
    LEFT JOIN `groups` g ON e.group_id = g.id
    ORDER BY e.position, u.full_name
")->fetchAll(PDO::FETCH_ASSOC);

admin_page_start('Сотрудники', 'Должности, группы и учётные записи персонала');
$toolbar = '<a href="add_employee.php" class="btn btn-sm btn-accent"><i class="bi bi-person-plus me-1"></i>Добавить</a>';
admin_collapse_toolbar('employees-list', 'Список сотрудников', false, (string)count($employees), $toolbar, false);
?>
<?php admin_render_table_search('Поиск по ФИО, логину, должности...'); ?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 admin-table">
        <thead class="table-light">
            <tr>
                <th></th><th>ФИО</th><th>Логин</th><th>Телефон</th><th>Должность</th><th>Группа</th><th>Кружки</th>
                <th class="text-end">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($employees) === 0): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Сотрудников нет</td></tr>
            <?php endif; ?>
            <?php foreach ($employees as $emp): ?>
            <tr>
                <td>
                    <?php if (!empty($emp['photo_url'])): ?>
                        <img src="../<?= htmlspecialchars($emp['photo_url']) ?>" alt=""
                             class="group-card-teacher-thumb">
                    <?php else: ?>
                        <span class="group-card-teacher-thumb group-card-teacher-thumb-placeholder">—</span>
                    <?php endif; ?>
                </td>
                <td class="fw-semibold"><?= htmlspecialchars($emp['full_name']) ?></td>
                <td><?= htmlspecialchars($emp['username']) ?></td>
                <td><?= htmlspecialchars($emp['phone'] ?? '—') ?></td>
                <td><span class="badge badge-news"><?= htmlspecialchars($emp['position']) ?></span></td>
                <td><?= !empty($emp['group_name']) ? htmlspecialchars($emp['group_name']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= !empty($emp['club_names']) ? htmlspecialchars($emp['club_names']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end">
                    <a href="edit_employee.php?id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-primary-dou"><i class="bi bi-pencil"></i></a>
                    <a href="edit_user.php?id=<?= (int)$emp['user_id'] ?>" class="btn btn-sm btn-outline-secondary" title="Учётная запись"><i class="bi bi-key"></i></a>
                    <?php if ((int)$emp['user_id'] !== (int)$_SESSION['user_id']): ?>
                    <form action="delete_employee.php" method="POST" class="d-inline"
                          onsubmit="return confirm('Удалить сотрудника «<?= htmlspecialchars($emp['full_name'], ENT_QUOTES) ?>»?')">
                        <input type="hidden" name="id" value="<?= (int)$emp['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php admin_render_table_search_end(); admin_collapse_toolbar_end(false); admin_page_end(); ?>
