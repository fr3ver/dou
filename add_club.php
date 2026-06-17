<?php
require_once '_auth.php';
require_once '_club_form.php';

admin_page_start('Новый кружок');
?>
<div class="row justify-content-center"><div class="col-lg-9">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<form action="save_club.php" method="POST">
<?php admin_club_form_fields($pdo); ?>
<button type="submit" class="btn btn-accent">Создать</button>
<a href="clubs.php" class="btn btn-outline-secondary">Отмена</a>
</form>
</div></div></div></div>
<?php admin_page_end(); ?>
