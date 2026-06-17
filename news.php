<?php

require_once '_auth.php';

require_once '_news_helpers.php';

require_once __DIR__ . '/../includes/entity_groups.php';



$groupNamesSql = entity_groups_news_names_sql('n');

$news_list = $pdo->query("

    SELECT n.*, u.full_name AS author_name, {$groupNamesSql} AS group_names
    FROM news n
    LEFT JOIN users u ON n.created_by = u.id

    ORDER BY n.publish_date DESC, n.id DESC

")->fetchAll(PDO::FETCH_ASSOC);



$roleLabels = admin_news_target_roles();

admin_page_start('Новости', 'Публикация объявлений для сайта и личных кабинетов');

$toolbar = '<a href="add_news.php" class="btn btn-sm btn-accent"><i class="bi bi-plus-lg me-1"></i>Добавить</a>';

admin_collapse_toolbar('news-list', 'Список новостей', false, (string)count($news_list), $toolbar, false);

?>

<?php admin_render_table_search('Поиск по заголовку, автору...'); ?>

<div class="table-responsive">

    <table class="table table-hover align-middle mb-0 admin-table">

        <thead class="table-light">

            <tr>

                <th>Дата</th><th>Заголовок</th><th>Аудитория</th><th>Группа</th><th>Автор</th>

                <th class="text-end">Действия</th>

            </tr>

        </thead>

        <tbody>

            <?php if (count($news_list) === 0): ?>

                <tr><td colspan="6" class="text-center text-muted py-4">Новостей пока нет</td></tr>

            <?php endif; ?>

            <?php foreach ($news_list as $n): ?>

            <tr>

                <td><?= date('d.m.Y', strtotime($n['publish_date'])) ?></td>

                <td class="fw-semibold">

                    <?php if (!empty($n['image_url'])): ?><i class="bi bi-image text-muted me-1"></i><?php endif; ?>

                    <?= htmlspecialchars($n['title']) ?>

                </td>

                <td><span class="badge badge-news"><?= htmlspecialchars($roleLabels[$n['target_role']] ?? $n['target_role']) ?></span></td>

                <td><?= htmlspecialchars(entity_groups_format_news_groups($n)) ?></td>

                <td class="small text-muted"><?= htmlspecialchars($n['author_name'] ?? '—') ?></td>

                <td class="text-end">

                    <a href="edit_news.php?id=<?= (int)$n['id'] ?>" class="btn btn-sm btn-primary-dou"><i class="bi bi-pencil"></i></a>

                    <form action="delete_news.php" method="POST" class="d-inline"

                          onsubmit="return confirm('Удалить новость «<?= htmlspecialchars($n['title'], ENT_QUOTES) ?>»?')">

                        <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">

                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>

                    </form>

                </td>

            </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php admin_render_table_search_end(); admin_collapse_toolbar_end(false); admin_page_end(); ?>

