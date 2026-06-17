<?php
require_once '_auth.php';
require_once '_menu_form.php';

$defaultGroup = (int)($_GET['group_id'] ?? 0);
$defaultWeekday = (int)($_GET['weekday'] ?? 0);

admin_page_start('Добавить блюдо', 'В утверждённое меню на день недели');
?>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<form action="save_menu.php" method="POST">
<?php admin_menu_form_fields(null, $defaultGroup ?: null, $defaultWeekday ?: null); ?>
<button type="submit" class="btn btn-accent">Добавить</button>
<a href="menu.php<?= $defaultGroup ? '?group_id=' . $defaultGroup : '' ?>" class="btn btn-outline-secondary">Отмена</a>
</form>
</div></div></div></div>
<?php admin_page_end(); ?>
