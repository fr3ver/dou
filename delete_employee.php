<?php

require_once '_auth.php';

require_once '_employee_helpers.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: employees.php');

    exit;

}



$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {

    header('Location: employees.php');

    exit;

}



$stmt = $pdo->prepare('SELECT e.*, u.full_name FROM employees e JOIN users u ON e.user_id = u.id WHERE e.id = ?');

$stmt->execute([$id]);

$emp = $stmt->fetch(PDO::FETCH_ASSOC);



if (!$emp) {

    admin_flash('error', 'Сотрудник не найден');

    header('Location: employees.php');

    exit;

}



if ((int)$emp['user_id'] === (int)$_SESSION['user_id']) {

    admin_flash('error', 'Нельзя удалить свой аккаунт');

    header('Location: employees.php');

    exit;

}



try {

    $pdo->beginTransaction();



    if ($emp['position'] === 'Воспитатель' && $emp['group_id']) {

        $check = $pdo->prepare("SELECT user_id FROM employees WHERE group_id = ? AND position = 'Воспитатель' LIMIT 1");

        $check->execute([(int)$emp['group_id']]);

        if ((int)$check->fetchColumn() === (int)$emp['user_id']) {

            admin_assign_teacher($pdo, (int)$emp['group_id'], null);

        }

    }



    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$emp['user_id']]);



    $pdo->commit();

    admin_flash('success', 'Сотрудник «' . $emp['full_name'] . '» удалён');

} catch (Exception $e) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }

    admin_flash('error', $e->getMessage());

}



header('Location: employees.php');

exit;

