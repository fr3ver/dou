<?php

require_once '_auth.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: allergies.php');

    exit;

}



$name = trim($_POST['name'] ?? '');

if ($name === '') {

    admin_flash('error', 'Укажите название');

    header('Location: allergies.php');

    exit;

}



try {

    $pdo->prepare('INSERT INTO allergies (name) VALUES (?)')->execute([$name]);

    admin_flash('success', 'Аллерген «' . $name . '» добавлен');

} catch (PDOException $e) {

    admin_flash('error', str_contains($e->getMessage(), 'Duplicate') ? 'Такой аллерген уже есть' : $e->getMessage());

}



header('Location: allergies.php');

exit;

