<?php
require_once '_auth.php';
require_once '_menu_form.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM menu WHERE id = ? AND date IS NULL');
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    admin_flash('error', 'Не найдено');
    header('Location: menu.php');
    exit;
}

admin_page_start('Редактировать блюдо');
?>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<form action="update_menu.php" method="POST">
<input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
<?php admin_menu_form_fields($item); ?>
<button type="submit" class="btn btn-accent">Сохранить</button>
<a href="menu.php?group_id=<?= (int)$item['group_id'] ?>" class="btn btn-outline-secondary">Отмена</a>
</form>
</div></div></div></div>
<?php admin_page_end(); ?>
