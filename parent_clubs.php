<?php
/**
 * @var array $clubsEnrolled
 * @var array $clubApplications
 * @var array $clubsOpen
 */
?>
<section id="clubs" class="lk-block scroll-margin-top">
    <?php lk_section_title('palette', 'Кружки'); ?>

    <?php if ($clubsEnrolled !== []): ?>
        <h3 class="h6 fw-semibold text-muted mb-3">Записан</h3>
        <div class="row g-3 mb-4">
            <?php foreach ($clubsEnrolled as $club): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h4 class="h5 fw-semibold mb-0"><?= htmlspecialchars($club['name']) ?></h4>
                            <span class="badge bg-soft-green text-dark">Записан</span>
                        </div>
                        <p class="small mb-1">Ребёнок: <strong><?= htmlspecialchars($club['child_name']) ?></strong></p>
                        <?php if (!empty($club['teacher_name'])): ?>
                            <p class="small mb-1">Руководитель: <?= htmlspecialchars($club['teacher_name']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($club['schedule'])): ?>
                            <p class="small text-muted mb-2"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($club['schedule']) ?></p>
                        <?php endif; ?>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php
                            $parentIdForChat = (int)($_SESSION['user_id'] ?? 0);
                            lk_render_contact_menu(
                                lk_parent_chat_url('club_teacher', $parentIdForChat, (int)$club['id'], '..'),
                                $club['teacher_phone'] ?? null,
                                false,
                                'Написать преподавателю',
                                'Позвонить преподавателю'
                            );
                            lk_render_broadcast_chat_link('club_broadcast', (int)$club['id'], '..', 'Общий чат кружка');
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($clubApplications !== []): ?>
        <h3 class="h6 fw-semibold text-muted mb-3">Мои заявки</h3>
        <div class="row g-3 mb-4">
            <?php foreach ($clubApplications as $app): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h4 class="h5 fw-semibold mb-0"><?= htmlspecialchars($app['club_name']) ?></h4>
                            <span class="badge <?= club_member_status_class($app['status']) ?>">
                                <?= htmlspecialchars(club_member_status_label($app['status'])) ?>
                            </span>
                        </div>
                        <p class="small mb-1">Ребёнок: <strong><?= htmlspecialchars($app['child_name']) ?></strong></p>
                        <p class="text-muted small mb-0">
                            Подана <?= date('d.m.Y H:i', strtotime($app['created_at'])) ?>
                            <?php if ($app['status'] === 'rejected'): ?>
                                · можно подать заявку снова
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($clubsOpen !== []): ?>
        <h3 class="h6 fw-semibold text-muted mb-3">Записаться на кружок</h3>
        <p class="small text-muted mb-3">
            Выберите кружок и ребёнка — заявка уйдёт администратору. После одобрения запись появится в разделе «Записан».
        </p>
        <div class="row g-3">
            <?php foreach ($clubsOpen as $club): ?>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h4 class="h6 fw-semibold mb-2"><?= htmlspecialchars($club['name']) ?></h4>
                        <?php if (!empty($club['age_category'])): ?>
                            <p class="small text-muted mb-1"><?= htmlspecialchars($club['age_category']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($club['schedule'])): ?>
                            <p class="small mb-2"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($club['schedule']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($club['teacher_name'])): ?>
                            <p class="small mb-3">Руководитель: <?= htmlspecialchars($club['teacher_name']) ?></p>
                        <?php endif; ?>
                        <form action="submit_club_application.php" method="POST" class="row g-2 align-items-end">
                            <input type="hidden" name="club_id" value="<?= (int) $club['id'] ?>">
                            <div class="col-sm-8">
                                <label class="form-label small text-muted mb-1">Ребёнок</label>
                                <select name="child_id" class="form-select form-select-sm" required>
                                    <?php foreach ($club['eligible_children'] as $child): ?>
                                        <option value="<?= (int) $child['id'] ?>">
                                            <?= htmlspecialchars($child['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <button type="submit" class="btn btn-accent btn-sm w-100">
                                    <i class="bi bi-send me-1"></i>Подать заявку
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($clubsEnrolled === [] && $clubApplications === []): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-muted py-4">
                Сейчас нет доступных кружков для записи: нет свободных мест или заявка уже подана. Обратитесь в сад при необходимости.
            </div>
        </div>
    <?php endif; ?>
</section>
