<?php

require_once '_auth.php';

require_once '_allergies.php';

require_once '_child_health_panel.php';

require_once '_parent_helpers.php';



$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM children WHERE id = ?');

$stmt->execute([$id]);

$child = $stmt->fetch(PDO::FETCH_ASSOC);



if (!$child) {

    admin_flash('error', 'Ребёнок не найден');

    header('Location: children.php');

    exit;

}



$allergies = admin_get_allergies($pdo);

$selected_allergies = admin_get_child_allergy_ids($pdo, $id);



admin_page_start('Редактировать ребёнка', $child['full_name']);

?>



<div class="row justify-content-center">

    <div class="col-lg-10">

        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <form action="update_child.php" method="POST">

                    <input type="hidden" name="id" value="<?= (int)$child['id'] ?>">

                    <h2 class="h6 fw-semibold text-muted mb-3">Основные данные</h2>

                    <div class="row">

                        <div class="col-md-8 mb-3">

                            <label class="form-label fw-semibold">ФИО <span class="text-danger">*</span></label>

                            <input type="text" name="full_name" class="form-control" required

                                   value="<?= htmlspecialchars($child['full_name']) ?>">

                        </div>

                        <div class="col-md-4 mb-3">

                            <label class="form-label fw-semibold">Дата рождения <span class="text-danger">*</span></label>

                            <input type="date" name="date_of_birth" class="form-control" required

                                   value="<?= htmlspecialchars($child['date_of_birth']) ?>">

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label fw-semibold">Группа <span class="text-danger">*</span></label>

                            <select name="group_id" class="form-select-groups" required>

                                <?php admin_render_group_select_options($pdo, (int)$child['group_id']); ?>

                            </select>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label fw-semibold">Родитель <span class="text-danger">*</span></label>

                            <?php admin_render_parent_select($pdo, (int)$child['parent_id'], false); ?>

                        </div>

                    </div>



                    <?php admin_render_child_health_form_block(

                        $allergies,

                        $selected_allergies,

                        (bool)$child['has_tnr']

                    ); ?>



                    <button type="submit" class="btn btn-accent">Сохранить</button>

                    <a href="children.php" class="btn btn-outline-secondary">Отмена</a>

                </form>



                <div id="child-docs">

                    <?php admin_render_child_health_documents_block($pdo, $id); ?>

                </div>

            </div>

        </div>

    </div>

</div>



<?php admin_page_end(); ?>


