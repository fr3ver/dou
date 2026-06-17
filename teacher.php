<?php

/** @var array $children */

/** @var array $employee */

$total_children = count($children);

?>

<div id="employee-teacher" class="lk-block scroll-margin-top">

    <?php lk_section_title('clipboard-check', 'Посещаемость группы', date('d.m.Y')); ?>



    <?php if ($total_children === 0): ?>

        <div class="card border-0 shadow-sm mb-5">

            <div class="card-body text-center text-muted py-5">В вашей группе пока нет детей.</div>

        </div>

    <?php else: ?>

        <div class="d-none d-xl-block">

            <div class="lk-panel p-0 overflow-hidden">

                <div class="lk-table-wrap">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th>ФИО</th><th>Родитель</th><th>ТНР</th><th>Аллергены</th><th>Сегодня</th><th>Пришёл / ушёл</th><th></th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($children as $child): ?>

                            <tr data-child-row="<?= (int)$child['id'] ?>">

                                <td class="fw-semibold"><?= htmlspecialchars($child['full_name']) ?></td>

                                <td class="small">

                                    <?= htmlspecialchars($child['parent_name'] ?? '—') ?>

                                </td>

                                <td><?= $child['has_tnr'] ? '<span class="badge badge-tnr">ТНР</span>' : '<span class="text-muted">—</span>' ?></td>

                                <td class="small"><?= !empty($child['allergy_names']) ? '<span class="text-allergy">' . htmlspecialchars($child['allergy_names']) . '</span>' : '<span class="text-muted">—</span>' ?></td>

                                <td class="attendance-status"><?= lk_attendance_badge($child['today_status'] ?? null) ?></td>

                                <td class="attendance-times small text-muted">

                                    <?= lk_attendance_times_html($child['today_arrival'] ?? null, $child['today_departure'] ?? null) ?: '—' ?>

                                </td>

                                <td>

                                    <div class="d-flex gap-1 flex-wrap justify-content-end">

                                        <?php if (!empty($child['parent_id']) && !empty($child['group_id'])): ?>

                                            <?php lk_render_parent_contact_menu(

                                                'teacher',

                                                (int)$child['parent_id'],

                                                (int)$child['group_id'],

                                                $child['parent_phone'] ?? null,

                                                '..',

                                                true

                                            ); ?>

                                        <?php endif; ?>

                                        <button type="button" class="btn btn-sm btn-primary-dou mark-attendance" data-child="<?= (int)$child['id'] ?>" data-status="present" title="Пришёл"><i class="bi bi-check-lg"></i></button>

                                        <button type="button" class="btn btn-sm btn-outline-primary mark-departure" data-child="<?= (int)$child['id'] ?>" title="Ушёл"><i class="bi bi-box-arrow-right"></i></button>

                                        <button type="button" class="btn btn-sm btn-outline-secondary mark-attendance" data-child="<?= (int)$child['id'] ?>" data-status="absent" title="Отсутствует"><i class="bi bi-x-lg"></i></button>

                                    </div>

                                </td>

                            </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>



        <div class="d-xl-none row g-3 mt-3">

            <?php foreach ($children as $child): ?>

            <div class="col-12" data-child-row="<?= (int)$child['id'] ?>">

                <article class="lk-child-card<?= $child['has_tnr'] ? ' lk-child-card--tnr' : '' ?>">

                    <div class="lk-child-card-body">

                        <div class="d-flex justify-content-between mb-2">

                            <h5 class="fw-semibold mb-0"><?= htmlspecialchars($child['full_name']) ?></h5>

                            <?php if ($child['has_tnr']): ?><span class="badge badge-tnr">ТНР</span><?php endif; ?>

                        </div>

                        <p class="small mb-1">

                            <?= htmlspecialchars($child['parent_name'] ?? '—') ?>

                        </p>

                        <?php if (!empty($child['allergy_names'])): ?>

                            <p class="small text-allergy mb-2"><?= htmlspecialchars($child['allergy_names']) ?></p>

                        <?php endif; ?>

                        <p class="small mb-1 attendance-status"><?= lk_attendance_badge($child['today_status'] ?? null) ?></p>

                        <div class="attendance-times mb-3">

                            <?= lk_attendance_times_html($child['today_arrival'] ?? null, $child['today_departure'] ?? null) ?: '<p class="small text-muted mb-0">Время не отмечено</p>' ?>

                        </div>

                        <div class="d-flex flex-wrap gap-2">

                            <?php if (!empty($child['parent_id']) && !empty($child['group_id'])): ?>

                                <?php lk_render_parent_contact_menu(

                                    'teacher',

                                    (int)$child['parent_id'],

                                    (int)$child['group_id'],

                                    $child['parent_phone'] ?? null

                                ); ?>

                            <?php endif; ?>

                            <button type="button" class="btn btn-sm btn-primary-dou mark-attendance" data-child="<?= (int)$child['id'] ?>" data-status="present"><i class="bi bi-check-lg me-1"></i>Пришёл</button>

                            <button type="button" class="btn btn-sm btn-outline-primary mark-departure" data-child="<?= (int)$child['id'] ?>"><i class="bi bi-box-arrow-right me-1"></i>Ушёл</button>

                            <button type="button" class="btn btn-sm btn-outline-secondary mark-attendance" data-child="<?= (int)$child['id'] ?>" data-status="absent"><i class="bi bi-x-lg me-1"></i>Нет</button>

                        </div>

                    </div>

                </article>

            </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

