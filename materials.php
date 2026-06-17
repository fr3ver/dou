<?php



require_once '../includes/config.php';

require_once '../includes/lk_helpers.php';

require_once '../includes/lk_layout.php';

require_once '../includes/staff_materials.php';

require_once '../includes/ktp.php';



lk_require_role(2, 3, 4);



$methodicalFiles = staff_materials_documents($pdo, 'methodical');

$ktpGrouped = ktp_documents_grouped($pdo, ktp_documents($pdo));



lk_page_start(

    'Педагогам и сотрудникам',

    'Методические материалы и календарно-тематическое планирование',

    [],

    '..',

    'Личный кабинет',

    'materials'

);

?>



<div class="lk-block">

    <div class="lk-panel staff-materials-page mb-4">

        <h2 class="h5 fw-semibold mb-3 staff-materials-section-title">Методические материалы</h2>

        <?php staff_materials_render_file_list($methodicalFiles, '../'); ?>

    </div>



    <div class="lk-panel ktp-page">

        <h2 class="h5 fw-semibold mb-2">Календарно-тематическое планирование</h2>

        <p class="text-muted small mb-4"><?= htmlspecialchars(ktp_intro_text()) ?></p>

        <?php ktp_render_grid($ktpGrouped, '../'); ?>

    </div>

</div>



<?php lk_page_end(false, '..'); ?>


