<?php

require_once '_auth.php';



$filter = $_GET['filter'] ?? 'pending';

$sql = "

    SELECT r.*, u.full_name AS parent_name,

           m.full_name AS moderator_name

    FROM reviews r

    JOIN users u ON r.parent_id = u.id

    LEFT JOIN users m ON r.moderated_by = m.id

";

if ($filter === 'pending') {

    $sql .= " WHERE r.status = 'pending'";

} elseif ($filter === 'approved') {

    $sql .= " WHERE r.status = 'approved'";

} elseif ($filter === 'rejected') {

    $sql .= " WHERE r.status = 'rejected'";

}

$sql .= ' ORDER BY r.created_at DESC';



$reviews = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$pending_count = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();



$statusLabels = [

    'pending'  => ['На модерации', 'bg-warning text-dark'],

    'approved' => ['Одобрен', 'bg-soft-green text-dark'],

    'rejected' => ['Отклонён', 'bg-secondary'],

];



admin_page_start('Модерация отзывов', 'Одобрение и отклонение отзывов родителей');
?>

<ul class="nav nav-pills mb-4 gap-2 flex-wrap">
    <li class="nav-item">
        <a class="nav-link <?= $filter === 'pending' ? 'active' : '' ?>" href="reviews.php?filter=pending">
            На модерации <?php if ($pending_count): ?><span class="badge bg-danger ms-1"><?= $pending_count ?></span><?php endif; ?>
        </a>
    </li>
    <li class="nav-item"><a class="nav-link <?= $filter === 'approved' ? 'active' : '' ?>" href="reviews.php?filter=approved">Одобренные</a></li>
    <li class="nav-item"><a class="nav-link <?= $filter === 'rejected' ? 'active' : '' ?>" href="reviews.php?filter=rejected">Отклонённые</a></li>
    <li class="nav-item"><a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>" href="reviews.php?filter=all">Все</a></li>
</ul>

<?php
admin_collapse_start('reviews-list', 'Список отзывов', false, (string)count($reviews), null, false);
if (count($reviews) === 0):
?>
    <div class="text-center text-muted py-5">Отзывов нет</div>
<?php else: ?>
    <div class="row g-3 p-3">
        <?php foreach ($reviews as $r):

            $st = $statusLabels[$r['status']] ?? ['—', 'bg-light'];

        ?>

        <div class="col-12">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">

                        <div>

                            <strong><?= htmlspecialchars($r['parent_name']) ?></strong>

                            <span class="text-muted small ms-2"><?= date('d.m.Y H:i', strtotime($r['created_at'])) ?></span>

                            <span class="badge <?= $st[1] ?> ms-2"><?= $st[0] ?></span>

                        </div>

                        <div>

                            <?php for ($i = 1; $i <= 5; $i++): ?>

                                <i class="bi bi-star<?= $i <= $r['rating'] ? '-fill text-warning' : ' text-muted' ?>"></i>

                            <?php endfor; ?>

                        </div>

                    </div>

                    <p class="mb-2"><?= nl2br(htmlspecialchars($r['text'])) ?></p>

                    <?php if (!empty($r['moderated_at'])): ?>

                        <p class="small text-muted mb-3">

                            Модерация: <?= date('d.m.Y H:i', strtotime($r['moderated_at'])) ?>

                            <?php if (!empty($r['moderator_name'])): ?>

                                — <?= htmlspecialchars($r['moderator_name']) ?>

                            <?php endif; ?>

                        </p>

                    <?php else: ?>

                        <div class="mb-3"></div>

                    <?php endif; ?>

                    <div class="d-flex gap-2 flex-wrap">

                        <?php if ($r['status'] === 'pending'): ?>

                        <form action="approve_review.php" method="POST">

                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                            <button type="submit" class="btn btn-sm btn-primary-dou"><i class="bi bi-check-lg me-1"></i>Одобрить</button>

                        </form>

                        <form action="reject_review.php" method="POST" onsubmit="return confirm('Отклонить отзыв?')">

                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Отклонить</button>

                        </form>

                        <?php elseif ($r['status'] === 'rejected'): ?>

                        <form action="approve_review.php" method="POST">

                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-check-lg me-1"></i>Одобрить</button>

                        </form>

                        <?php elseif ($r['status'] === 'approved'): ?>

                        <form action="reject_review.php" method="POST" onsubmit="return confirm('Отклонить одобренный отзыв?')">

                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Отклонить</button>

                        </form>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

        <?php endforeach; ?>

    </div>
<?php endif; admin_collapse_end(false); admin_page_end(); ?>
