<?php
/** @var array $attendanceSummary */
if ($attendanceSummary === []) {
    return;
}
?>
<div id="employee-attendance-week" class="lk-block scroll-margin-top">
    <?php lk_section_title('bar-chart', 'Посещаемость за неделю'); ?>
    <div class="lk-panel p-0 overflow-hidden">
        <div class="lk-table-wrap">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Дата</th><th>Пришли</th><th>Отсутствуют</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($attendanceSummary as $row): ?>
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
</div>
