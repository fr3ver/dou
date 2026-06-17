<?php

function role_labels(): array
{
    return [
        1 => 'Родитель',
        2 => 'Сотрудник',
        3 => 'Администратор',
        4 => 'Старший воспитатель',
    ];
}

/** Роли с доступом в админ-панель */
function admin_panel_role_ids(): array
{
    return [3, 4];
}

function role_is_admin_panel(int $roleId): bool
{
    return in_array($roleId, admin_panel_role_ids(), true);
}

/** Формирование отчётов — только администратор (заведующий) */
function role_can_access_reports(int $roleId): bool
{
    return $roleId === 3;
}

/** Учёт повышения квалификации — заведующий и старший воспитатель */
function role_can_access_qualifications(int $roleId): bool
{
    return in_array($roleId, [3, 4], true);
}

/** Меню питания — заведующий и старший воспитатель */
function role_can_edit_menu(int $roleId): bool
{
    return in_array($roleId, [3, 4], true);
}

function role_label(int $roleId): string
{
    return role_labels()[$roleId] ?? '—';
}

function role_sync_names(PDO $pdo): void
{
    $labels = role_labels();
    $stmt = $pdo->prepare('UPDATE roles SET name = ? WHERE id = ?');
    foreach ($labels as $id => $name) {
        $stmt->execute([$name, $id]);
    }
}
