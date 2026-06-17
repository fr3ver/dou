<?php

/** Список родителей и поле выбора с поиском по ФИО (Tom Select в админке). */

function admin_render_parent_select(PDO $pdo, int $selectedId = 0, bool $withEmpty = true): void
{
    $parents = $pdo->query(
        'SELECT id, full_name FROM users WHERE role_id = 1 ORDER BY full_name'
    )->fetchAll(PDO::FETCH_ASSOC);

    echo '<div class="parent-select-wrap">';
    echo '<select name="parent_id" class="form-select-parent" required'
        . ($withEmpty && $selectedId === 0 ? ' data-placeholder="1"' : '')
        . '>';
    foreach ($parents as $p) {
        $id = (int) $p['id'];
        $sel = $selectedId === $id ? ' selected' : '';
        echo '<option value="' . $id . '"' . $sel . '>'
            . htmlspecialchars($p['full_name']) . '</option>';
    }
    echo '</select></div>';
}
