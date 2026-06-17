<?php
/** @var array|null $groupInfo */
/** @var array $children */

if (!$groupInfo) {
    return;
}

$total_children = count($children);
$tnr_count = count(array_filter($children, fn($c) => !empty($c['has_tnr'])));
$allergy_count = count(array_filter($children, fn($c) => !empty($c['allergy_names'])));
?>
<div id="employee-group-info" class="lk-block scroll-margin-top">
    <?php lk_section_title('collection', 'Моя группа'); ?>

    <div class="lk-panel mb-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <h3 class="h5 fw-semibold mb-2"><?= htmlspecialchars($groupInfo['name']) ?></h3>
                    <?php if (!empty($groupInfo['age_category'])): ?>
                        <p class="text-muted small mb-2"><?= htmlspecialchars(dou_age_display($groupInfo['age_category'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($groupInfo['description'])): ?>
                        <p class="small mb-0"><?= htmlspecialchars($groupInfo['description']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="group-stat-box mb-2">
                        <i class="bi bi-emoji-smile text-primary"></i>
                        <div>
                            <div class="fw-semibold"><?= (int) $groupInfo['children_count'] ?> / <?= (int) $groupInfo['capacity'] ?></div>
                            <div class="small text-muted">детей в группе</div>
                        </div>
                    </div>
                </div>
            </div>
    </div>

    <?php if ($total_children === 0): ?>
        <div class="lk-panel">
            <div class="lk-empty-state">В вашей группе пока нет детей.</div>
        </div>
    <?php else: ?>
        <div class="lk-panel p-0 overflow-hidden d-none d-lg-block">
            <div class="px-3 py-2 border-bottom fw-semibold small text-muted">Список детей</div>
            <div class="lk-table-wrap">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ФИО</th>
                            <th>Дата рождения</th>
                            <th>Родитель</th>
                            <th>ТНР</th>
                            <th>Аллергены</th>
                            <th>Сегодня</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($children as $child): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($child['full_name']) ?></td>
                            <td class="small text-nowrap">
                                <?= !empty($child['date_of_birth'])
                                    ? date('d.m.Y', strtotime($child['date_of_birth']))
                                    : '—' ?>
                            </td>
                            <td class="small">
                                <?= htmlspecialchars($child['parent_name'] ?? '—') ?>
                            </td>
                            <td><?= $child['has_tnr'] ? '<span class="badge badge-tnr">ТНР</span>' : '<span class="text-muted">—</span>' ?></td>
                            <td class="small">
                                <?= !empty($child['allergy_names'])
                                    ? '<span class="text-allergy">' . htmlspecialchars($child['allergy_names']) . '</span>'
                                    : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td><?= lk_attendance_badge($child['today_status'] ?? null) ?></td>
                            <td class="text-end">
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
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-lg-none">
            <h3 class="h6 fw-semibold mb-3">Список детей</h3>
            <div class="row g-3">
                <?php foreach ($children as $child): ?>
                <div class="col-12">
                    <article class="lk-child-card<?= $child['has_tnr'] ? ' lk-child-card--tnr' : '' ?>">
                        <div class="lk-child-card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <h4 class="h6 fw-semibold mb-0"><?= htmlspecialchars($child['full_name']) ?></h4>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php if ($child['has_tnr']): ?>
                                        <span class="badge badge-tnr">ТНР</span>
                                    <?php endif; ?>
                                    <?= lk_attendance_badge($child['today_status'] ?? null) ?>
                                </div>
                            </div>
                            <?php if (!empty($child['date_of_birth'])): ?>
                                <p class="small text-muted mb-1">
                                    <?= date('d.m.Y', strtotime($child['date_of_birth'])) ?>
                                </p>
                            <?php endif; ?>
                            <p class="small mb-1">
                                <?= htmlspecialchars($child['parent_name'] ?? '—') ?>
                            </p>
                            <?php if (!empty($child['allergy_names'])): ?>
                                <p class="small text-allergy mb-2"><?= htmlspecialchars($child['allergy_names']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($child['parent_id']) && !empty($child['group_id'])): ?>
                                <div class="mt-2">
                                    <?php lk_render_parent_contact_menu(
                                        'teacher',
                                        (int)$child['parent_id'],
                                        (int)$child['group_id'],
                                        $child['parent_phone'] ?? null
                                    ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
