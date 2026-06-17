<?php
require_once '_auth.php';
require_once '_group_helpers.php';

$groups = $pdo->query("
    SELECT g.id, g.name, g.age_category, g.capacity,
           (SELECT COUNT(*) FROM children c WHERE c.group_id = g.id) AS children_count,
           (SELECT GROUP_CONCAT(
                CONCAT(u.full_name, ' (', e.position, ')')
                ORDER BY FIELD(e.position, 'Воспитатель', 'Логопед'), u.full_name
                SEPARATOR ', '
            )
            FROM employees e
            JOIN users u ON u.id = e.user_id
            WHERE e.group_id = g.id AND e.position IN ('Воспитатель', 'Логопед')
           ) AS staff_names
    FROM `groups` g
")->fetchAll(PDO::FETCH_ASSOC);

$order = array_flip(dou_age_band_order());
usort($groups, static function ($a, $b) use ($order) {
    $ba = $order[dou_age_band($a['age_category'] ?? '')] ?? 99;
    $bb = $order[dou_age_band($b['age_category'] ?? '')] ?? 99;
    if ($ba !== $bb) {
        return $ba <=> $bb;
    }
    return strnatcasecmp($a['name'], $b['name']);
});

admin_page_start('Управление группами', 'Добавление, редактирование и назначение педагогов');
$toolbar = '<a href="add_group.php" class="btn btn-sm btn-accent"><i class="bi bi-plus-lg me-1"></i>Новая группа</a>';
admin_collapse_toolbar('groups-list', 'Список групп', false, (string)count($groups), $toolbar, false);
?>
<?php admin_render_table_search('Поиск по названию, воспитателю...'); ?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 admin-table">
        <thead class="table-light">
            <tr>
                <th>Название</th><th>Возраст</th><th>Детей</th><th>Вместимость</th><th>Педагоги</th>
                <th class="text-end">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groups as $g): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars($g['name']) ?></td>
                <td><?= htmlspecialchars(admin_group_age_display($g['age_category'] ?? null)) ?></td>
                <td>
                    <a href="group_children.php?id=<?= (int)$g['id'] ?>" class="text-decoration-none">
                        <span class="badge bg-soft-green text-dark">
                            <?= admin_group_children_label((int)$g['children_count'], (int)$g['capacity']) ?>
                        </span>
                    </a>
                </td>
                <td><?= (int)$g['capacity'] ?></td>
                <td><?= !empty($g['staff_names']) ? htmlspecialchars($g['staff_names']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end text-nowrap">
                    <a href="group_children.php?id=<?= (int)$g['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Состав">
                        <i class="bi bi-people"></i>
                    </a>
                    <a href="edit_group.php?id=<?= (int)$g['id'] ?>" class="btn btn-sm btn-primary-dou" title="Изменить"><i class="bi bi-pencil"></i></a>
                    <?php if ((int)$g['children_count'] === 0): ?>
                    <form action="delete_group.php" method="POST" class="d-inline"
                          onsubmit="return confirm('Удалить группу «<?= htmlspecialchars($g['name'], ENT_QUOTES) ?>»?')">
                        <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
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
