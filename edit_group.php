<?php
require_once '_auth.php';
require_once '_group_form.php';

$id = (int) ($_GET['id'] ?? 0);
$group = admin_group_find($pdo, $id);

if (!$group) {
    admin_flash('error', 'Группа не найдена');
    header('Location: groups.php');
    exit;
}

admin_page_start('Редактировать группу', $group['name']);
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="update_group.php" method="POST">
                    <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
                    <?php admin_group_form_fields($pdo, $group); ?>
                    <a href="group_children.php?id=<?= (int) $group['id'] ?>" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-people me-1"></i>Состав
                    </a>
                    <button type="submit" class="btn btn-accent">Сохранить</button>
                    <a href="groups.php" class="btn btn-outline-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php admin_page_end(); ?>
