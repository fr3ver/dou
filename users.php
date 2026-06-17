<?php
require_once '_auth.php';
require_once __DIR__ . '/../includes/roles.php';

$allUsers = $pdo->query("
    SELECT u.id, u.username, u.full_name, u.phone, u.role_id, r.name AS role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.full_name
")->fetchAll(PDO::FETCH_ASSOC);

$staff = array_values(array_filter(
    $allUsers,
    static fn(array $u): bool => in_array((int)$u['role_id'], [2, 3, 4], true)
));
$parents = array_values(array_filter(
    $allUsers,
    static fn(array $u): bool => (int)$u['role_id'] === 1
));

function admin_render_users_table(array $users, int $currentUserId): void
{
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 admin-table">
            <thead class="table-light">
                <tr>
                    <th>ФИО</th>
                    <th>Логин</th>
                    <th>Телефон</th>
                    <th>Роль</th>
                    <th class="text-end">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users === []): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Никого нет</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['phone'] ?? '—') ?></td>
                    <td><span class="badge badge-news"><?= htmlspecialchars(role_label((int)$user['role_id'])) ?></span></td>
                    <td class="text-end text-nowrap">
                        <a href="edit_user.php?id=<?= (int)$user['id'] ?>" class="btn btn-sm btn-primary-dou"><i class="bi bi-pencil"></i></a>
                        <?php if ((int)$user['id'] !== $currentUserId): ?>
                        <form action="delete_user.php" method="POST" class="d-inline"
                              onsubmit="return confirm('Удалить пользователя «<?= htmlspecialchars($user['full_name'], ENT_QUOTES) ?>»?')">
                            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

admin_page_start('Пользователи', 'Учётные записи персонала и родителей');
$currentUserId = (int)$_SESSION['user_id'];
$staffCount = count($staff);
$parentsCount = count($parents);
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3 d-flex flex-wrap align-items-center gap-3">
        <label class="fw-semibold mb-0 text-nowrap" for="users-list-filter">
            <i class="bi bi-funnel me-1"></i>Показать:
        </label>
        <select id="users-list-filter" class="form-select" style="max-width: 220px;">
            <option value="staff">Персонал</option>
            <option value="parents">Родители</option>
            <option value="all">Все</option>
        </select>
        <div class="ms-auto d-flex flex-wrap gap-2" id="users-list-actions">
            <a href="add_employee.php" class="btn btn-sm btn-accent users-action-staff">
                <i class="bi bi-person-plus me-1"></i>Добавить сотрудника
            </a>
            <a href="add_user.php" class="btn btn-sm btn-accent users-action-parents d-none">
                <i class="bi bi-person-plus me-1"></i>Добавить родителя
            </a>
        </div>
    </div>
</div>

<div class="admin-users-section" data-users-section="staff">
<?php
admin_collapse_toolbar('users-staff', 'Персонал', true, (string)$staffCount, '', false);
?>
<?php admin_render_table_search('Поиск по ФИО, логину...'); ?>
<?php admin_render_users_table($staff, $currentUserId); ?>
<?php admin_render_table_search_end(); admin_collapse_toolbar_end(false); ?>
</div>

<div class="admin-users-section" data-users-section="parents">
<?php
admin_collapse_toolbar('users-parents', 'Родители', true, (string)$parentsCount, '', false);
?>
<?php admin_render_table_search('Поиск по ФИО, логину, телефону...'); ?>
<?php admin_render_users_table($parents, $currentUserId); ?>
<?php admin_render_table_search_end(); admin_collapse_toolbar_end(false); ?>
</div>

<?php
admin_append_footer(<<<'JS'
<script>
(function () {
    var filter = document.getElementById('users-list-filter');
    var sections = document.querySelectorAll('.admin-users-section');
    var btnStaff = document.querySelector('.users-action-staff');
    var btnParents = document.querySelector('.users-action-parents');
    if (!filter) return;

    function applyUsersFilter() {
        var mode = filter.value;
        sections.forEach(function (el) {
            var key = el.getAttribute('data-users-section');
            var show = mode === 'all' || mode === key;
            el.style.display = show ? '' : 'none';
        });
        if (btnStaff) btnStaff.classList.toggle('d-none', mode === 'parents');
        if (btnParents) btnParents.classList.toggle('d-none', mode !== 'parents');
    }

    filter.addEventListener('change', applyUsersFilter);
    applyUsersFilter();
})();
</script>
JS
);
admin_page_end();
