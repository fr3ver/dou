<?php
require_once '_auth.php';
require_once '_club_helpers.php';
require_once __DIR__ . '/../includes/club_enrollment.php';

$pendingApps = 0;
try {
    $pendingApps = admin_club_applications_pending_count($pdo);
} catch (Exception $e) {
    $pendingApps = 0;
}

$clubs = $pdo->query("
    SELECT c.*, u.full_name AS teacher_name,
           (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id AND cm.status = 'enrolled') AS member_count
    FROM clubs c
    LEFT JOIN employees e ON c.teacher_id = e.id
    LEFT JOIN users u ON e.user_id = u.id
    ORDER BY c.name
")->fetchAll(PDO::FETCH_ASSOC);

admin_page_start('Кружки', 'Дополнительные занятия для детей');
?>
<div class="mb-4">
    <a href="club_applications.php" class="btn btn-outline-primary">
        <i class="bi bi-inbox me-1"></i>Заявки
        <?php if ($pendingApps > 0): ?>
            <span class="badge bg-danger ms-1"><?= $pendingApps ?></span>
        <?php endif; ?>
    </a>
</div>
<?php
$toolbar = '<a href="add_club.php" class="btn btn-sm btn-accent"><i class="bi bi-plus-lg me-1"></i>Добавить</a>';
admin_collapse_toolbar('clubs-list', 'Список кружков', false, (string) count($clubs), $toolbar, false);
?>
<?php admin_render_table_search('Поиск по названию, расписанию...'); ?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 admin-table">
        <thead class="table-light">
            <tr>
                <th>Название</th>
                <th>Возраст</th>
                <th>Расписание</th>
                <th>Руководитель</th>
                <th>Участники</th>
                <th>Мест</th>
                <th class="text-end">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($clubs) === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Кружков пока нет</td></tr>
            <?php endif; ?>
            <?php foreach ($clubs as $club): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars($club['name']) ?></td>
                <td><?= $club['age_category'] ? htmlspecialchars($club['age_category']) : '—' ?></td>
                <td><?= $club['schedule'] ? htmlspecialchars($club['schedule']) : '—' ?></td>
                <td><?= $club['teacher_name'] ? htmlspecialchars($club['teacher_name']) : '—' ?></td>
                <td>
                    <a href="club_members.php?id=<?= (int)$club['id'] ?>" class="text-decoration-none">
                        <?= admin_club_seats_label((int)$club['member_count'], $club['max_participants'] !== null ? (int)$club['max_participants'] : null) ?>
                    </a>
                </td>
                <td><?= $club['max_participants'] ? (int)$club['max_participants'] : '—' ?></td>
                <td class="text-end text-nowrap">
                    <a href="club_members.php?id=<?= (int)$club['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Состав">
                        <i class="bi bi-people"></i>
                    </a>
                    <a href="edit_club.php?id=<?= (int)$club['id'] ?>" class="btn btn-sm btn-primary-dou" title="Изменить"><i class="bi bi-pencil"></i></a>
                    <form action="delete_club.php" method="POST" class="d-inline"
                          onsubmit="return confirm('Удалить кружок «<?= htmlspecialchars($club['name'], ENT_QUOTES) ?>»?')">
                        <input type="hidden" name="id" value="<?= (int)$club['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php admin_render_table_search_end(); admin_collapse_toolbar_end(false); admin_page_end(); ?>
