<?php
require_once '_auth.php';
require_once __DIR__ . '/../includes/club_enrollment.php';

$filter = $_GET['filter'] ?? 'pending';
$applications = admin_club_applications_list($pdo, $filter);
$pending_count = admin_club_applications_pending_count($pdo);

admin_page_start('Заявки в кружки', 'Подтверждение записи детей по заявкам родителей');
?>

<a href="clubs.php" class="admin-back-link d-inline-flex mb-3">
    <i class="bi bi-arrow-left"></i> К списку кружков
</a>

<ul class="nav nav-pills mb-4 gap-2 flex-wrap">
    <li class="nav-item">
        <a class="nav-link <?= $filter === 'pending' ? 'active' : '' ?>" href="club_applications.php?filter=pending">
            На рассмотрении
            <?php if ($pending_count): ?>
                <span class="badge bg-danger ms-1"><?= $pending_count ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filter === 'approved' ? 'active' : '' ?>" href="club_applications.php?filter=approved">Одобренные</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filter === 'rejected' ? 'active' : '' ?>" href="club_applications.php?filter=rejected">Отклонённые</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>" href="club_applications.php?filter=all">Все</a>
    </li>
</ul>

<?php
admin_collapse_start('club-apps-list', 'Список заявок', false, (string) count($applications), null, false);
if ($applications === []):
?>
    <div class="text-center text-muted py-5">
        <?= $filter === 'pending' ? 'Новых заявок нет' : 'Заявок нет' ?>
    </div>
<?php else: ?>
    <div class="row g-3 p-3">
        <?php foreach ($applications as $app):
            $stLabel = club_member_status_label($app['status']);
            $stClass = club_member_status_class($app['status']);
            $seats = admin_club_seats_label(
                (int) $app['member_count'],
                $app['max_participants'] !== null ? (int) $app['max_participants'] : null
            );
            $ageWarning = club_enrollment_age_warning($app['date_of_birth'] ?? null, $app['age_category'] ?? null);
            $approveConfirm = $ageWarning && !$ageWarning['fits']
                ? $ageWarning['message'] . ' Одобрить запись?'
                : 'Одобрить заявку и записать ребёнка в кружок?';
        ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <div>
                            <strong><?= htmlspecialchars($app['club_name']) ?></strong>
                            <span class="badge <?= $stClass ?> ms-2"><?= htmlspecialchars($stLabel) ?></span>
                        </div>
                        <span class="text-muted small"><?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></span>
                    </div>
                    <ul class="list-unstyled small mb-3">
                        <li class="mb-1">
                            <i class="bi bi-person me-1 text-muted"></i>
                            <strong><?= htmlspecialchars($app['child_name']) ?></strong>
                            <?php if (!empty($app['group_name'])): ?>
                                <span class="text-muted">· <?= htmlspecialchars($app['group_name']) ?></span>
                            <?php endif; ?>
                        </li>
                        <li class="mb-1">
                            <i class="bi bi-person-lines-fill me-1 text-muted"></i>
                            <?= htmlspecialchars($app['parent_name']) ?>
                            <?php if (!empty($app['parent_phone'])): ?>
                                · <?= htmlspecialchars($app['parent_phone']) ?>
                            <?php endif; ?>
                        </li>
                        <?php if (!empty($app['schedule'])): ?>
                            <li class="mb-1">
                                <i class="bi bi-clock me-1 text-muted"></i><?= htmlspecialchars($app['schedule']) ?>
                            </li>
                        <?php endif; ?>
                        <li>
                            <i class="bi bi-people me-1 text-muted"></i>Мест в кружке: <?= htmlspecialchars($seats) ?>
                        </li>
                        <?php if (!empty($app['age_category'])): ?>
                            <li class="mb-1">
                                <i class="bi bi-calendar3 me-1 text-muted"></i>Возраст кружка: <?= htmlspecialchars($app['age_category']) ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($ageWarning): ?>
                            <li>
                                <i class="bi bi-cake2 me-1 text-muted"></i>Возраст ребёнка: <strong><?= htmlspecialchars($ageWarning['age_label']) ?></strong>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <?php if ($ageWarning && !$ageWarning['fits']): ?>
                        <div class="alert alert-warning border-0 py-2 px-3 small mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($ageWarning['message']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($app['moderated_at'])): ?>
                        <p class="small text-muted mb-3">
                            Обработано: <?= date('d.m.Y H:i', strtotime($app['moderated_at'])) ?>
                            <?php if (!empty($app['moderator_name'])): ?>
                                — <?= htmlspecialchars($app['moderator_name']) ?>
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <div class="mb-3"></div>
                    <?php endif; ?>
                    <?php if ($app['status'] === 'pending'): ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <form action="approve_club_application.php" method="POST"
                                  onsubmit="return confirm(<?= htmlspecialchars(json_encode($approveConfirm, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>);">
                                <input type="hidden" name="club_id" value="<?= (int) $app['club_id'] ?>">
                                <input type="hidden" name="child_id" value="<?= (int) $app['child_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-primary-dou">
                                    <i class="bi bi-check-lg me-1"></i>Одобрить и записать
                                </button>
                            </form>
                            <form action="reject_club_application.php" method="POST"
                                  onsubmit="return confirm('Отклонить заявку?')">
                                <input type="hidden" name="club_id" value="<?= (int) $app['club_id'] ?>">
                                <input type="hidden" name="child_id" value="<?= (int) $app['child_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-lg me-1"></i>Отклонить
                                </button>
                            </form>
                            <a href="club_members.php?id=<?= (int) $app['club_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                Состав кружка
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="club_members.php?id=<?= (int) $app['club_id'] ?>" class="btn btn-sm btn-outline-secondary">
                            Состав кружка
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif;
admin_collapse_end(false);
admin_page_end();
