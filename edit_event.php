<?php
require_once '_auth.php';
require_once '_event_form.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) { admin_flash('error', 'Не найдено'); header('Location: events.php'); exit; }

admin_page_start('Редактировать мероприятие', $event['title']);
?>
<div class="row justify-content-center"><div class="col-lg-9">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<form action="update_event.php" method="POST">
<input type="hidden" name="id" value="<?= (int)$event['id'] ?>">
<?php admin_event_form_fields($event); ?>
<button type="submit" class="btn btn-accent">Сохранить</button>
<a href="events.php" class="btn btn-outline-secondary">Отмена</a>
</form>
</div></div></div></div>
<?php admin_page_end(); ?>
