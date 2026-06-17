<?php
require_once '_auth.php';
require_once '_allergies.php';
require_once '_child_health_panel.php';
require_once '_parent_helpers.php';

$allergies = admin_get_allergies($pdo);
admin_page_start('Добавить ребёнка');
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="save_child.php" method="POST">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold">ФИО ребёнка <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Дата рождения <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Группа <span class="text-danger">*</span></label>
                            <select name="group_id" class="form-select-groups" required>
                                <?php admin_render_group_select_options($pdo, 0, true); ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Родитель <span class="text-danger">*</span></label>
                            <?php admin_render_parent_select($pdo); ?>
                        </div>
                    </div>
                    <?php admin_render_child_health_form_block($allergies, [], false); ?>
                    <p class="small text-muted mb-3">Справки родитель сможет загрузить в личном кабинете после сохранения карточки.</p>
                    <button type="submit" class="btn btn-accent">Добавить ребёнка</button>
                    <a href="children.php" class="btn btn-outline-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php admin_page_end(); ?>
