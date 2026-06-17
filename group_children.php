<?php
require_once '_auth.php';
require_once '_group_helpers.php';
require_once '_child_documents_section.php';

$id = (int) ($_GET['id'] ?? 0);
$group = admin_group_find($pdo, $id);

if (!$group) {
    admin_flash('error', 'Группа не найдена');
    header('Location: groups.php');
    exit;
}

$children = admin_group_children($pdo, $id);
$count = count($children);
$capacity = (int) ($group['capacity'] ?? 0);

admin_page_start('Состав группы', $group['name']);
?>

<a href="groups.php" class="admin-back-link d-inline-flex mb-3">
    <i class="bi bi-arrow-left"></i> К списку групп
</a>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3"><?= htmlspecialchars($group['name']) ?></h2>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2">
                        <i class="bi bi-balloon me-2 text-muted"></i>
                        <?= htmlspecialchars(admin_group_age_display($group['age_category'] ?? null)) ?>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-person-badge me-2 text-muted"></i>
                        <?= !empty($group['staff_names'])
                            ? htmlspecialchars($group['staff_names'])
                            : 'Педагоги не назначены' ?>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-people me-2 text-muted"></i>
                        <strong><?= admin_group_children_label($count, $capacity) ?></strong> детей
                    </li>
                </ul>
                <hr>
                <a href="edit_group.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i>Настройки группы
                </a>
                <a href="add_child.php" class="btn btn-sm btn-accent ms-1">
                    <i class="bi bi-plus-lg me-1"></i>Добавить ребёнка
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 fw-semibold mb-0">Воспитанники</h3>
                    <span class="badge badge-news"><?= $count ?> чел.</span>
                </div>

                <?php if ($children === []): ?>
                    <p class="text-muted mb-0">
                        В группе пока нет детей.
                        <a href="add_child.php">Добавить ребёнка</a> или назначьте группу при редактировании.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 admin-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ФИО</th>
                                    <th>Дата рождения</th>
                                    <th>Родитель</th>
                                    <th>ТНР</th>
                                    <th>Аллергии</th>
                                    <th>Справки</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($children as $child): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($child['full_name']) ?></td>
                                    <td class="small"><?= date('d.m.Y', strtotime($child['date_of_birth'])) ?></td>
                                    <td class="small">
                                        <?= htmlspecialchars($child['parent_name']) ?>
                                        <?php if (!empty($child['parent_phone'])): ?>
                                            <br><span class="text-muted"><?= htmlspecialchars($child['parent_phone']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $child['has_tnr'] ? '<span class="badge badge-tnr">ТНР</span>' : '—' ?></td>
                                    <td class="small text-allergy">
                                        <?= !empty($child['allergy_names']) ? htmlspecialchars($child['allergy_names']) : '—' ?>
                                    </td>
                                    <td class="small"><?= admin_child_documents_badge($pdo, (int)$child['id']) ?></td>
                                    <td class="text-end">
                                        <a href="edit_child.php?id=<?= (int) $child['id'] ?>"
                                           class="btn btn-sm btn-primary-dou" title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php admin_page_end(); ?>
