<?php

require_once '_auth.php';

require_once '_qualification_helpers.php';



if (!role_can_access_qualifications((int)$_SESSION['role_id'])) {

    admin_flash('danger', 'Нет доступа.');

    header('Location: dashboard.php');

    exit;

}



$employeeId = (int)($_POST['employee_id'] ?? 0);



try {

    admin_qualification_delete($pdo, $employeeId);

    admin_flash('success', 'Данные о курсе удалены.');

} catch (Throwable $e) {

    admin_flash('danger', $e->getMessage());

}



$redirect = $employeeId > 0

    ? 'employee_qualifications.php?id=' . $employeeId

    : 'qualifications.php';

header('Location: ' . $redirect);

exit;

