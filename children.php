<?php
require_once '_auth.php';
require_once '_allergies.php';
require_once '_group_helpers.php';
require_once '_child_documents_section.php';

$children = $pdo->query("
    SELECT c.id, c.full_name, c.date_of_birth, c.has_tnr, c.group_id,
           " . admin_child_allergies_sql() . " AS allergy_names,
           g.name AS group_name, g.age_category,
           p.full_name AS parent_name
    FROM children c
    JOIN `groups` g ON c.group_id = g.id
    JOIN users p ON c.parent_id = p.id
    ORDER BY c.full_name
")->fetchAll(PDO::FETCH_ASSOC);

$byGroupId = [];
foreach ($children as $child) {
    $byGroupId[(int) $child['group_id']][] = $child;
}

$groupsOrdered = admin_groups_sorted_by_band($pdo);
$colCount = 7;

admin_page_start('Управление детьми', 'Редактирование и удаление воспитанников');
$toolbar = '<a href="add_child.php" class="btn btn-sm btn-accent"><i class="bi bi-plus-lg me-1"></i>Добавить</a>';
admin_collapse_toolbar('children-list', 'Список детей', false, (string) count($children), $toolbar, false);
?>
<?php admin_render_table_search('Поиск по ФИО, группе, родителю...'); ?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 admin-table admin-table-grouped">
        <thead class="table-light">
            <tr>
                <th>ФИО</th>
                <th>Родитель</th>
                <th>Дата рождения</th>
                <th>ТНР</th>
                <th>Аллергии</th>
                <th>Справки</th>
                <th class="text-end">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($children === []): ?>
            <tr>
                <td colspan="<?= $colCount ?>" class="text-center text-muted py-4">Детей пока нет</td>
            </tr>
            <?php else: ?>
                <?php foreach ($groupsOrdered as $group):
                    $gid = (int) $group['id'];
                    if (empty($byGroupId[$gid])) {
                        continue;
                    }
                    $groupChildren = $byGroupId[$gid];
                    $count = count($groupChildren);
                ?>
                <tr class="admin-table-group-header" data-group-header data-group-key="<?= $gid ?>">
                    <td colspan="<?= $colCount ?>">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="admin-table-group-title"><?= htmlspecialchars($group['name']) ?></span>
                            <span class="text-muted small"><?= htmlspecialchars(admin_group_age_display($group['age_category'] ?? null)) ?></span>
                            <span class="badge bg-soft-green text-dark"><?= $count ?> <?= $count === 1 ? 'ребёнок' : ($count < 5 ? 'ребёнка' : 'детей') ?></span>
                            <a href="group_children.php?id=<?= $gid ?>" class="small link-more ms-auto">Состав группы</a>
                        </div>
                    </td>
                </tr>
                <?php foreach ($groupChildren as $child): ?>
                <tr class="admin-table-group-row" data-group-key="<?= $gid ?>">
                    <td class="fw-semibold ps-4"><?= htmlspecialchars($child['full_name']) ?></td>
                    <td><?= htmlspecialchars($child['parent_name']) ?></td>
                    <td><?= date('d.m.Y', strtotime($child['date_of_birth'])) ?></td>
                    <td><?= $child['has_tnr'] ? '<span class="badge badge-tnr">ТНР</span>' : '—' ?></td>
                    <td class="small text-allergy"><?= !empty($child['allergy_names']) ? htmlspecialchars($child['allergy_names']) : '—' ?></td>
                    <td class="small"><?= admin_child_documents_badge($pdo, (int)$child['id']) ?></td>
                    <td class="text-end">
                        <a href="edit_child.php?id=<?= (int) $child['id'] ?>" class="btn btn-sm btn-primary-dou"><i class="bi bi-pencil"></i></a>
                        <form action="delete_child.php" method="POST" class="d-inline"
                              onsubmit="return confirm('Удалить «<?= htmlspecialchars($child['full_name'], ENT_QUOTES) ?>»?')">
                            <input type="hidden" name="id" value="<?= (int) $child['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php admin_render_table_search_end(); admin_collapse_toolbar_end(false); admin_page_end(); ?>
