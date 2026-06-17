<?php
require_once '_auth.php';
require_once '_allergies.php';

$allergies = admin_get_allergies($pdo);
admin_page_start('Справочник аллергенов', 'Управление списком аллергенов для детей и меню');
?>

<?php admin_collapse_start('allergy-add', 'Добавить аллерген', false); ?>
<div class="p-3">
    <form action="save_allergy.php" method="POST" class="row g-2 align-items-end">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Название</label>
            <input type="text" name="name" class="form-control" required maxlength="100" placeholder="Молоко">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-accent w-100">Добавить</button>
        </div>
    </form>
</div>
<?php admin_collapse_end(); ?>

<?php admin_collapse_start('allergy-list', 'Справочник аллергенов', false, (string)count($allergies)); ?>
<?php admin_render_table_search('Поиск по названию...'); ?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 admin-table">
        <thead class="table-light">
            <tr><th>Название</th><th>Детей</th><th class="text-end">Действия</th></tr>
        </thead>
        <tbody>
            <?php foreach ($allergies as $a):
                $used = count_children_with_allergy($pdo, (int)$a['id']);
            ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars($a['name']) ?></td>
                <td><?= $used ? '<span class="badge bg-soft-orange text-dark">' . $used . '</span>' : '—' ?></td>
                <td class="text-end">
                    <?php if ($used === 0): ?>
                    <form action="delete_allergy.php" method="POST" class="d-inline"
                          onsubmit="return confirm('Удалить «<?= htmlspecialchars($a['name'], ENT_QUOTES) ?>»?')">
                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted small">Используется</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($allergies) === 0): ?>
            <tr><td colspan="3" class="text-center text-muted py-4">Справочник пуст</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php admin_render_table_search_end(); admin_collapse_end(); admin_page_end(); ?>
