<?php

require_once '_auth.php';

require_once __DIR__ . '/../includes/entity_groups.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: add_event.php'); exit; }



$title          = trim($_POST['title'] ?? '');

$description    = trim($_POST['description'] ?? '');

$event_date     = $_POST['event_date'] ?? '';

$event_time     = trim($_POST['event_time'] ?? '');

$location       = trim($_POST['location'] ?? '');

$status         = $_POST['status'] ?? 'active';

$for_all_groups = isset($_POST['for_all_groups']) ? 1 : 0;

$postponed_to   = $_POST['postponed_to'] ?? null;

$group_ids      = $for_all_groups ? [] : entity_groups_collect_post_ids();



if (empty($title) || empty($event_date)) {

    admin_flash('error', 'Укажите название и дату');

    header('Location: add_event.php'); exit;

}



$allowed = ['active', 'finished', 'cancelled', 'postponed'];

if (!in_array($status, $allowed, true)) $status = 'active';



try {

    $targetGroupIds = $for_all_groups ? null : entity_groups_json($group_ids);

    $stmt = $pdo->prepare("INSERT INTO events
        (title, description, event_date, event_time, location, status, created_by, postponed_to, for_all_groups, target_group_ids)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $title, $description ?: null, $event_date, $event_time ?: null, $location ?: null,
        $status, $_SESSION['user_id'],
        ($status === 'postponed' && $postponed_to) ? $postponed_to : null, $for_all_groups, $targetGroupIds,
    ]);

    admin_flash('success', 'Мероприятие создано');

    header('Location: events.php');

} catch (Exception $e) {

    admin_flash('error', $e->getMessage());

    header('Location: add_event.php');

}

exit;

