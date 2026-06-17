<?php

/** Период между повышениями квалификации (лет) — по практике аттестации педагогов */
const QUALIFICATION_CYCLE_YEARS = 3;

/** За сколько дней до срока показывать напоминание */
const QUALIFICATION_REMINDER_DAYS = 90;

function admin_qualification_select_columns(): string
{
    return 'id, qual_course_title, qual_organization, qual_completed_on, qual_next_due_on,
            qual_hours, qual_certificate_no, qual_notes, qualification_upgrades';
}

/** @return array<string, mixed>|null */
function admin_qualification_course_from_row(array $row): ?array
{
    $title = trim((string)($row['qual_course_title'] ?? ''));
    $completedOn = trim((string)($row['qual_completed_on'] ?? ''));
    if ($title === '' || $completedOn === '') {
        return null;
    }

    return [
        'id'            => (int)$row['id'],
        'employee_id'   => (int)$row['id'],
        'title'         => $title,
        'organization'  => $row['qual_organization'] ?? null,
        'completed_on'  => $completedOn,
        'next_due_on'   => $row['qual_next_due_on'] ?? null,
        'hours'         => $row['qual_hours'] ?? null,
        'certificate_no'=> $row['qual_certificate_no'] ?? null,
        'notes'         => $row['qual_notes'] ?? null,
    ];
}

function admin_qualification_calc_next_due(string $completedOn): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $completedOn);
    if (!$dt) {
        return $completedOn;
    }
    $dt->modify('+' . QUALIFICATION_CYCLE_YEARS . ' years');

    return $dt->format('Y-m-d');
}

/** @return list<array<string, mixed>> */
function admin_qualification_upgrades_for_employee(PDO $pdo, int $employeeId): array
{
    $stmt = $pdo->prepare('SELECT ' . admin_qualification_select_columns() . ' FROM employees WHERE id = ?');
    $stmt->execute([$employeeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return [];
    }

    $course = admin_qualification_course_from_row($row);

    return $course !== null ? [$course] : [];
}

function admin_qualification_latest_due(PDO $pdo, int $employeeId): ?array
{
    $courses = admin_qualification_upgrades_for_employee($pdo, $employeeId);
    if ($courses === []) {
        return null;
    }

    return [
        'completed_on' => $courses[0]['completed_on'],
        'next_due_on'  => $courses[0]['next_due_on'],
    ];
}

/** @return 'none'|'ok'|'soon'|'overdue' */
function admin_qualification_status(?string $nextDueOn): string
{
    if (!$nextDueOn) {
        return 'none';
    }

    $today = new DateTimeImmutable('today');
    $due = DateTimeImmutable::createFromFormat('Y-m-d', $nextDueOn);
    if (!$due) {
        return 'none';
    }

    if ($due < $today) {
        return 'overdue';
    }

    $warn = $today->modify('+' . QUALIFICATION_REMINDER_DAYS . ' days');
    if ($due <= $warn) {
        return 'soon';
    }

    return 'ok';
}

function admin_qualification_status_label(string $status): string
{
    return match ($status) {
        'overdue' => 'Просрочено',
        'soon'    => 'Скоро срок',
        'ok'      => 'В норме',
        default   => 'Нет данных',
    };
}

function admin_qualification_status_badge(string $status): string
{
    $class = match ($status) {
        'overdue' => 'bg-danger',
        'soon'    => 'bg-warning text-dark',
        'ok'      => 'bg-success',
        default   => 'bg-secondary',
    };

    return '<span class="badge ' . $class . '">' . admin_qualification_status_label($status) . '</span>';
}

function admin_qualification_format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $dt = DateTime::createFromFormat('Y-m-d', $date);

    return $dt ? $dt->format('d.m.Y') : $date;
}

function admin_qualification_sync_public_text(PDO $pdo, int $employeeId): void
{
    $course = admin_qualification_upgrades_for_employee($pdo, $employeeId)[0] ?? null;
    $text = null;

    if ($course !== null) {
        $line = '«' . $course['title'] . '»';
        if (!empty($course['completed_on'])) {
            $line .= ', ' . substr($course['completed_on'], 0, 4) . ' г.';
        }
        $text = $line;
    }

    $pdo->prepare('UPDATE employees SET qualification_upgrades = ? WHERE id = ?')
        ->execute([$text, $employeeId]);
}

function admin_qualification_sync_all_public_text(PDO $pdo): int
{
    $ids = $pdo->query("
        SELECT id FROM employees
        WHERE qual_course_title IS NOT NULL AND qual_course_title <> ''
          AND qual_completed_on IS NOT NULL
    ")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($ids as $id) {
        admin_qualification_sync_public_text($pdo, (int)$id);
    }

    return count($ids);
}

function admin_qualification_employees_overview(PDO $pdo): array
{
    $rows = $pdo->query("
        SELECT e.id AS employee_id, e.position, u.full_name,
               e.qual_course_title AS last_course,
               e.qual_completed_on AS completed_on,
               e.qual_next_due_on AS next_due_on
        FROM employees e
        JOIN users u ON u.id = e.user_id
        ORDER BY u.full_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['status'] = admin_qualification_status($row['next_due_on'] ?? null);
    }
    unset($row);

    return $rows;
}

function admin_qualification_reminders(PDO $pdo): array
{
    $overview = admin_qualification_employees_overview($pdo);

    return array_values(array_filter(
        $overview,
        static fn(array $row): bool => in_array($row['status'], ['soon', 'overdue'], true)
    ));
}

function admin_qualification_save(PDO $pdo, array $data): int
{
    $employeeId = (int)($data['employee_id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $organization = trim($data['organization'] ?? '') ?: null;
    $completedOn = trim($data['completed_on'] ?? '');
    $nextDueOn = trim($data['next_due_on'] ?? '') ?: null;
    $hours = (int)($data['hours'] ?? 0);
    $certificateNo = trim($data['certificate_no'] ?? '') ?: null;
    $notes = trim($data['notes'] ?? '') ?: null;

    if ($employeeId <= 0 || $title === '' || $completedOn === '') {
        throw new InvalidArgumentException('Заполните сотрудника, название курса и дату окончания.');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $completedOn)) {
        throw new InvalidArgumentException('Некорректная дата окончания.');
    }

    if (!$nextDueOn) {
        $nextDueOn = admin_qualification_calc_next_due($completedOn);
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextDueOn)) {
        throw new InvalidArgumentException('Некорректная дата следующего повышения.');
    }

    $hoursVal = $hours > 0 ? $hours : null;

    $pdo->prepare('
        UPDATE employees SET
            qual_course_title = ?, qual_organization = ?, qual_completed_on = ?,
            qual_next_due_on = ?, qual_hours = ?, qual_certificate_no = ?, qual_notes = ?
        WHERE id = ?
    ')->execute([
        $title, $organization, $completedOn,
        $nextDueOn, $hoursVal, $certificateNo, $notes, $employeeId,
    ]);

    admin_qualification_sync_public_text($pdo, $employeeId);

    return $employeeId;
}

function admin_qualification_delete(PDO $pdo, int $employeeId): void
{
    $stmt = $pdo->prepare('SELECT id FROM employees WHERE id = ?');
    $stmt->execute([$employeeId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Сотрудник не найден.');
    }

    $pdo->prepare('
        UPDATE employees SET
            qual_course_title = NULL, qual_organization = NULL, qual_completed_on = NULL,
            qual_next_due_on = NULL, qual_hours = NULL, qual_certificate_no = NULL, qual_notes = NULL
        WHERE id = ?
    ')->execute([$employeeId]);

    admin_qualification_sync_public_text($pdo, $employeeId);
}

function admin_qualification_employee_exists(PDO $pdo, int $employeeId): ?array
{
    $stmt = $pdo->prepare('
        SELECT e.id, e.position, u.full_name
        FROM employees e
        JOIN users u ON u.id = e.user_id
        WHERE e.id = ?
    ');
    $stmt->execute([$employeeId]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/** @return list<array> */
function admin_qualification_courses_in_year(PDO $pdo, int $year): array
{
    $stmt = $pdo->prepare("
        SELECT e.id AS employee_id, e.position, u.full_name,
               e.qual_course_title AS title, e.qual_organization AS organization,
               e.qual_completed_on AS completed_on, e.qual_next_due_on AS next_due_on,
               e.qual_hours AS hours, e.qual_certificate_no AS certificate_no, e.qual_notes AS notes
        FROM employees e
        JOIN users u ON u.id = e.user_id
        WHERE e.qual_completed_on IS NOT NULL
          AND YEAR(e.qual_completed_on) = ?
        ORDER BY e.qual_completed_on DESC, u.full_name ASC
    ");
    $stmt->execute([$year]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * @return array{total: int, ok: int, soon: int, overdue: int, none: int, courses_in_year: int, hours_in_year: int}
 */
function admin_qualification_report_stats(PDO $pdo, int $year): array
{
    $employees = admin_qualification_employees_overview($pdo);
    $stats = [
        'total' => count($employees),
        'ok' => 0,
        'soon' => 0,
        'overdue' => 0,
        'none' => 0,
        'courses_in_year' => 0,
        'hours_in_year' => 0,
    ];

    foreach ($employees as $row) {
        $status = $row['status'] ?? 'none';
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS course_count, COALESCE(SUM(qual_hours), 0) AS hour_sum
        FROM employees
        WHERE qual_completed_on IS NOT NULL AND YEAR(qual_completed_on) = ?
    ");
    $stmt->execute([$year]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stats['courses_in_year'] = (int)($row['course_count'] ?? 0);
    $stats['hours_in_year'] = (int)($row['hour_sum'] ?? 0);

    return $stats;
}

/** @return list<array> */
function admin_qualification_filter_overview(array $rows, string $statusFilter): array
{
    if ($statusFilter === '' || $statusFilter === 'all') {
        return $rows;
    }

    return array_values(array_filter(
        $rows,
        static fn(array $row): bool => ($row['status'] ?? 'none') === $statusFilter
    ));
}
