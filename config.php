<?php

$localConfigFile = __DIR__ . '/config.local.php';
$local = is_file($localConfigFile) ? require $localConfigFile : [];
if (!is_array($local)) {
    $local = [];
}

/**
 * Путь приложения от корня сайта: / или /dou/ и т.д.
 */
function app_base_path(): string
{
    if (PHP_SAPI === 'cli') {
        return '/dou/';
    }

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $appRoot = realpath(dirname(__DIR__));
    if ($docRoot && $appRoot) {
        $docRoot = str_replace('\\', '/', $docRoot);
        $appRoot = str_replace('\\', '/', $appRoot);
        if (str_starts_with($appRoot, $docRoot)) {
            $rel = substr($appRoot, strlen($docRoot));
            $rel = '/' . trim($rel, '/');
            return $rel === '/' ? '/' : $rel . '/';
        }
    }

    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (str_ends_with($scriptDir, '/admin') || str_ends_with($scriptDir, '/parent')
        || str_ends_with($scriptDir, '/employee') || str_ends_with($scriptDir, '/api')
        || str_ends_with($scriptDir, '/chat')) {
        $scriptDir = dirname($scriptDir);
    }
    if (str_ends_with($scriptDir, '/includes')) {
        $scriptDir = dirname($scriptDir);
    }

    return ($scriptDir === '' || $scriptDir === '/') ? '/' : rtrim($scriptDir, '/') . '/';
}

function app_base_url(): string
{
    if (!empty($GLOBALS['local']['base_url'])) {
        return rtrim((string)$GLOBALS['local']['base_url'], '/') . '/';
    }

    if (PHP_SAPI === 'cli') {
        return 'http://localhost/dou/';
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . app_base_path();
}

$GLOBALS['local'] = $local;

$host = $local['db_host'] ?? 'localhost';
$dbname = $local['db_name'] ?? 'dou';
$dbuser = $local['db_user'] ?? 'root';
$dbpass = $local['db_pass'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

date_default_timezone_set('Asia/Irkutsk');

define('SITE_NAME', 'Детский сад «Радуга»');
define('SITE_ADDRESS', 'Республика Бурятия, г. Улан-Удэ, ул. Хахалова, 3 «А»');
define('SITE_PHONE', '8 (950) 388-78-33');
define('SITE_PHONE_TEL', '+79503887833');
define('SITE_EMAIL', 'raduga@gmail.com');
define('BASE_URL', app_base_url());
define('APP_BASE_PATH', app_base_path());
define('ENROLLMENT_URL', 'https://sad.obr03.ru/?once=bAD30szCOYJzLrAAJ989la10w7l5yBy13e5SskDFSKJ91PIyp__ifi-TT0HuP9oSzQtC_b1o99NN50j0w3mNr7yd51Y#/');
define('MAP_LAT', 51.847363);
define('MAP_LON', 107.629608);
define('MAP_ZOOM', 17);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => APP_BASE_PATH,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => PHP_SAPI !== 'cli' && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}
