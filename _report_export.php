<?php

require_once __DIR__ . '/_allergies.php';
require_once __DIR__ . '/_group_helpers.php';
require_once __DIR__ . '/_club_helpers.php';
require_once __DIR__ . '/_qualification_helpers.php';
require_once __DIR__ . '/../includes/club_attendance.php';
require_once __DIR__ . '/../includes/parent_fee.php';
require_once __DIR__ . '/../includes/menu_weekly.php';
require_once __DIR__ . '/../includes/public.php';
require_once __DIR__ . '/../includes/entity_groups.php';

function admin_report_export_link(string $report, array $params = []): string
{
    $params['report'] = $report;

    return 'reports_export.php?' . http_build_query($params);
}

function admin_report_export_button(string $report, array $params = [], string $label = 'Excel'): void
{
    $href = admin_report_export_link($report, $params);
    ?>
    <a href="<?= htmlspecialchars($href) ?>" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i><?= htmlspecialchars($label) ?>
    </a>
    <?php
}

/** @param list<list<string|int|float|null>> $rows */
function admin_report_send_excel(string $filename, array $headers, array $rows): void
{
    $safeName = preg_replace('/[^\w\-\.]+/u', '_', $filename) ?: 'report.csv';
    if (!str_ends_with(strtolower($safeName), '.csv')) {
        $safeName .= '.csv';
    }

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Cache-Control: max-age=0');

    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }

    fputcsv($out, $headers, ';');
    foreach ($rows as $row) {
        fputcsv($out, array_map(static function ($cell) {
            if ($cell === null) {
                return '';
            }
            if (is_float($cell) || is_int($cell)) {
                return (string) $cell;
            }

            return (string) $cell;
        }, $row), ';');
    }
    fclose($out);
    exit;
}

function admin_report_export_attendance(PDO $pdo, string $monthStart, string $monthEnd): void
{
    $stmt = $pdo->prepare("
        SELECT c.full_name, g.name AS group_name,
               SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,
               SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
               COUNT(a.id) AS marked_days
        FROM children c
        JOIN `groups` g ON c.group_id = g.id
        LEFT JOIN attendance a ON a.child_id = c.id AND a.club_id = 0 AND a.date BETWEEN ? AND ?
        GROUP BY c.id, c.full_name, g.name
        ORDER BY g.name, c.full_name
    ");
    $stmt->execute([$monthStart, $monthEnd]);
    $rows = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $rows[] = [
            $r['full_name'],
            $r['group_name'],
            (int) $r['present_days'],
            (int) $r['absent_days'],
            (int) $r['marked_days'],
        ];
    }

    admin_report_send_excel(
        'poseshchaemost_' . substr($monthStart, 0, 7),
        ['Ребёнок', 'Группа', 'Присутствовал', 'Отсутствовал', 'Всего отметок'],
        $rows
    );
}

function admin_report_export_payment(PDO $pdo, string $monthStart, string $monthEnd, int $groupId): void
{
    $data = parent_fee_report_rows($pdo, $monthStart, $monthEnd, $groupId);
    $rows = [];
    foreach ($data as $r) {
        $rows[] = [
            $r['full_name'],
            $r['group_name'],
            $r['parent_name'] ?? '',
            (int) $r['present_days'],
            number_format((float) $r['contracted_daily_rate'], 2, ',', ''),
            number_format((float) $r['total'], 2, ',', ''),
        ];
    }

    admin_report_send_excel(
        'oplata_' . substr($monthStart, 0, 7),
        ['Ребёнок', 'Группа', 'Родитель', 'Дней в саду', 'Тариф группы ₽/день', 'К оплате'],
        $rows
    );
}

function admin_report_export_tnr(PDO $pdo, string $month): void
{
    $tnr = $pdo->query("
        SELECT c.full_name, c.date_of_birth,
               " . admin_child_allergies_sql('c') . " AS allergy_names,
               g.name AS group_name, p.full_name AS parent_name
        FROM children c
        JOIN `groups` g ON c.group_id = g.id
        JOIN users p ON c.parent_id = p.id
        WHERE c.has_tnr = 1
        ORDER BY g.name, c.full_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $rows = [];
    foreach ($tnr as $r) {
        $rows[] = [
            $r['full_name'],
            date('d.m.Y', strtotime($r['date_of_birth'])),
            $r['group_name'],
            $r['parent_name'],
            $r['allergy_names'] ?? '',
        ];
    }

    admin_report_send_excel(
        'deti_tnr_' . $month,
        ['ФИО', 'Дата рождения', 'Группа', 'Родитель', 'Аллергены'],
        $rows
    );
}

function admin_report_export_clubs(PDO $pdo, string $monthStart, string $monthEnd): void
{
    $grouped = admin_report_clubs_attendance_grouped($pdo, $monthStart, $monthEnd);
    $rows = [];
    foreach ($grouped as $block) {
        $clubName = $block['club']['name'] ?? '';
        foreach ($block['members'] as $m) {
            $rows[] = [
                $clubName,
                $m['child_name'] ?? '',
                $m['group_name'] ?? '',
                (int) ($m['present_days'] ?? 0),
                (int) ($m['absent_days'] ?? 0),
                (int) ($m['marked_days'] ?? 0),
            ];
        }
    }

    admin_report_send_excel(
        'krushki_' . substr($monthStart, 0, 7),
        ['Кружок', 'Ребёнок', 'Группа в саду', 'Присутствовал', 'Отсутствовал', 'Всего отметок'],
        $rows
    );
}

function admin_report_export_menu(PDO $pdo, string $monthStart, string $monthEnd, int $groupId): void
{
    if ($groupId <= 0) {
        admin_report_send_excel('menu', ['Сообщение'], [['Выберите группу']]);
    }

    $items = menu_weekly_for_date_range($pdo, $monthStart, $monthEnd, [$groupId]);
    $rows = [];
    foreach ($items as $m) {
        $weekday = (int) date('N', strtotime($m['date']));
        $rows[] = [
            date('d.m.Y', strtotime($m['date'])),
            menu_weekly_weekday_label($weekday),
            $m['group_name'] ?? '',
            $m['meal_type'],
            $m['dish_name'],
            $m['weight'] ? (int) $m['weight'] : '',
            $m['allergies'] ?? '',
        ];
    }

    admin_report_send_excel(
        'menu_' . substr($monthStart, 0, 7) . '_gr' . $groupId,
        ['Дата', 'День недели', 'Группа', 'Приём пищи', 'Блюдо', 'Вес (г)', 'Аллергены'],
        $rows
    );
}

function admin_report_export_events(PDO $pdo, string $monthStart, string $monthEnd): void
{
    $groupNamesSql = entity_groups_event_names_sql('e');
    $stmt = $pdo->prepare("
        SELECT e.*, {$groupNamesSql} AS group_names
        FROM events e
        WHERE COALESCE(
            CASE WHEN e.status = 'postponed' AND e.postponed_to IS NOT NULL THEN e.postponed_to END,
            e.event_date
        ) BETWEEN ? AND ?
        ORDER BY COALESCE(e.postponed_to, e.event_date) ASC, e.event_time ASC
    ");
    $stmt->execute([$monthStart, $monthEnd]);
    $statusLabels = [
        'active' => 'Активно',
        'finished' => 'Завершено',
        'cancelled' => 'Отменено',
        'postponed' => 'Перенесено',
    ];
    $rows = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $e) {
        $displayDate = ($e['status'] === 'postponed' && !empty($e['postponed_to']))
            ? $e['postponed_to'] : $e['event_date'];
        $rows[] = [
            date('d.m.Y', strtotime($displayDate)),
            $e['event_time'] ? substr($e['event_time'], 0, 5) : '',
            $e['title'],
            entity_groups_format_event_groups($e),
            $e['location'] ?? '',
            $statusLabels[$e['status']] ?? $e['status'],
            strip_tags($e['description'] ?? ''),
        ];
    }

    admin_report_send_excel(
        'meropriyatiya_' . substr($monthStart, 0, 7),
        ['Дата', 'Время', 'Название', 'Группа', 'Место', 'Статус', 'Описание'],
        $rows
    );
}

function admin_report_export_qualifications(PDO $pdo, int $year, string $qualStatus): void
{
    $employees = admin_qualification_filter_overview(
        admin_qualification_employees_overview($pdo),
        $qualStatus
    );
    $statusLabels = [
        'ok' => 'В норме',
        'soon' => 'Скоро срок',
        'overdue' => 'Просрочено',
        'none' => 'Нет данных',
    ];
    $rows = [];
    foreach ($employees as $row) {
        $rows[] = [
            $row['full_name'],
            $row['position'],
            $row['last_course'] ?? '',
            admin_qualification_format_date($row['completed_on'] ?? null),
            admin_qualification_format_date($row['next_due_on'] ?? null),
            $statusLabels[$row['status']] ?? $row['status'],
        ];
    }

    admin_report_send_excel(
        'kvalifikaciya_' . $year,
        ['Сотрудник', 'Должность', 'Последний курс', 'Окончание', 'Следующий срок', 'Статус'],
        $rows
    );
}
