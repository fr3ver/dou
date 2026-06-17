<?php
require_once '_auth.php';
require_once '_group_form.php';

admin_page_start('Новая группа');
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="save_group.php" method="POST">
                    <?php admin_group_form_fields($pdo); ?>
                    <button type="submit" class="btn btn-accent">Создать группу</button>
                    <a href="groups.php" class="btn btn-outline-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php admin_page_end(); ?>
