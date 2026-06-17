<?php

require_once '_auth.php';

if (!role_can_access_reports((int)$_SESSION['role_id'])) {
    admin_flash('danger', 'Формирование отчётов доступно только заведующему.');
    header('Location: dashboard.php');
    exit;
}

require_once '_allergies.php';

require_once '_group_helpers.php';

require_once '_club_helpers.php';

require_once __DIR__ . '/../includes/club_attendance.php';

require_once __DIR__ . '/../includes/parent_fee.php';

require_once __DIR__ . '/../includes/menu_weekly.php';
require_once __DIR__ . '/../includes/public.php';
require_once '_qualification_helpers.php';

require_once '_report_export.php';

$report = $_GET['report'] ?? 'attendance';

$qualYear = (int)($_GET['year'] ?? date('Y'));
$qualStatus = trim($_GET['qual_status'] ?? 'all');
$allowedQualStatuses = ['all', 'ok', 'soon', 'overdue', 'none'];
if (!in_array($qualStatus, $allowedQualStatuses, true)) {
    $qualStatus = 'all';
}
if ($qualYear < 2000 || $qualYear > 2100) {
    $qualYear = (int)date('Y');
}

$month  = $_GET['month'] ?? date('Y-m');

$groupId = (int)($_GET['group_id'] ?? 0);



$allowedReports = ['attendance', 'payment', 'tnr', 'clubs', 'menu', 'events', 'qualifications'];

if (!in_array($report, $allowedReports, true)) {

    $report = 'attendance';

}



if (!preg_match('/^\d{4}-\d{2}$/', $month)) {

    $month = date('Y-m');

}



$monthStart = $month . '-01';

$monthEnd   = date('Y-m-t', strtotime($monthStart));

$ruMonths = [

    1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель', 5 => 'Май', 6 => 'Июнь',

    7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',

];

$monthLabel = ($ruMonths[(int)date('n', strtotime($monthStart))] ?? '') . ' ' . date('Y', strtotime($monthStart));



$groups = dou_groups_sorted_by_band($pdo);

if ($groupId <= 0 && $groups !== [] && $report === 'menu') {

    $groupId = (int)$groups[0]['id'];

}



$attendance_data = [];

$tnr_data = [];

$clubs_grouped = [];

$attendance_grouped = [];

$payment_data = [];

$payment_grouped = [];

$payment_summary = ['children' => 0, 'present_days' => 0, 'total' => 0.0];

$menu_data = [];

$menu_by_date = [];

$events_data = [];

$qualification_employees = [];

$qualification_courses = [];

$qualification_stats = [
    'total' => 0, 'ok' => 0, 'soon' => 0, 'overdue' => 0, 'none' => 0,
    'courses_in_year' => 0, 'hours_in_year' => 0,
];

$summary = ['children_tnr' => 0];



$eventStatusLabels = [

    'active'    => ['label' => 'Активно', 'class' => 'bg-soft-green text-dark'],

    'finished'  => ['label' => 'Завершено', 'class' => 'bg-secondary'],

    'cancelled' => ['label' => 'Отменено', 'class' => 'bg-soft-yellow text-dark'],

    'postponed' => ['label' => 'Перенесено', 'class' => 'bg-soft-blue text-dark'],

];



if ($report === 'attendance') {

    $stmt = $pdo->prepare("

        SELECT c.id, c.full_name, c.group_id, g.name AS group_name, g.age_category,

               SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,

               SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,

               COUNT(a.id) AS marked_days

        FROM children c

        JOIN `groups` g ON c.group_id = g.id

        LEFT JOIN attendance a ON a.child_id = c.id AND a.club_id = 0 AND a.date BETWEEN ? AND ?

        GROUP BY c.id, c.full_name, c.group_id, g.name, g.age_category

        ORDER BY c.full_name

    ");

    $stmt->execute([$monthStart, $monthEnd]);

    $attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $attendance_grouped = admin_report_grouped_by_group($pdo, $attendance_data);

}



if ($report === 'payment') {

    $payment_data = parent_fee_report_rows($pdo, $monthStart, $monthEnd, $groupId > 0 ? $groupId : 0);

    $payment_grouped = admin_report_grouped_by_group($pdo, $payment_data);

    foreach ($payment_data as $row) {

        $payment_summary['children']++;

        $payment_summary['present_days'] += (int)($row['present_days'] ?? 0);

        $payment_summary['total'] += (float)($row['total'] ?? 0);

    }

}



if ($report === 'tnr') {

    $tnr_data = $pdo->query("

        SELECT c.full_name, c.date_of_birth,

               " . admin_child_allergies_sql('c') . " AS allergy_names,

               g.name AS group_name, p.full_name AS parent_name

        FROM children c

        JOIN `groups` g ON c.group_id = g.id

        JOIN users p ON c.parent_id = p.id

        WHERE c.has_tnr = 1

        ORDER BY g.name, c.full_name

    ")->fetchAll(PDO::FETCH_ASSOC);

    $summary['children_tnr'] = count($tnr_data);

}



if ($report === 'clubs') {

    $clubs_grouped = admin_report_clubs_attendance_grouped($pdo, $monthStart, $monthEnd);

}



if ($report === 'menu' && $groupId > 0) {

    $menu_data = menu_weekly_for_date_range($pdo, $monthStart, $monthEnd, [$groupId]);

    foreach ($menu_data as $row) {
        $menu_by_date[$row['date']][] = $row;
    }
    ksort($menu_by_date);

}



if ($report === 'events') {

    $groupNamesSql = entity_groups_event_names_sql('e');

    $stmt = $pdo->prepare("

        SELECT e.*, u.full_name AS creator_name, {$groupNamesSql} AS group_names
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id

        WHERE COALESCE(

            CASE WHEN e.status = 'postponed' AND e.postponed_to IS NOT NULL THEN e.postponed_to END,

            e.event_date

        ) BETWEEN ? AND ?

        ORDER BY COALESCE(e.postponed_to, e.event_date) ASC, e.event_time ASC

    ");

    $stmt->execute([$monthStart, $monthEnd]);

    $events_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

}



if ($report === 'qualifications') {

    $qualification_employees = admin_qualification_filter_overview(
        admin_qualification_employees_overview($pdo),
        $qualStatus
    );

    $qualification_courses = admin_qualification_courses_in_year($pdo, $qualYear);

    $qualification_stats = admin_qualification_report_stats($pdo, $qualYear);

}



$selectedGroup = null;

foreach ($groups as $g) {

    if ((int)$g['id'] === $groupId) {

        $selectedGroup = $g;

        break;

    }

}



admin_page_start('Отчёты', 'Посещаемость, оплата, меню, мероприятия, ТНР, кружки и повышение квалификации');

?>



<ul class="nav nav-pills mb-4 gap-2 flex-wrap">

    <li class="nav-item">

        <a class="nav-link <?= $report === 'attendance' ? 'active' : '' ?>"

           href="reports.php?report=attendance&amp;month=<?= urlencode($month) ?>">

            <i class="bi bi-calendar-check me-1"></i>Посещаемость

        </a>

    </li>

    <li class="nav-item">

        <a class="nav-link <?= $report === 'payment' ? 'active' : '' ?>"

           href="reports.php?report=payment&amp;month=<?= urlencode($month) ?>&amp;group_id=<?= $groupId ?>">

            <i class="bi bi-cash-coin me-1"></i>Оплата

        </a>

    </li>

    <li class="nav-item">

        <a class="nav-link <?= $report === 'menu' ? 'active' : '' ?>"

           href="reports.php?report=menu&amp;group_id=<?= $groupId ?>&amp;month=<?= urlencode($month) ?>">

            <i class="bi bi-egg-fried me-1"></i>Меню

        </a>

    </li>

    <li class="nav-item">

        <a class="nav-link <?= $report === 'events' ? 'active' : '' ?>"

           href="reports.php?report=events&amp;month=<?= urlencode($month) ?>">

            <i class="bi bi-calendar-event me-1"></i>Мероприятия

        </a>

    </li>

    <li class="nav-item">

        <a class="nav-link <?= $report === 'tnr' ? 'active' : '' ?>" href="reports.php?report=tnr&amp;month=<?= urlencode($month) ?>">

            <i class="bi bi-chat-left-text me-1"></i>Дети с ТНР

        </a>

    </li>

    <li class="nav-item">

        <a class="nav-link <?= $report === 'clubs' ? 'active' : '' ?>" href="reports.php?report=clubs&amp;month=<?= urlencode($month) ?>">

            <i class="bi bi-palette me-1"></i>Кружки

        </a>

    </li>

    <li class="nav-item">

        <a class="nav-link <?= $report === 'qualifications' ? 'active' : '' ?>"
           href="reports.php?report=qualifications&amp;year=<?= $qualYear ?>&amp;qual_status=<?= urlencode($qualStatus) ?>">

            <i class="bi bi-mortarboard me-1"></i>Повышение квалификации

        </a>

    </li>

</ul>



<?php if ($report === 'attendance'): ?>

    <form method="GET" class="row g-2 align-items-end mb-4">

        <input type="hidden" name="report" value="attendance">

        <div class="col-auto">

            <label class="form-label fw-semibold">Месяц</label>

            <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($month) ?>">

        </div>

        <div class="col-auto"><button type="submit" class="btn btn-primary-dou">Показать</button></div>

        <div class="col-auto"><?php admin_report_export_button('attendance', ['month' => $month]); ?></div>

    </form>



    <?php admin_collapse_start('report-attendance', 'Таблица посещаемости за ' . $monthLabel, false, (string)count($attendance_data)); ?>

        <?php admin_render_table_search('Поиск по ребёнку, группе...'); ?>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0 admin-table admin-table-grouped">

                <thead class="table-light">

                    <tr>

                        <th>Ребёнок</th><th>Присутствовал</th><th>Отсутствовал</th><th>Всего отметок</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if ($attendance_grouped === []): ?>

                    <tr><td colspan="4" class="text-center text-muted py-4">Нет данных за выбранный период</td></tr>

                    <?php else: ?>

                        <?php foreach ($attendance_grouped as $block):

                            $group = $block['group'];

                            $gid = (int) $group['id'];

                            $rows = $block['rows'];

                            $count = count($rows);

                        ?>

                        <tr class="admin-table-group-header" data-group-header data-group-key="<?= $gid ?>">

                            <td colspan="4">

                                <div class="d-flex flex-wrap align-items-center gap-2">

                                    <span class="admin-table-group-title"><?= htmlspecialchars($group['name']) ?></span>

                                    <span class="text-muted small"><?= htmlspecialchars(admin_group_age_display($group['age_category'] ?? null)) ?></span>

                                    <span class="badge bg-soft-green text-dark"><?= $count ?> <?= $count === 1 ? 'ребёнок' : ($count < 5 ? 'ребёнка' : 'детей') ?></span>

                                    <a href="group_children.php?id=<?= $gid ?>" class="small link-more ms-auto">Состав группы</a>

                                </div>

                            </td>

                        </tr>

                        <?php foreach ($rows as $row): ?>

                        <tr class="admin-table-group-row" data-group-key="<?= $gid ?>">

                            <td class="fw-semibold ps-4"><?= htmlspecialchars($row['full_name']) ?></td>

                            <td class="text-success fw-semibold"><?= (int) $row['present_days'] ?></td>

                            <td class="text-allergy fw-semibold"><?= (int) $row['absent_days'] ?></td>

                            <td><?= (int) $row['marked_days'] ?></td>

                        </tr>

                        <?php endforeach; ?>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <?php admin_render_table_search_end(); ?>

    <?php admin_collapse_end(); ?>



<?php elseif ($report === 'payment'): ?>

    <div class="alert alert-light border shadow-sm small mb-4">

        Расчёт по постановлению Администрации г. Улан-Удэ от 27.06.2024 № 162:

        за каждый день посещения начисляется <strong>размер родительской платы в день</strong> по таблице постановления
        (в т.ч. питание и расходные материалы);

        при раннем уходе — пониженный тариф по фактическому времени (кратковременное — 51,0 ₽/день, в т.ч. 48 + 3).

    </div>

    <form method="GET" class="row g-2 align-items-end mb-4">

        <input type="hidden" name="report" value="payment">

        <div class="col-auto">

            <label class="form-label fw-semibold">Месяц</label>

            <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($month) ?>">

        </div>

        <div class="col-auto">

            <label class="form-label fw-semibold">Группа</label>

            <select name="group_id" class="form-select-groups">

                <option value="0"<?= $groupId <= 0 ? ' selected' : '' ?>>Все группы</option>

                <?php dou_render_group_select_options($pdo, $groupId > 0 ? $groupId : 0, false); ?>

            </select>

        </div>

        <div class="col-auto"><button type="submit" class="btn btn-primary-dou">Показать</button></div>

        <div class="col-auto"><?php admin_report_export_button('payment', ['month' => $month, 'group_id' => $groupId]); ?></div>

    </form>

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body py-3 text-center">

                    <div class="fs-4 fw-bold"><?= (int)$payment_summary['children'] ?></div>

                    <div class="small text-muted">Детей в отчёте</div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body py-3 text-center">

                    <div class="fs-4 fw-bold text-success"><?= (int)$payment_summary['present_days'] ?></div>

                    <div class="small text-muted">Дней посещения (всего)</div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body py-3 text-center">

                    <div class="fs-4 fw-bold"><?= parent_fee_format_rub($payment_summary['total']) ?></div>

                    <div class="small text-muted">Сумма к оплате</div>

                </div>

            </div>

        </div>

    </div>

    <?php admin_collapse_start('report-payment', 'Родительская плата за ' . $monthLabel, false, (string)count($payment_data)); ?>

        <?php admin_render_table_search('Поиск по ребёнку, группе, родителю...'); ?>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0 admin-table admin-table-grouped">

                <thead class="table-light">

                    <tr>

                        <th>Ребёнок</th>

                        <th>Родитель</th>

                        <th>Дней в саду</th>

                        <th>Тариф группы, ₽/день</th>

                        <th>К оплате</th>

                        <th></th>

                    </tr>

                </thead>

                <tbody>

                    <?php if ($payment_grouped === []): ?>

                    <tr><td colspan="6" class="text-center text-muted py-4">Нет данных за выбранный период</td></tr>

                    <?php else: ?>

                        <?php foreach ($payment_grouped as $block):

                            $group = $block['group'];

                            $gid = (int) $group['id'];

                            $rows = $block['rows'];

                            $count = count($rows);

                            $groupTotal = 0.0;

                            foreach ($rows as $r) {

                                $groupTotal += (float)($r['total'] ?? 0);

                            }

                        ?>

                        <tr class="admin-table-group-header" data-group-header data-group-key="<?= $gid ?>">

                            <td colspan="6">

                                <div class="d-flex flex-wrap align-items-center gap-2">

                                    <span class="admin-table-group-title"><?= htmlspecialchars($group['name']) ?></span>

                                    <span class="text-muted small"><?= htmlspecialchars(admin_group_age_display($group['age_category'] ?? null)) ?></span>

                                    <span class="text-muted small"><?= htmlspecialchars(parent_fee_care_mode_label($group['care_mode'] ?? '12h')) ?></span>

                                    <span class="badge bg-light text-dark border"><?= parent_fee_format_rub(parent_fee_contracted_daily_rate($group['age_category'] ?? '', $group['care_mode'] ?? '12h')) ?>/день</span>

                                    <span class="badge bg-soft-green text-dark"><?= $count ?> <?= $count === 1 ? 'ребёнок' : ($count < 5 ? 'ребёнка' : 'детей') ?></span>

                                    <span class="badge bg-soft-blue text-dark ms-auto"><?= parent_fee_format_rub($groupTotal) ?></span>

                                </div>

                            </td>

                        </tr>

                        <?php foreach ($rows as $row):

                            $detailId = 'payment-detail-' . (int)$row['child_id'];

                        ?>

                        <tr class="admin-table-group-row" data-group-key="<?= $gid ?>">

                            <td class="fw-semibold ps-4"><?= htmlspecialchars($row['full_name']) ?></td>

                            <td class="small"><?= htmlspecialchars($row['parent_name'] ?? '—') ?></td>

                            <td class="text-success fw-semibold"><?= (int)$row['present_days'] ?></td>

                            <td><?= parent_fee_format_rub((float)($row['contracted_daily_rate'] ?? 0)) ?></td>

                            <td class="fw-semibold"><?= parent_fee_format_rub((float)$row['total']) ?></td>

                            <td>

                                <button type="button" class="btn btn-sm btn-outline-secondary"

                                        data-bs-toggle="collapse" data-bs-target="#<?= $detailId ?>"

                                        aria-expanded="false">По дням</button>

                            </td>

                        </tr>

                        <tr class="admin-table-group-row collapse" id="<?= $detailId ?>" data-group-key="<?= $gid ?>">

                            <td colspan="6" class="ps-4 pb-3">

                                <div class="table-responsive">

                                    <table class="table table-sm mb-0">

                                        <thead>

                                            <tr>

                                                <th>Дата</th><th>Статус</th><th>Пришёл</th><th>Ушёл</th><th>Часы</th><th>Режим</th><th>Плата, ₽/день</th><th>в т.ч. питание</th><th>в т.ч. материалы</th>

                                            </tr>

                                        </thead>

                                        <tbody>

                                            <?php foreach ($row['days'] as $day): ?>

                                            <tr>

                                                <td><?= date('d.m.Y', strtotime($day['date'])) ?></td>

                                                <td><?= $day['status'] === 'present' ? 'Был' : ($day['status'] === 'absent' ? 'Не был' : '—') ?></td>

                                                <td><?= parent_fee_format_time($day['arrival_time'] ?? null) ?></td>

                                                <td><?= parent_fee_format_time($day['departure_time'] ?? null) ?></td>

                                                <td><?= $day['hours'] !== null ? number_format($day['hours'], 1, ',', '') : '—' ?></td>

                                                <td class="small"><?= $day['effective_mode'] ? htmlspecialchars(parent_fee_care_mode_label($day['effective_mode'])) : '—' ?></td>

                                                <td class="fw-semibold"><?= (float)($day['day_rate'] ?? 0) > 0 ? parent_fee_format_rub((float)$day['day_rate']) : '—' ?></td>

                                                <td><?= (float)($day['food'] ?? 0) > 0 ? parent_fee_format_rub((float)$day['food']) : '—' ?></td>

                                                <td><?= (float)($day['hygiene'] ?? 0) > 0 ? parent_fee_format_rub((float)$day['hygiene']) : '—' ?></td>

                                            </tr>

                                            <?php endforeach; ?>

                                        </tbody>

                                    </table>

                                </div>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <?php admin_render_table_search_end(); ?>

    <?php admin_collapse_end(); ?>



<?php elseif ($report === 'menu'): ?>

    <?php if ($groups === []): ?>

        <div class="alert alert-warning border-0 shadow-sm">Сначала создайте группы.</div>

    <?php else: ?>

        <form method="GET" class="row g-2 align-items-end mb-4">

            <input type="hidden" name="report" value="menu">

            <div class="col-auto">

                <label class="form-label fw-semibold">Месяц</label>

                <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($month) ?>">

            </div>

            <div class="col-auto">

                <label class="form-label fw-semibold">Группа</label>

                <select name="group_id" class="form-select-groups">

                    <?php dou_render_group_select_options($pdo, $groupId); ?>

                </select>

            </div>

            <div class="col-auto"><button type="submit" class="btn btn-primary-dou">Показать</button></div>

            <div class="col-auto"><?php admin_report_export_button('menu', ['month' => $month, 'group_id' => $groupId]); ?></div>

            <div class="col-auto">

                <a href="menu.php?group_id=<?= $groupId ?>" class="btn btn-outline-secondary">

                    <i class="bi bi-pencil me-1"></i>Редактировать меню

                </a>

            </div>

        </form>



        <?php if ($selectedGroup): ?>

        <p class="text-muted small mb-4">

            Меню за <strong><?= htmlspecialchars($monthLabel) ?></strong> (будние дни) —

            <strong><?= htmlspecialchars($selectedGroup['name']) ?></strong>,

            <?= htmlspecialchars(admin_group_age_display($selectedGroup['age_category'] ?? null)) ?>.

        </p>

        <?php endif; ?>



        <?php if ($menu_by_date === []): ?>

            <div class="alert alert-light border shadow-sm text-muted">Нет блюд за выбранный период.</div>

        <?php endif; ?>



        <?php foreach ($menu_by_date as $dateStr => $dayItems): ?>

            <?php
            $dayNum = (int) date('N', strtotime($dateStr));
            $dayLabel = date('d.m.Y', strtotime($dateStr)) . ' · ' . menu_weekly_weekday_label($dayNum);
            $isToday = $dateStr === date('Y-m-d');
            ?>

            <?php admin_collapse_start('report-menu-day-' . str_replace('-', '', $dateStr), $dayLabel, $isToday, (string)count($dayItems)); ?>

                <?php if ($dayItems === []): ?>

                    <p class="text-muted mb-0 py-2">Блюда не заданы.</p>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table table-sm table-hover align-middle mb-0 admin-table">

                            <thead class="table-light">

                                <tr><th>Приём пищи</th><th>Блюдо</th><th>Вес (г)</th><th>Аллергены</th></tr>

                            </thead>

                            <tbody>

                                <?php foreach ($dayItems as $m): ?>

                                <tr>

                                    <td><?= htmlspecialchars($m['meal_type']) ?></td>

                                    <td class="fw-semibold"><?= htmlspecialchars($m['dish_name']) ?></td>

                                    <td><?= $m['weight'] ? (int)$m['weight'] : '—' ?></td>

                                    <td class="small text-allergy"><?= htmlspecialchars($m['allergies'] ?? '—') ?></td>

                                </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            <?php admin_collapse_end(); ?>

        <?php endforeach; ?>

    <?php endif; ?>



<?php elseif ($report === 'events'): ?>

    <form method="GET" class="row g-2 align-items-end mb-4">

        <input type="hidden" name="report" value="events">

        <div class="col-auto">

            <label class="form-label fw-semibold">Месяц</label>

            <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($month) ?>">

        </div>

        <div class="col-auto"><button type="submit" class="btn btn-primary-dou">Показать</button></div>

        <div class="col-auto"><?php admin_report_export_button('events', ['month' => $month]); ?></div>

        <div class="col-auto">

            <a href="events.php" class="btn btn-outline-secondary">

                <i class="bi bi-pencil me-1"></i>Редактировать мероприятия

            </a>

        </div>

    </form>



    <?php admin_collapse_start('report-events', 'Мероприятия за ' . $monthLabel, false, (string)count($events_data)); ?>

        <?php admin_render_table_search('Поиск по названию, группе, месту...'); ?>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0 admin-table">

                <thead class="table-light">

                    <tr>

                        <th>Дата</th>

                        <th>Название</th>

                        <th>Группа</th>

                        <th>Место</th>

                        <th>Статус</th>

                        <th>Описание</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($events_data as $e):

                        $st = $eventStatusLabels[$e['status']] ?? ['label' => $e['status'], 'class' => 'bg-secondary'];

                        $displayDate = ($e['status'] === 'postponed' && !empty($e['postponed_to']))

                            ? $e['postponed_to'] : $e['event_date'];

                    ?>

                    <tr>

                        <td>

                            <?= date('d.m.Y', strtotime($displayDate)) ?>

                            <?php if ($e['event_time']): ?>

                                <br><small class="text-muted"><?= substr($e['event_time'], 0, 5) ?></small>

                            <?php endif; ?>

                            <?php if ($e['status'] === 'postponed' && !empty($e['postponed_to'])): ?>

                                <br><small class="text-muted">было <?= date('d.m.Y', strtotime($e['event_date'])) ?></small>

                            <?php endif; ?>

                        </td>

                        <td class="fw-semibold"><?= htmlspecialchars($e['title']) ?></td>

                        <td><?= htmlspecialchars(entity_groups_format_event_groups($e)) ?></td>

                        <td><?= htmlspecialchars($e['location'] ?? '—') ?></td>

                        <td><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>

                        <td class="small text-muted"><?= !empty($e['description']) ? htmlspecialchars(public_excerpt($e['description'], 80)) : '—' ?></td>

                    </tr>

                    <?php endforeach; ?>

                    <?php if (count($events_data) === 0): ?>

                    <tr><td colspan="6" class="text-center text-muted py-4">Нет мероприятий за выбранный месяц</td></tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <?php admin_render_table_search_end(); ?>

    <?php admin_collapse_end(); ?>



<?php elseif ($report === 'tnr'): ?>

    <form method="GET" class="row g-2 align-items-end mb-4">

        <input type="hidden" name="report" value="tnr">

        <div class="col-auto">

            <label class="form-label fw-semibold">Месяц</label>

            <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($month) ?>">

        </div>

        <div class="col-auto"><button type="submit" class="btn btn-primary-dou">Показать</button></div>

        <div class="col-auto"><?php admin_report_export_button('tnr', ['month' => $month]); ?></div>

    </form>

    <?php admin_collapse_start('report-tnr', 'Дети с ТНР · ' . $monthLabel, false, (string)$summary['children_tnr']); ?>

        <?php admin_render_table_search('Поиск по ФИО, группе, родителю...'); ?>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0 admin-table">

                <thead class="table-light">

                    <tr>

                        <th>ФИО</th><th>Дата рождения</th><th>Группа</th><th>Родитель</th><th>Аллергены</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($tnr_data as $row): ?>

                    <tr>

                        <td class="fw-semibold"><?= htmlspecialchars($row['full_name']) ?></td>

                        <td><?= date('d.m.Y', strtotime($row['date_of_birth'])) ?></td>

                        <td><?= htmlspecialchars($row['group_name']) ?></td>

                        <td><?= htmlspecialchars($row['parent_name']) ?></td>

                        <td class="small text-allergy"><?= htmlspecialchars($row['allergy_names'] ?? '—') ?></td>

                    </tr>

                    <?php endforeach; ?>

                    <?php if (count($tnr_data) === 0): ?>

                    <tr><td colspan="5" class="text-center text-muted py-4">Детей с ТНР не найдено</td></tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <?php admin_render_table_search_end(); ?>

    <?php admin_collapse_end(); ?>



<?php elseif ($report === 'clubs'): ?>

    <form method="GET" class="row g-2 align-items-end mb-4">

        <input type="hidden" name="report" value="clubs">

        <div class="col-auto">

            <label class="form-label fw-semibold">Месяц</label>

            <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($month) ?>">

        </div>

        <div class="col-auto"><button type="submit" class="btn btn-primary-dou">Показать</button></div>

        <div class="col-auto"><?php admin_report_export_button('clubs', ['month' => $month]); ?></div>

        <div class="col-auto">

            <a href="clubs.php" class="btn btn-outline-secondary">

                <i class="bi bi-pencil me-1"></i>Редактировать кружки

            </a>

        </div>

    </form>

    <?php

    $clubs_enrolled = 0;

    foreach ($clubs_grouped as $block) {

        $clubs_enrolled += count($block['members']);

    }

    ?>

    <?php admin_collapse_start('report-clubs', 'Посещаемость кружков за ' . $monthLabel, false, (string) $clubs_enrolled); ?>

        <?php admin_render_table_search('Поиск по кружку, ребёнку, группе...'); ?>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0 admin-table admin-table-grouped">

                <thead class="table-light">

                    <tr>

                        <th>Ребёнок</th><th>Группа в саду</th><th>Присутствовал</th><th>Отсутствовал</th><th>Всего отметок</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if ($clubs_grouped === []): ?>

                    <tr><td colspan="5" class="text-center text-muted py-4">Кружков пока нет</td></tr>

                    <?php else: ?>

                        <?php foreach ($clubs_grouped as $block):

                            $club = $block['club'];

                            $cid = (int) $club['id'];

                            $members = $block['members'];

                            $count = count($members);

                            $max = $club['max_participants'] !== null ? (int) $club['max_participants'] : null;

                        ?>

                        <tr class="admin-table-group-header admin-table-club-header" data-group-header data-group-key="club-<?= $cid ?>">

                            <td colspan="5">

                                <div class="d-flex flex-wrap align-items-center gap-2">

                                    <span class="admin-table-group-title"><?= htmlspecialchars($club['name']) ?></span>

                                    <?php if (!empty($club['age_category'])): ?>

                                        <span class="text-muted small"><?= htmlspecialchars($club['age_category']) ?></span>

                                    <?php endif; ?>

                                    <?php if (!empty($club['schedule'])): ?>

                                        <span class="text-muted small"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($club['schedule']) ?></span>

                                    <?php endif; ?>

                                    <?php if (!empty($club['teacher_name'])): ?>

                                        <span class="text-muted small"><i class="bi bi-person me-1"></i><?= htmlspecialchars($club['teacher_name']) ?></span>

                                    <?php endif; ?>

                                    <span class="badge bg-soft-green text-dark"><?= admin_club_seats_label($count, $max) ?> уч.</span>

                                    <a href="club_members.php?id=<?= $cid ?>" class="small link-more ms-auto">Состав кружка</a>

                                </div>

                            </td>

                        </tr>

                        <?php if ($members === []): ?>

                        <tr class="admin-table-group-row" data-group-key="club-<?= $cid ?>">

                            <td colspan="5" class="text-muted ps-4 py-3">Участников пока нет</td>

                        </tr>

                        <?php else: ?>

                            <?php foreach ($members as $m): ?>

                            <tr class="admin-table-group-row" data-group-key="club-<?= $cid ?>">

                                <td class="fw-semibold ps-4"><?= htmlspecialchars($m['child_name']) ?></td>

                                <td><?= htmlspecialchars($m['group_name'] ?? '—') ?></td>

                                <td class="text-success fw-semibold"><?= (int)($m['present_days'] ?? 0) ?></td>

                                <td class="text-allergy fw-semibold"><?= (int)($m['absent_days'] ?? 0) ?></td>

                                <td><?= (int)($m['marked_days'] ?? 0) ?></td>

                            </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <?php admin_render_table_search_end(); ?>

    <?php admin_collapse_end(); ?>



<?php elseif ($report === 'qualifications'): ?>

    <form method="GET" class="row g-2 align-items-end mb-4">
        <input type="hidden" name="report" value="qualifications">
        <div class="col-auto">
            <label class="form-label fw-semibold">Год прохождения курсов</label>
            <input type="number" name="year" class="form-control" min="2000" max="2100"
                   value="<?= $qualYear ?>" style="width: 7rem;">
        </div>
        <div class="col-auto">
            <label class="form-label fw-semibold">Статус срока</label>
            <select name="qual_status" class="form-select">
                <option value="all"<?= $qualStatus === 'all' ? ' selected' : '' ?>>Все сотрудники</option>
                <option value="ok"<?= $qualStatus === 'ok' ? ' selected' : '' ?>>В норме</option>
                <option value="soon"<?= $qualStatus === 'soon' ? ' selected' : '' ?>>Скоро срок</option>
                <option value="overdue"<?= $qualStatus === 'overdue' ? ' selected' : '' ?>>Просрочено</option>
                <option value="none"<?= $qualStatus === 'none' ? ' selected' : '' ?>>Нет данных</option>
            </select>
        </div>
        <div class="col-auto"><button type="submit" class="btn btn-primary-dou">Показать</button></div>
        <div class="col-auto"><?php admin_report_export_button('qualifications', ['year' => $qualYear, 'qual_status' => $qualStatus]); ?></div>
        <div class="col-auto">
            <a href="qualifications.php" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i>Учёт курсов
            </a>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold"><?= (int)$qualification_stats['total'] ?></div>
                    <div class="small text-muted">Сотрудников</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold text-success"><?= (int)$qualification_stats['ok'] ?></div>
                    <div class="small text-muted">В норме</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold text-warning"><?= (int)$qualification_stats['soon'] ?></div>
                    <div class="small text-muted">Скоро срок</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold text-danger"><?= (int)$qualification_stats['overdue'] ?></div>
                    <div class="small text-muted">Просрочено</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold"><?= (int)$qualification_stats['courses_in_year'] ?></div>
                    <div class="small text-muted">Курсов за <?= $qualYear ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold"><?= (int)$qualification_stats['hours_in_year'] ?></div>
                    <div class="small text-muted">Часов за <?= $qualYear ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php admin_collapse_start(
        'report-qual-employees',
        'Сводка по сотрудникам',
        in_array($qualStatus, ['soon', 'overdue'], true),
        (string)count($qualification_employees)
    ); ?>
        <?php admin_render_table_search('Поиск по ФИО, должности, курсу...'); ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 admin-table">
                <thead class="table-light">
                    <tr>
                        <th>Сотрудник</th>
                        <th>Должность</th>
                        <th>Последний курс</th>
                        <th>Окончание</th>
                        <th>Следующий срок</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qualification_employees as $row): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['position']) ?></td>
                        <td><?= !empty($row['last_course']) ? htmlspecialchars($row['last_course']) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= admin_qualification_format_date($row['completed_on'] ?? null) ?></td>
                        <td><?= admin_qualification_format_date($row['next_due_on'] ?? null) ?></td>
                        <td><?= admin_qualification_status_badge($row['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($qualification_employees === []): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Нет сотрудников по выбранному фильтру</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php admin_render_table_search_end(); ?>
    <?php admin_collapse_end(); ?>

    <?php admin_collapse_start(
        'report-qual-courses',
        'Курсы, пройденные в ' . $qualYear . ' году',
        false,
        (string)count($qualification_courses)
    ); ?>
        <?php admin_render_table_search('Поиск по сотруднику, курсу, организации...'); ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 admin-table">
                <thead class="table-light">
                    <tr>
                        <th>Сотрудник</th>
                        <th>Должность</th>
                        <th>Курс</th>
                        <th>Организация</th>
                        <th>Окончание</th>
                        <th>Часы</th>
                        <th>Удостоверение</th>
                        <th>След. срок</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qualification_courses as $course): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($course['full_name']) ?></td>
                        <td><?= htmlspecialchars($course['position']) ?></td>
                        <td><?= htmlspecialchars($course['title']) ?></td>
                        <td class="small"><?= htmlspecialchars($course['organization'] ?? '—') ?></td>
                        <td><?= admin_qualification_format_date($course['completed_on'] ?? null) ?></td>
                        <td><?= !empty($course['hours']) ? (int)$course['hours'] : '—' ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($course['certificate_no'] ?? '—') ?></td>
                        <td><?= admin_qualification_format_date($course['next_due_on'] ?? null) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($qualification_courses === []): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Нет курсов за выбранный год</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php admin_render_table_search_end(); ?>
    <?php admin_collapse_end(); ?>

<?php endif; ?>



<?php admin_page_end(); ?>


