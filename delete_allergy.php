<?php

require_once '_auth.php';
require_once __DIR__ . '/../includes/allergies.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: allergies.php');

    exit;

}



$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {

    header('Location: allergies.php');

    exit;

}



$used = count_children_with_allergy($pdo, $id);



if ($used > 0) {

    admin_flash('error', 'Нельзя удалить: аллерген назначен ' . $used . ' детям');

    header('Location: allergies.php');

    exit;

}



try {

    $pdo->prepare('DELETE FROM allergies WHERE id = ?')->execute([$id]);

    admin_flash('success', 'Аллерген удалён');

} catch (Exception $e) {

    admin_flash('error', $e->getMessage());

}



header('Location: allergies.php');

exit;

