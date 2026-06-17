<?php

require_once '_auth.php';

require_once '_news_helpers.php';

require_once '_news_form.php';



admin_page_start('Новая новость');

?>

<div class="row justify-content-center"><div class="col-lg-9">

<div class="card border-0 shadow-sm"><div class="card-body p-4">

<form action="save_news.php" method="POST" enctype="multipart/form-data">

<?php admin_news_form_fields(); ?>

<button type="submit" class="btn btn-accent">Опубликовать</button>

<a href="news.php" class="btn btn-outline-secondary">Отмена</a>

</form>

</div></div></div></div>

<?php admin_page_end(); ?>

