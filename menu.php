<?php
require_once '../includes/config.php';
require_once '../includes/auth_helpers.php';
require_once '../includes/lk_helpers.php';
require_once '../includes/lk_layout.php';
require_once '../includes/menu_nutrition.php';
require_once '../includes/menu_weekly.php';
require_once '../includes/menu_alternatives.php';
require_once '../includes/org_documents.php';

lk_require_role(1);

$parentId = (int)$_SESSION['user_id'];
$db_error = null;

try {
    $children = menu_children_attach_allergy_ids($pdo, lk_parent_children($pdo, $parentId));
    $groupIds = lk_parent_group_ids($children);
    [$weekFrom, $weekTo] = lk_week_range();
    $menu = lk_menu_for_range($pdo, $weekFrom, $weekTo, $groupIds, true);
    $nutritionMap = menu_daily_nutrition_map($pdo, $groupIds);
    $nutritionDocs = org_documents_for_section($pdo, 'nutrition', true);
} catch (Exception $e) {
    $db_error = $e->getMessage();
    $menu = [];
    $children = [];
    $nutritionMap = [];
    $nutritionDocs = [];
    $weekFrom = $weekTo = date('Y-m-d');
}

lk_page_start(
    'Меню и питание',
    'Типовое меню на пн–пт, показатели БЖУ и документы по организации питания',
    [],
    '..',
    'Личный кабинет родителя',
    'menu'
);
?>

<?php if ($db_error): ?>
    <div class="alert alert-danger border-0 shadow-sm">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($db_error) ?>
    </div>
<?php else: ?>

    <div class="lk-info-banner lk-menu-notice">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <div>
            <p class="mb-2 mb-md-1"><strong>Обратите внимание.</strong> <?= htmlspecialchars(menu_weekly_disclaimer_text()) ?></p>
            <p class="mb-0 text-muted"><?= htmlspecialchars(menu_chef_notice_text()) ?></p>
        </div>
    </div>

    <section class="lk-block">
        <?php lk_section_title('cup-hot', 'Меню на неделю', date('d.m', strtotime($weekFrom)) . ' — ' . date('d.m.Y', strtotime($weekTo))); ?>
        <p class="lk-section-subtitle mb-3">Меню задано по дням недели (пн–пт). Выберите день в списке — показаны блюда на соответствующий день недели.</p>
        <?php
        $emptyMessage = 'Меню для группы вашего ребёнка ещё не настроено. Обратитесь к администратору.';
        require __DIR__ . '/../includes/partials/lk_menu_week.php';
        ?>
    </section>

    <section class="lk-block">
        <?php lk_section_title('file-earmark-medical', 'Документы по питанию', 'СанПиН, локальные акты и иные материалы'); ?>
        <?php if ($nutritionDocs === []): ?>
            <div class="lk-panel text-muted">
                Документы по организации питания будут опубликованы администрацией.
            </div>
        <?php else: ?>
            <div class="lk-panel">
                <div class="lk-nutrition-docs">
                    <?php foreach ($nutritionDocs as $doc): ?>
                        <?php org_document_render_file_bar($doc, '../'); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

<?php endif; ?>

<?php lk_page_end($menu !== [], '..'); ?>
