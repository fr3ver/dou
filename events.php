<?php

require_once '_auth.php';

require_once __DIR__ . '/../includes/entity_groups.php';



$groupNamesSql = entity_groups_event_names_sql('e');

$events = $pdo->query("

    SELECT e.*, u.full_name AS creator_name, {$groupNamesSql} AS group_names
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id

    ORDER BY e.event_date DESC, e.event_time DESC

")->fetchAll(PDO::FETCH_ASSOC);



$statusLabels = [

    'active' => ['label' => 'Активно', 'class' => 'bg-soft-green text-dark'],

    'finished' => ['label' => 'Завершено', 'class' => 'bg-secondary'],

    'cancelled' => ['label' => 'Отменено', 'class' => 'bg-soft-yellow text-dark'],

    'postponed' => ['label' => 'Перенесено', 'class' => 'bg-soft-blue text-dark'],

];



admin_page_start('Мероприятия', 'Планирование событий и праздников');

$toolbar = '<a href="add_event.php" class="btn btn-sm btn-accent"><i class="bi bi-plus-lg me-1"></i>Добавить</a>';

admin_collapse_toolbar('events-list', 'Список мероприятий', false, (string)count($events), $toolbar, false);

?>

<?php admin_render_table_search('Поиск по названию, группе, месту...'); ?>

<div class="table-responsive">

        <table class="table table-hover align-middle mb-0 admin-table">

            <thead class="table-light">

                <tr>

                    <th>Дата</th>

                    <th>Название</th>

                    <th>Группа</th>

                    <th>Место</th>

                    <th>Статус</th>

                    <th class="text-end">Действия</th>

                </tr>

            </thead>

            <tbody>

                <?php if (count($events) === 0): ?>

                    <tr><td colspan="6" class="text-center text-muted py-4">Мероприятий пока нет</td></tr>

                <?php endif; ?>

                <?php foreach ($events as $e):

                    $st = $statusLabels[$e['status']] ?? ['label' => $e['status'], 'class' => 'bg-secondary'];

                ?>

                <tr>

                    <td>

                        <?= date('d.m.Y', strtotime($e['event_date'])) ?>

                        <?php if ($e['event_time']): ?><br><small class="text-muted"><?= substr($e['event_time'], 0, 5) ?></small><?php endif; ?>

                    </td>

                    <td class="fw-semibold"><?= htmlspecialchars($e['title']) ?></td>

                    <td><?= htmlspecialchars(entity_groups_format_event_groups($e)) ?></td>

                    <td><?= htmlspecialchars($e['location'] ?? '—') ?></td>

                    <td><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>

                    <td class="text-end">

                        <a href="edit_event.php?id=<?= (int)$e['id'] ?>" class="btn btn-sm btn-primary-dou"><i class="bi bi-pencil"></i></a>

                        <form action="delete_event.php" method="POST" class="d-inline" onsubmit="return confirm('Удалить мероприятие?')">

                            <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">

                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>

                        </form>

                    </td>

                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php admin_render_table_search_end(); admin_collapse_toolbar_end(false); admin_page_end(); ?>

