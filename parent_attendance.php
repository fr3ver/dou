<?php
/**
 * @var array $children
 * @var array $attendanceMap
 */
if ($children === []) {
    return;
}
?>
<section id="attendance" class="lk-block scroll-margin-top">
    <?php lk_section_title('clock-history', 'Посещаемость', 'Когда ребёнок приходил и уходил из сада'); ?>

    <div class="row g-3">
        <?php foreach ($children as $child): ?>
            <?php $history = $attendanceMap[(int)$child['id']] ?? []; ?>
            <div class="col-lg-6">
                <article class="lk-panel h-100">
                    <h3 class="h6 fw-semibold mb-3"><?= htmlspecialchars($child['full_name']) ?></h3>
                    <p class="small mb-3">
                        Сегодня: <?= lk_attendance_badge($child['today_status'] ?? null) ?>
                        <?php if (!empty($child['today_arrival']) || !empty($child['today_departure'])): ?>
                            <span class="text-muted ms-2">
                                <?= lk_format_time($child['today_arrival'] ?? null) ?>
                                →
                                <?= lk_format_time($child['today_departure'] ?? null) ?>
                            </span>
                        <?php endif; ?>
                    </p>

                    <?php if ($history === []): ?>
                        <p class="small text-muted mb-0">За последние дни отметок нет.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Дата</th>
                                        <th>Статус</th>
                                        <th>Пришёл</th>
                                        <th>Ушёл</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td><?= date('d.m.Y', strtotime($h['date'])) ?></td>
                                        <td>
                                            <?php if ($h['status'] === 'present'): ?>
                                                <span class="text-success">Был</span>
                                            <?php else: ?>
                                                <span class="text-muted">Не был</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= lk_format_time($h['arrival_time'] ?? null) ?></td>
                                        <td><?= lk_format_time($h['departure_time'] ?? null) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</section>
