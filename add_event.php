<?php
require_once '_auth.php';
require_once '_event_form.php';
admin_page_start('Новое мероприятие');
?>
<div class="row justify-content-center"><div class="col-lg-9">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<form action="save_event.php" method="POST">
<?php admin_event_form_fields(); ?>
<button type="submit" class="btn btn-accent">Создать</button>
<a href="events.php" class="btn btn-outline-secondary">Отмена</a>
</form>
</div></div></div></div>
<?php admin_page_end(); ?>
