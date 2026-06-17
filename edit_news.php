<?php

require_once '_auth.php';

require_once '_news_helpers.php';

require_once '_news_form.php';



$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');

$stmt->execute([$id]);

$news = $stmt->fetch(PDO::FETCH_ASSOC);



if (!$news) {

    admin_flash('error', 'Новость не найдена');

    header('Location: news.php');

    exit;

}



admin_page_start('Редактировать новость', $news['title']);

?>

<div class="row justify-content-center"><div class="col-lg-9">

<div class="card border-0 shadow-sm"><div class="card-body p-4">

<form action="update_news.php" method="POST" enctype="multipart/form-data">

<input type="hidden" name="id" value="<?= (int)$news['id'] ?>">

<?php admin_news_form_fields($news); ?>

<button type="submit" class="btn btn-accent">Сохранить</button>

<a href="news.php" class="btn btn-outline-secondary">Отмена</a>

</form>

</div></div></div></div>

<?php admin_page_end(); ?>

