<?php

/**
 * Заведующий для блока «Наш коллектив» (без записи в employees).
 * Имя и фото подтягиваются из учётной записи администратора.
 */
function public_team_director(PDO $pdo): ?array
{
    $stmt = $pdo->query("
        SELECT u.id, u.full_name, u.photo_url
        FROM users u
        WHERE u.role_id = 3
          AND u.id NOT IN (
              SELECT user_id FROM employees WHERE position = 'Старший воспитатель'
          )
        ORDER BY u.id
        LIMIT 1
    ");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        return null;
    }

    return [
        'id'                           => 'director-' . (int)$admin['id'],
        'full_name'                    => $admin['full_name'],
        'position'                     => 'Заведующий',
        'group_name'                   => null,
        'photo_url'                    => $admin['photo_url'],
        'education'                    => 'Высшее педагогическое. БГПУ, дошкольная педагогика и психология, 2012 г.',
        'retraining'                   => '«Управление дошкольным образовательным учреждением», 2018 г.',
        'qualification_upgrades'       => '«Организация деятельности МДОУ в современных условиях», 2024 г.',
        'experience_total_years'       => 14,
        'experience_pedagogical_years' => 14,
        'experience_specialty_note'    => '8 лет',
        'is_director'                  => true,
    ];
}
