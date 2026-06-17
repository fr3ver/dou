<?php

require_once '_auth.php';

require_once __DIR__ . '/../includes/entity_groups.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: events.php'); exit; }



$id             = (int)($_POST['id'] ?? 0);

$title          = trim($_POST['title'] ?? '');

$description    = trim($_POST['description'] ?? '');

$event_date     = $_POST['event_date'] ?? '';

$event_time     = trim($_POST['event_time'] ?? '');

$location       = trim($_POST['location'] ?? '');

$status         = $_POST['status'] ?? 'active';

$for_all_groups = isset($_POST['for_all_groups']) ? 1 : 0;

$postponed_to   = $_POST['postponed_to'] ?? null;

$group_ids      = $for_all_groups ? [] : entity_groups_collect_post_ids();



if ($id <= 0 || empty($title) || empty($event_date)) {

    admin_flash('error', 'Заполните обязательные поля');

    header('Location: edit_event.php?id=' . $id); exit;

}



$allowed = ['active', 'finished', 'cancelled', 'postponed'];

if (!in_array($status, $allowed, true)) $status = 'active';



try {

    $targetGroupIds = $for_all_groups ? null : entity_groups_json($group_ids);

    $stmt = $pdo->prepare("UPDATE events SET
        title=?, description=?, event_date=?, event_time=?, location=?, status=?, postponed_to=?, for_all_groups=?, target_group_ids=?
        WHERE id=?");

    $stmt->execute([
        $title, $description ?: null, $event_date, $event_time ?: null, $location ?: null,
        $status,
        ($status === 'postponed' && $postponed_to) ? $postponed_to : null, $for_all_groups, $targetGroupIds, $id,
    ]);

    admin_flash('success', 'Мероприятие обновлено');

} catch (Exception $e) {

    admin_flash('error', $e->getMessage());

}

header('Location: edit_event.php?id=' . $id);

exit;

