<?php

require_once '_auth.php';

if (!role_can_access_reports((int)$_SESSION['role_id'])) {
    http_response_code(403);
    exit('Доступ запрещён');
}

require_once '_report_export.php';

$report = $_GET['report'] ?? '';
$allowedReports = ['attendance', 'payment', 'tnr', 'clubs', 'menu', 'events', 'qualifications'];

if (!in_array($report, $allowedReports, true)) {
    http_response_code(400);
    exit('Неизвестный отчёт');
}

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}
$monthStart = $month . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));
$groupId = (int) ($_GET['group_id'] ?? 0);
$qualYear = (int) ($_GET['year'] ?? date('Y'));
$qualStatus = trim($_GET['qual_status'] ?? 'all');
$allowedQualStatuses = ['all', 'ok', 'soon', 'overdue', 'none'];
if (!in_array($qualStatus, $allowedQualStatuses, true)) {
    $qualStatus = 'all';
}

match ($report) {
    'attendance' => admin_report_export_attendance($pdo, $monthStart, $monthEnd),
    'payment' => admin_report_export_payment($pdo, $monthStart, $monthEnd, $groupId),
    'tnr' => admin_report_export_tnr($pdo, $month),
    'clubs' => admin_report_export_clubs($pdo, $monthStart, $monthEnd),
    'menu' => admin_report_export_menu($pdo, $monthStart, $monthEnd, $groupId),
    'events' => admin_report_export_events($pdo, $monthStart, $monthEnd),
    'qualifications' => admin_report_export_qualifications($pdo, $qualYear, $qualStatus),
    default => exit('Неизвестный отчёт'),
};
