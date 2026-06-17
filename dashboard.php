<?php
require_once '../includes/config.php';
require_once '../includes/auth_helpers.php';
require_once '../includes/lk_helpers.php';
require_once '../includes/lk_layout.php';
require_once '../includes/public.php';
require_once '../includes/club_enrollment.php';
require_once '../includes/org_documents.php';

lk_require_role(1);

$parentId = (int)$_SESSION['user_id'];
$db_error = null;

try {
    $children = lk_parent_children($pdo, $parentId);
    $groupIds = lk_parent_group_ids($children);
    [$weekFrom, $weekTo] = lk_week_range();

    $news = lk_news_for_audience($pdo, ['all', 'parent'], $groupIds, 8, true);
    $events = lk_events_for_groups($pdo, $groupIds, 6, true);
    $clubsEnrolled = lk_parent_clubs($pdo, $parentId);
    $clubApplications = lk_parent_club_applications($pdo, $parentId);
    $clubsOpen = lk_clubs_open_for_parent($pdo, $parentId, $children);
    $reviews = lk_parent_reviews($pdo, $parentId);
    $childIds = array_map(fn($c) => (int)$c['id'], $children);
    $attendanceMap = lk_children_attendance_map($pdo, $childIds, 14);
} catch (Exception $e) {
    $db_error = $e->getMessage();
    $children = $news = $events = $clubsEnrolled = $clubApplications = $clubsOpen = $reviews = [];
    $attendanceMap = [];
    $weekFrom = $weekTo = date('Y-m-d');
}

$presentToday = count(array_filter($children, fn($c) => ($c['today_status'] ?? '') === 'present'));

$badges = [];
if (count($children) > 0) {
    $badges[] = ['text' => count($children) . ' ' . (count($children) === 1 ? 'ребёнок' : 'детей'), 'class' => 'badge-news'];
}

$heroStats = [
    ['icon' => 'emoji-smile', 'value' => count($children), 'label' => 'Мои дети', 'tone' => 'green'],
    ['icon' => 'check-circle', 'value' => $presentToday, 'label' => 'Пришли сегодня', 'tone' => 'blue'],
    ['icon' => 'stars', 'value' => count($clubsEnrolled), 'label' => 'Кружков', 'tone' => 'orange'],
];

lk_page_start(
    'Кабинет родителя',
    'Здравствуйте, <strong>' . htmlspecialchars($_SESSION['full_name']) . '</strong>',
    $badges,
    '..',
    'Личный кабинет родителя',
    'dashboard',
    $heroStats
);
?>

<?php if ($db_error): ?>
    <div class="alert alert-danger border-0 shadow-sm">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($db_error) ?>
    </div>
<?php else: ?>

    <section id="children" class="lk-block scroll-margin-top">
        <?php lk_section_title('people', 'Мои дети', 'Группа, воспитатель и посещаемость на сегодня'); ?>

        <?php if ($children === []): ?>
            <div class="lk-panel">
                <div class="lk-empty-state">
                    <i class="bi bi-emoji-smile d-block"></i>
                    Дети не привязаны к вашей учётной записи. Обратитесь к администратору.
                </div>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($children as $child): ?>
                <div class="col-md-6">
                    <article class="lk-child-card<?= $child['has_tnr'] ? ' lk-child-card--tnr' : '' ?>">
                        <div class="lk-child-card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <h3 class="h6 fw-semibold mb-0"><?= htmlspecialchars($child['full_name']) ?></h3>
                                <?php if ($child['has_tnr']): ?>
                                    <span class="badge badge-tnr">ТНР</span>
                                <?php endif; ?>
                            </div>
                            <p class="lk-child-meta mb-1">
                                <i class="bi bi-calendar3"></i>
                                <span><?= date('d.m.Y', strtotime($child['date_of_birth'])) ?></span>
                            </p>
                            <p class="lk-child-meta mb-1">
                                <i class="bi bi-collection"></i>
                                <span>
                                    <?= htmlspecialchars($child['group_name'] ?? '—') ?>
                                    <?php if (!empty($child['age_category'])): ?>
                                        <span class="text-muted d-block"><?= htmlspecialchars(dou_age_display($child['age_category'])) ?></span>
                                    <?php endif; ?>
                                </span>
                            </p>
                            <?php if (!empty($child['teacher_name'])): ?>
                            <p class="lk-child-meta mb-1">
                                <i class="bi bi-person-badge"></i>
                                <span><?= htmlspecialchars($child['teacher_name']) ?></span>
                            </p>
                            <?php if (!empty($child['group_id'])): ?>
                            <div class="mb-2">
                                <?php lk_render_contact_menu(
                                    lk_parent_chat_url('teacher', $parentId, (int)$child['group_id']),
                                    $child['teacher_phone'] ?? null,
                                    false,
                                    'Написать воспитателю',
                                    'Позвонить воспитателю'
                                ); ?>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($child['allergy_names'])): ?>
                                <p class="small mb-2 text-allergy"><i class="bi bi-shield-exclamation me-1"></i><?= htmlspecialchars($child['allergy_names']) ?></p>
                            <?php endif; ?>
                            <p class="small mb-2">
                                Сегодня: <?= lk_attendance_badge($child['today_status'] ?? null) ?>
                                <?php if (!empty($child['today_arrival']) || !empty($child['today_departure'])): ?>
                                    <span class="text-muted d-block mt-1">
                                        <?= lk_format_time($child['today_arrival'] ?? null) ?>
                                        →
                                        <?= lk_format_time($child['today_departure'] ?? null) ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                            <a href="#attendance" class="small link-more">Подробная посещаемость</a>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php require __DIR__ . '/../includes/partials/parent_attendance.php'; ?>

    <?php require __DIR__ . '/../includes/partials/parent_svedeniya_block.php'; ?>

    <section id="news" class="lk-block scroll-margin-top">
    <?php lk_section_title('newspaper', 'Новости', 'Объявления для родителей ваших групп'); ?>
    <?php if ($news === []): ?>
        <div class="lk-panel text-muted">Новостей пока нет.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($news as $item): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm card-hover h-100">
                    <div class="card-body">
                        <div class="news-date-pill mb-2"><?= date('d.m.Y', strtotime($item['publish_date'])) ?></div>
                        <h5 class="fw-semibold"><?= htmlspecialchars($item['title']) ?></h5>
                        <?php
                        $newsGroupsLabel = entity_groups_format_news_groups($item, '');
                        if ($newsGroupsLabel !== ''): ?>
                            <span class="badge badge-news mb-2"><?= htmlspecialchars($newsGroupsLabel) ?></span>
                        <?php endif; ?>
                        <p class="text-muted small mb-0"><?= htmlspecialchars(lk_excerpt($item['content'])) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    </section>

    <section id="events" class="lk-block scroll-margin-top">
    <?php lk_section_title('calendar-event', 'Мероприятия', 'Только для групп ваших детей'); ?>
    <?php if ($events === []): ?>
        <div class="lk-panel text-muted">Ближайших мероприятий нет.</div>
    <?php else: ?>
        <div class="lk-panel p-0 overflow-hidden">
            <div class="list-group list-group-flush">
                <?php foreach ($events as $event): ?>
                <div class="list-group-item py-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <strong><?= htmlspecialchars($event['title']) ?></strong>
                        <?php
                        $eventGroupsLabel = empty($event['for_all_groups'])
                            ? entity_groups_format_event_groups($event, '')
                            : '';
                        if ($eventGroupsLabel !== ''): ?>
                                <span class="badge badge-news ms-1"><?= htmlspecialchars($eventGroupsLabel) ?></span>
                        <?php endif; ?>
                            <?php if ($event['status'] === 'postponed'): ?>
                                <span class="badge bg-soft-yellow text-dark ms-1">Перенесено</span>
                            <?php endif; ?>
                        </div>
                        <span class="text-muted small">
                            <?= public_event_display_date($event) ?>
                            <?php if (!empty($event['event_time'])): ?>
                                · <?= date('H:i', strtotime($event['event_time'])) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (!empty($event['location'])): ?>
                        <div class="small text-muted mt-1"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($event['location']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($event['description'])): ?>
                        <p class="small text-muted mb-0 mt-2"><?= htmlspecialchars(lk_excerpt($event['description'], 200)) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    </section>

    <?php
    require __DIR__ . '/../includes/partials/parent_clubs.php';
    ?>

    <section id="reviews" class="lk-block scroll-margin-top">
    <?php lk_section_title('chat-heart', 'Отзыв о детском саде'); ?>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="submit_review.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Оценка</label>
                            <select name="rating" class="form-select" required>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?= $i ?>"><?= $i ?> ★</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Текст отзыва</label>
                            <textarea name="text" class="form-control" rows="4" required maxlength="2000"
                                      placeholder="Поделитесь впечатлениями…"></textarea>
                        </div>
                        <button type="submit" class="btn btn-accent">Отправить на модерацию</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <?php if ($reviews === []): ?>
                <div class="card border-0 shadow-sm"><div class="card-body text-muted py-4">Вы ещё не оставляли отзывов.</div></div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="list-group list-group-flush">
                        <?php foreach ($reviews as $review): ?>
                        <div class="list-group-item py-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <span class="text-warning"><?= str_repeat('★', (int)$review['rating']) ?></span>
                                <span class="badge <?= lk_review_status_class($review['status']) ?>">
                                    <?= lk_review_status_label($review['status']) ?>
                                </span>
                            </div>
                            <p class="mb-1"><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                            <span class="text-muted small"><?= date('d.m.Y H:i', strtotime($review['created_at'])) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </section>

<?php endif; ?>

<?php lk_page_end(false, '..'); ?>
