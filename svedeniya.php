<?php

require_once '_auth.php';

require_once __DIR__ . '/../includes/svedeniya.php';



svedeniya_seed_sections($pdo);

$sections = svedeniya_all_sections($pdo);



admin_page_start(

    'Сведения об организации',

    'Единое управление разделами сайта: тексты и прикреплённые файлы'

);

?>

<div class="mb-3">

    <a href="../svedeniya.php" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">

        <i class="bi bi-box-arrow-up-right me-1"></i>Открыть на сайте

    </a>

</div>

<?php admin_render_table_search('Поиск по разделу...'); ?>

<div class="table-responsive">

    <table class="table table-hover align-middle mb-0 admin-table">

        <thead class="table-light">

            <tr>

                <th>Раздел</th>

                <th>Тип</th>

                <th>Файлы</th>

                <th>Обновлено</th>

                <th class="text-end">Действия</th>

            </tr>

        </thead>

        <tbody>

            <?php foreach ($sections as $section):
                $isLink = ($section['type'] ?? '') === 'link';
                $fileCount = $isLink ? 0 : org_document_count_for_section($pdo, $section['slug']);
                $db = $isLink ? null : svedeniya_get_section($pdo, $section['slug']);
                $publicHref = '../' . ltrim(svedeniya_section_url($section['slug']), '/');
            ?>

            <tr>

                <td class="fw-semibold"><?= htmlspecialchars($section['title']) ?></td>

                <td>

                    <?php if ($isLink): ?>
                    <span class="badge bg-secondary">Ссылка</span>
                    <?php else: ?>
                    <span class="badge badge-news">Текст + файлы</span>
                    <?php endif; ?>

                </td>

                <td class="small"><?= $isLink ? '—' : (string)$fileCount ?></td>

                <td class="small text-muted">

                    <?= !$isLink && !empty($db['updated_at']) ? date('d.m.Y H:i', strtotime($db['updated_at'])) : '—' ?>

                </td>

                <td class="text-end text-nowrap">

                    <?php if ($isLink): ?>
                    <a href="<?= htmlspecialchars($publicHref) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Открыть
                    </a>
                    <?php else: ?>
                    <a href="edit_svedeniya_section.php?slug=<?= urlencode($section['slug']) ?>" class="btn btn-sm btn-primary-dou">

                        <i class="bi bi-pencil me-1"></i>Изменить

                    </a>

                    <a href="<?= htmlspecialchars('../svedeniya_section.php?slug=' . urlencode($section['slug'])) ?>"

                       class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">

                        <i class="bi bi-eye"></i>

                    </a>
                    <?php endif; ?>

                </td>

            </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php admin_render_table_search_end(); admin_page_end(); ?>


