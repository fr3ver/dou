<?php

require_once __DIR__ . '/group_labels.php';

/**
 * Тарифы родительской платы (постановление Администрации г. Улан-Удэ от 27.06.2024 № 162).
 * total = питание + расходные материалы (гигиена).
 *
 * @return array<string, array<string, array{total: float, food: float, hygiene: float}>>
 */
function parent_fee_rates(): array
{
    return [
        'young_1_3' => [
            'round' => ['total' => 193.0, 'food' => 178.0, 'hygiene' => 15.0],
            '12h'   => ['total' => 177.0, 'food' => 162.0, 'hygiene' => 15.0],
            '8_10h' => ['total' => 160.0, 'food' => 145.0, 'hygiene' => 15.0],
            'short' => ['total' => 51.0,  'food' => 48.0,  'hygiene' => 3.0],
        ],
        'old_3_7' => [
            'round' => ['total' => 239.0, 'food' => 224.0, 'hygiene' => 15.0],
            '12h'   => ['total' => 219.0, 'food' => 204.0, 'hygiene' => 15.0],
            '8_10h' => ['total' => 199.0, 'food' => 184.0, 'hygiene' => 15.0],
            'short' => ['total' => 51.0,  'food' => 48.0,  'hygiene' => 3.0],
        ],
    ];
}

/** @return array<string, string> */
function parent_fee_care_mode_labels(): array
{
    return [
        'round' => 'Круглосуточное пребывание',
        '12h'   => '12-часовое пребывание',
        '8_10h' => '8–10-часовое пребывание',
        'short' => 'Кратковременное (до 5 ч)',
    ];
}

function parent_fee_care_mode_label(?string $mode): string
{
    return parent_fee_care_mode_labels()[$mode ?? ''] ?? '12-часовое пребывание';
}

function parent_fee_normalize_care_mode(?string $mode): string
{
    $mode = $mode ?? '12h';
    return array_key_exists($mode, parent_fee_care_mode_labels()) ? $mode : '12h';
}

function parent_fee_age_key(?string $ageCategory): string
{
    return dou_age_band($ageCategory) === 'nursery' ? 'young_1_3' : 'old_3_7';
}

function parent_fee_mode_rank(string $mode): int
{
    return match (parent_fee_normalize_care_mode($mode)) {
        'short' => 0,
        '8_10h' => 1,
        '12h'   => 2,
        'round' => 3,
        default => 2,
    };
}

/** Режим по фактическому времени пребывания (часы). */
function parent_fee_mode_by_hours(float $hours): string
{
    if ($hours <= 5.0) {
        return 'short';
    }
    if ($hours <= 10.0) {
        return '8_10h';
    }
    if ($hours <= 12.0) {
        return '12h';
    }

    return 'round';
}

function parent_fee_stay_hours(?string $arrival, ?string $departure): ?float
{
    if ($arrival === null || $arrival === '' || $departure === null || $departure === '') {
        return null;
    }

    $start = strtotime('1970-01-01 ' . substr($arrival, 0, 8));
    $end = strtotime('1970-01-01 ' . substr($departure, 0, 8));
    if ($start === false || $end === false || $end <= $start) {
        return null;
    }

    return ($end - $start) / 3600;
}

/**
 * Тариф за день: при раннем уходе — более низкий режим (п. 1 постановления).
 */
function parent_fee_effective_mode(string $contractedMode, ?string $arrival, ?string $departure, bool $present): string
{
    $contractedMode = parent_fee_normalize_care_mode($contractedMode);
    if (!$present) {
        return $contractedMode;
    }

    $hours = parent_fee_stay_hours($arrival, $departure);
    if ($hours === null) {
        return $contractedMode;
    }

    $actualMode = parent_fee_mode_by_hours($hours);

    return parent_fee_mode_rank($actualMode) < parent_fee_mode_rank($contractedMode)
        ? $actualMode
        : $contractedMode;
}

/** @return list<string> */
function parent_fee_billing_dates(string $monthStart, string $monthEnd): array
{
    $dates = [];
    $cur = new DateTimeImmutable($monthStart);
    $end = new DateTimeImmutable($monthEnd);

    while ($cur <= $end) {
        if ((int)$cur->format('N') <= 5) {
            $dates[] = $cur->format('Y-m-d');
        }
        $cur = $cur->modify('+1 day');
    }

    return $dates;
}

function parent_fee_format_time(?string $time): string
{
    if ($time === null || $time === '') {
        return '—';
    }

    return substr($time, 0, 5);
}

function parent_fee_format_rub(float $amount): string
{
    return number_format($amount, 2, ',', ' ') . ' ₽';
}

/** Размер родительской платы в день по договорному режиму группы (п. 1 постановления № 162). */
function parent_fee_contracted_daily_rate(?string $ageCategory, ?string $careMode): float
{
    $ageKey = parent_fee_age_key($ageCategory);
    $mode = parent_fee_normalize_care_mode($careMode);

    return parent_fee_rates()[$ageKey][$mode]['total'];
}

/** Ставки за день для режима (total, food, hygiene). */
function parent_fee_rates_for_day(?string $ageCategory, string $careMode): array
{
    $ageKey = parent_fee_age_key($ageCategory);
    $mode = parent_fee_normalize_care_mode($careMode);

    return parent_fee_rates()[$ageKey][$mode];
}

/**
 * @param array<string, array<string, mixed>> $attendanceByDate
 * @return array{
 *   present_days: int,
 *   absent_days: int,
 *   food_total: float,
 *   hygiene_total: float,
 *   care_total: float,
 *   total: float,
 *   days: list<array>
 * }
 */
function parent_fee_calculate_month(
    string $ageCategory,
    string $contractedMode,
    array $attendanceByDate,
    string $monthStart,
    string $monthEnd
): array {
    $ageKey = parent_fee_age_key($ageCategory);
    $contractedMode = parent_fee_normalize_care_mode($contractedMode);
    $ratesTable = parent_fee_rates();

    $presentDays = 0;
    $absentDays = 0;
    $foodTotal = 0.0;
    $hygieneTotal = 0.0;
    $total = 0.0;
    $days = [];

    foreach (parent_fee_billing_dates($monthStart, $monthEnd) as $date) {
        $row = $attendanceByDate[$date] ?? null;
        $present = $row !== null && ($row['status'] ?? '') === 'present';

        if ($present) {
            $presentDays++;
            $arrival = $row['arrival_time'] ?? null;
            $departure = $row['departure_time'] ?? null;
            $effectiveMode = parent_fee_effective_mode($contractedMode, $arrival, $departure, true);
            $dayRates = $ratesTable[$ageKey][$effectiveMode];
            $hours = parent_fee_stay_hours($arrival, $departure);

            $foodTotal += $dayRates['food'];
            $hygieneTotal += $dayRates['hygiene'];
            $dayTotal = $dayRates['total'];
        } else {
            if ($row !== null && ($row['status'] ?? '') === 'absent') {
                $absentDays++;
            }
            $dayRates = ['food' => 0.0, 'hygiene' => 0.0, 'total' => 0.0];
            $dayTotal = 0.0;
            $effectiveMode = null;
            $hours = null;
            $arrival = $row['arrival_time'] ?? null;
            $departure = $row['departure_time'] ?? null;
        }

        $total += $dayTotal;
        $days[] = [
            'date' => $date,
            'status' => $present ? 'present' : ($row['status'] ?? 'none'),
            'arrival_time' => $arrival,
            'departure_time' => $departure,
            'hours' => $hours,
            'effective_mode' => $effectiveMode,
            'day_rate' => $present ? $dayRates['total'] : 0.0,
            'food' => $dayRates['food'],
            'hygiene' => $dayRates['hygiene'],
            'day_total' => $dayTotal,
        ];
    }

    return [
        'present_days' => $presentDays,
        'absent_days' => $absentDays,
        'contracted_daily_rate' => parent_fee_contracted_daily_rate($ageCategory, $contractedMode),
        'food_total' => $foodTotal,
        'hygiene_total' => $hygieneTotal,
        'care_total' => $total - $foodTotal,
        'total' => $total,
        'days' => $days,
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function parent_fee_report_rows(PDO $pdo, string $monthStart, string $monthEnd, int $groupId = 0): array
{
    $sql = "
        SELECT c.id AS child_id, c.full_name, c.date_of_birth,
               g.id AS group_id, g.name AS group_name, g.age_category,
               COALESCE(g.care_mode, '12h') AS care_mode,
               p.full_name AS parent_name, p.phone AS parent_phone
        FROM children c
        JOIN `groups` g ON c.group_id = g.id
        LEFT JOIN users p ON c.parent_id = p.id
    ";
    $params = [];
    if ($groupId > 0) {
        $sql .= ' WHERE g.id = ?';
        $params[] = $groupId;
    }
    $sql .= ' ORDER BY g.name, c.full_name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($children === []) {
        return [];
    }

    $childIds = array_map(static fn($c) => (int)$c['child_id'], $children);
    $placeholders = implode(',', array_fill(0, count($childIds), '?'));
    $attStmt = $pdo->prepare("
        SELECT child_id, `date`, status, arrival_time, departure_time
        FROM attendance
        WHERE child_id IN ($placeholders)
          AND club_id = 0
          AND `date` BETWEEN ? AND ?
    ");
    $attStmt->execute(array_merge($childIds, [$monthStart, $monthEnd]));

    $attMap = [];
    foreach ($attStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $attMap[(int)$row['child_id']][$row['date']] = $row;
    }

    $rows = [];
    foreach ($children as $child) {
        $childId = (int)$child['child_id'];
        $calc = parent_fee_calculate_month(
            $child['age_category'] ?? '',
            $child['care_mode'] ?? '12h',
            $attMap[$childId] ?? [],
            $monthStart,
            $monthEnd
        );

        $rows[] = array_merge($child, $calc);
    }

    return $rows;
}
