<?php

/** @var PDO $pdo */

/** @var array $clubs */

require_once __DIR__ . '/../../includes/club_attendance.php';

?>

<div id="employee-clubs" class="lk-block scroll-margin-top mb-5">

    <?php lk_section_title('palette', 'Мои кружки'); ?>



    <?php foreach ($clubs as $club): ?>

        <?php

        $clubId = (int)$club['id'];

        $members = lk_club_members($pdo, $clubId);

        $clubSummary = lk_club_attendance_summary($pdo, $clubId, 5);

        ?>

        <div class="card border-0 shadow-sm mb-4 overflow-hidden" data-club-card="<?= $clubId ?>">

            <?php
            $showBroadcastChat = true;
            require __DIR__ . '/../../includes/partials/lk_club_profile.php';
            ?>

            <div class="card-body border-top">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

                    <h4 class="h6 fw-semibold mb-0">Посещаемость · <?= date('d.m.Y') ?></h4>

                </div>



                <?php if ($members === []): ?>

                    <p class="text-muted mb-0">Пока никто не записан.</p>

                <?php else: ?>

                    <div class="d-none d-xl-block">

                        <div class="table-responsive">

                            <table class="table table-sm align-middle mb-0 admin-table">

                                <thead class="table-light">

                                    <tr>

                                        <th>Ребёнок</th>

                                        <th>Группа</th>

                                        <th>Родитель</th>

                                        <th>Аллергены</th>

                                        <th>Сегодня</th>

                                        <th></th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($members as $m): ?>

                                    <tr data-club-child-row="<?= $clubId ?>-<?= (int)$m['id'] ?>">

                                        <td class="fw-semibold"><?= htmlspecialchars($m['full_name']) ?></td>

                                        <td><?= htmlspecialchars($m['group_name'] ?? '—') ?></td>

                                        <td class="small">

                                            <?= htmlspecialchars($m['parent_name'] ?? '—') ?>

                                        </td>

                                        <td class="small"><?= !empty($m['allergy_names']) ? htmlspecialchars($m['allergy_names']) : '—' ?></td>

                                        <td class="club-attendance-status"><?= lk_attendance_badge($m['today_status'] ?? null) ?></td>

                                        <td>

                                            <div class="d-flex gap-1 flex-wrap justify-content-end">

                                                <?php if (!empty($m['parent_id'])): ?>

                                                    <?php lk_render_parent_contact_menu(
                                                        'club_teacher',
                                                        (int)$m['parent_id'],
                                                        $clubId,
                                                        $m['parent_phone'] ?? null,
                                                        '..',
                                                        true
                                                    ); ?>

                                                <?php endif; ?>

                                                <button type="button" class="btn btn-sm btn-primary-dou mark-club-attendance"

                                                        data-club="<?= $clubId ?>" data-child="<?= (int)$m['id'] ?>" data-status="present">

                                                    <i class="bi bi-check-lg"></i>

                                                </button>

                                                <button type="button" class="btn btn-sm btn-outline-secondary mark-club-attendance"

                                                        data-club="<?= $clubId ?>" data-child="<?= (int)$m['id'] ?>" data-status="absent">

                                                    <i class="bi bi-x-lg"></i>

                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>



                    <div class="d-xl-none row g-3">

                        <?php foreach ($members as $m): ?>

                        <div class="col-12" data-club-child-row="<?= $clubId ?>-<?= (int)$m['id'] ?>">

                            <article class="lk-child-card">

                                <div class="lk-child-card-body">

                                    <h5 class="fw-semibold mb-1"><?= htmlspecialchars($m['full_name']) ?></h5>

                                    <p class="small text-muted mb-1"><?= htmlspecialchars($m['group_name'] ?? '—') ?></p>

                                    <p class="small mb-2 club-attendance-status"><?= lk_attendance_badge($m['today_status'] ?? null) ?></p>

                                    <div class="d-flex flex-wrap gap-2">

                                        <?php if (!empty($m['parent_id'])): ?>

                                            <?php lk_render_parent_contact_menu(
                                                'club_teacher',
                                                (int)$m['parent_id'],
                                                $clubId,
                                                $m['parent_phone'] ?? null
                                            ); ?>

                                        <?php endif; ?>

                                        <button type="button" class="btn btn-sm btn-primary-dou flex-fill mark-club-attendance"

                                                data-club="<?= $clubId ?>" data-child="<?= (int)$m['id'] ?>" data-status="present">

                                            <i class="bi bi-check-lg me-1"></i>Пришёл

                                        </button>

                                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill mark-club-attendance"

                                                data-club="<?= $clubId ?>" data-child="<?= (int)$m['id'] ?>" data-status="absent">

                                            <i class="bi bi-x-lg me-1"></i>Нет

                                        </button>

                                    </div>

                                </div>

                            </article>

                        </div>

                        <?php endforeach; ?>

                    </div>



                    <?php if ($clubSummary !== []): ?>

                    <div class="mt-3 pt-3 border-top">

                        <div class="small fw-semibold mb-2">За последние дни</div>

                        <div class="table-responsive">

                            <table class="table table-sm align-middle mb-0">

                                <thead class="table-light">

                                    <tr><th>Дата</th><th>Пришли</th><th>Отсутствуют</th></tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($clubSummary as $row): ?>

                                    <tr>

                                        <td><?= date('d.m.Y', strtotime($row['date'])) ?></td>

                                        <td class="text-success fw-semibold"><?= (int)$row['present_count'] ?></td>

                                        <td class="text-muted"><?= (int)$row['absent_count'] ?></td>

                                    </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

        </div>

    <?php endforeach; ?>

</div>


