<?php
/**
 * Карточка кружка в ЛК (как на публичной странице club.php).
 *
 * @var array  $club
 * @var string $base
 * @var bool   $showBroadcastChat
 */
require_once __DIR__ . '/../public.php';

$base ??= '..';
$showBroadcastChat ??= true;

$clubId = (int)($club['id'] ?? 0);
$icon = public_club_icon($clubId);
$memberCount = (int)($club['member_count'] ?? 0);
$maxParticipants = isset($club['max_participants']) && $club['max_participants'] !== ''
    ? (int)$club['max_participants']
    : null;
$seatsLabel = $maxParticipants !== null && $maxParticipants > 0
    ? $memberCount . ' / ' . $maxParticipants
    : (string)$memberCount;
?>
<div class="lk-club-profile">
    <div class="lk-club-profile-hero">
        <div class="lk-club-profile-icon" aria-hidden="true">
            <i class="bi <?= htmlspecialchars($icon) ?>"></i>
        </div>
        <div class="lk-club-profile-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                <div>
                    <?php if (!empty($club['age_category'])): ?>
                    <span class="news-date-pill mb-2 d-inline-block"><?= htmlspecialchars($club['age_category']) ?></span>
                    <?php endif; ?>
                    <h3 class="h5 fw-semibold mb-1"><?= htmlspecialchars($club['name'] ?? '') ?></h3>
                    <?php if (!empty($club['description'])): ?>
                    <p class="text-muted small mb-0 lk-club-profile-desc"><?= htmlspecialchars($club['description']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <?php if ($showBroadcastChat && $clubId > 0): ?>
                        <?php lk_render_broadcast_chat_link('club_broadcast', $clubId, $base, 'Общий чат'); ?>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($base) ?>/club.php?id=<?= $clubId ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        <span class="ms-1 d-none d-sm-inline">На сайте</span>
                    </a>
                </div>
            </div>

            <div class="row g-2 g-md-3">
                <?php if (!empty($club['schedule'])): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="group-stat-box">
                        <i class="bi bi-clock"></i>
                        <div>
                            <strong><?= htmlspecialchars($club['schedule']) ?></strong>
                            <span>расписание</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="group-stat-box">
                        <i class="bi bi-people"></i>
                        <div>
                            <strong><?= htmlspecialchars($seatsLabel) ?></strong>
                            <span><?= $maxParticipants !== null && $maxParticipants > 0 ? 'мест занято' : 'участников' ?></span>
                        </div>
                    </div>
                </div>
                <?php if (!empty($club['age_category'])): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="group-stat-box">
                        <i class="bi bi-calendar3"></i>
                        <div>
                            <strong><?= htmlspecialchars($club['age_category']) ?></strong>
                            <span>возраст</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($club['activities_features'])): ?>
    <div class="lk-club-profile-section">
        <h4 class="h6 fw-semibold mb-2"><i class="bi bi-stars me-1"></i>Особенности занятий</h4>
        <div class="text-muted small group-detail-text"><?= nl2br(htmlspecialchars($club['activities_features'])) ?></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($club['education_program'])): ?>
    <div class="lk-club-profile-section">
        <h4 class="h6 fw-semibold mb-2"><i class="bi bi-journal-text me-1"></i>Образовательная программа</h4>
        <div class="text-muted small group-detail-text"><?= nl2br(htmlspecialchars($club['education_program'])) ?></div>
    </div>
    <?php endif; ?>
</div>
