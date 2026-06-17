<?php
require_once '_auth.php';
require_once '_club_form.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM clubs WHERE id = ?');
$stmt->execute([$id]);
$club = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$club) {
    admin_flash('error', 'Кружок не найден');
    header('Location: clubs.php');
    exit;
}

admin_page_start('Редактировать кружок', $club['name']);
?>
<div class="row justify-content-center"><div class="col-lg-9">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<form action="update_club.php" method="POST">
<input type="hidden" name="id" value="<?= (int)$club['id'] ?>">
<?php admin_club_form_fields($pdo, $club); ?>
<a href="club_members.php?id=<?= (int)$club['id'] ?>" class="btn btn-outline-secondary me-2">
    <i class="bi bi-people me-1"></i>Состав
</a>
<button type="submit" class="btn btn-accent">Сохранить</button>
<a href="clubs.php" class="btn btn-outline-secondary">Отмена</a>
</form>
</div></div></div></div>
<?php admin_page_end(); ?>
