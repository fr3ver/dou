<?php
/** @var PDO $pdo */
/** @var array $news */
/** @var array $events */
/** @var array $menuWeek */
/** @var string $weekFrom */
/** @var string $weekTo */
/** @var array|null $employee */
?>
<div id="employee-feed" class="lk-block">
    <?php if ($news !== []): ?>
        <?php lk_section_title('newspaper', 'Новости группы'); ?>
        <div class="row g-3 mb-5">
            <?php foreach ($news as $item): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="news-date-pill mb-2"><?= date('d.m.Y', strtotime($item['publish_date'])) ?></div>
                        <h5 class="fw-semibold"><?= htmlspecialchars($item['title']) ?></h5>
                        <p class="text-muted small mb-0"><?= htmlspecialchars(lk_excerpt($item['content'])) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($events !== []): ?>
        <?php lk_section_title('calendar-event', 'Мероприятия группы'); ?>
        <div class="card border-0 shadow-sm mb-5">
            <div class="list-group list-group-flush">
                <?php foreach ($events as $event): ?>
                <div class="list-group-item py-3">
                    <strong><?= htmlspecialchars($event['title']) ?></strong>
                    <span class="text-muted small ms-2"><?= public_event_display_date($event) ?></span>
                    <?php if (!empty($event['description'])): ?>
                        <p class="small text-muted mb-0 mt-1"><?= htmlspecialchars(lk_excerpt($event['description'], 160)) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div id="menu" class="lk-block scroll-margin-top">
        <?php
        require_once __DIR__ . '/../../includes/menu_weekly.php';
        require_once __DIR__ . '/../../includes/menu_alternatives.php';
        lk_section_title('cup-hot', 'Меню на неделю (утверждённое)', date('d.m', strtotime($weekFrom)) . ' — ' . date('d.m.Y', strtotime($weekTo)));
        ?>
        <p class="text-muted small mb-3"><?= htmlspecialchars(menu_weekly_disclaimer_text()) ?></p>
        <?php
        $menu = $menuWeek;
        $menuChildren = menu_children_attach_allergy_ids($pdo, $children ?? []);
        $nutritionMap = [];
        if (!empty($employee['group_id'])) {
            require_once __DIR__ . '/../../includes/menu_nutrition.php';
            $nutritionMap = menu_daily_nutrition_map($pdo, [(int)$employee['group_id']]);
        }
        $emptyMessage = 'Меню для вашей группы ещё не настроено. Обратитесь к администратору.';
        $children = $menuChildren;
        require __DIR__ . '/../../includes/partials/lk_menu_week.php';
        ?>
    </div>
</div>
