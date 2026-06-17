<!-- http://localhost/dou/test_db.php  -->
<?php
require_once 'includes/config.php';

echo "<h2>🔍 Проверка базы данных 'dou'</h2>";

try {
    // 1. Общая информация
    echo "<h4>✅ Подключение к базе данных: <span style='color:green;'>УСПЕШНО</span></h4>";

    // 2. Список всех таблиц
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h5>Таблиц в базе: " . count($tables) . "</h5>";
    echo "<ul>";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<li><b>$table</b> — $count записей</li>";
    }
    echo "</ul>";

    // 3. Пользователи
    echo "<h5>👤 Пользователи:</h5>";
    $users = $pdo->query("SELECT id, username, full_name, role_id FROM users LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($users);
    echo "</pre>";

    // 4. Группы
    echo "<h5>👥 Группы:</h5>";
    $groups = $pdo->query("SELECT id, name, age_category FROM groups")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($groups);
    echo "</pre>";

    // 5. Дети
    echo "<h5>👶 Дети:</h5>";
    $children = $pdo->query("SELECT id, full_name, group_id FROM children LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($children);
    echo "</pre>";

} catch (Exception $e) {
    echo "<h3 style='color:red;'>❌ Ошибка: " . $e->getMessage() . "</h3>";
}
?>
