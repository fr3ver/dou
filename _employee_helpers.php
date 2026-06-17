<?php

function admin_employee_position_names(PDO $pdo): array
{
    static $defaults = [
        'Старший воспитатель',
        'Воспитатель',
        'Логопед',
        'Преподаватель английского языка',
        'Преподаватель изобразительного искусства',
        'Преподаватель музыки',
    ];

    try {
        $existing = $pdo->query("
            SELECT DISTINCT position FROM employees
            WHERE TRIM(position) <> ''
            ORDER BY position
        ")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $existing = [];
    }

    $excluded = ['Сотрудник'];

    $names = array_values(array_unique(array_merge($defaults, $existing)));
    $names = array_values(array_filter(
        $names,
        static fn(string $name): bool => !in_array($name, $excluded, true)
    ));
    sort($names, SORT_LOCALE_STRING);

    return $names;
}

function admin_role_id_for_position(string $position): int
{
    return $position === 'Старший воспитатель' ? 4 : 2;
}

function admin_position_skips_group(string $position): bool
{
    return $position === 'Старший воспитатель';
}

function admin_normalize_employee_group(string $position, ?int $group_id): ?int
{
    if (admin_position_skips_group($position)) {
        return null;
    }

    return $group_id > 0 ? $group_id : null;
}

function admin_position_is_valid(PDO $pdo, string $position, ?string $currentPosition = null): bool
{
    if ($currentPosition !== null && $position === $currentPosition) {
        return true;
    }

    return in_array($position, admin_employee_position_names($pdo), true);
}

function admin_sync_employee(PDO $pdo, int $employee_id, int $user_id, string $position, ?int $group_id, ?string $old_position = null, ?int $old_group_id = null): void
{
    if ($old_position === 'Воспитатель' && $old_group_id
        && ($position !== 'Воспитатель' || $old_group_id !== $group_id)) {
        $stmt = $pdo->prepare("SELECT user_id FROM employees WHERE group_id = ? AND position = 'Воспитатель' LIMIT 1");
        $stmt->execute([$old_group_id]);
        if ((int)$stmt->fetchColumn() === $user_id) {
            admin_assign_teacher($pdo, $old_group_id, null);
        }
    }

    if (admin_position_skips_group($position)) {
        $group_id = null;
    }

    if ($position === 'Воспитатель' && $group_id) {
        admin_assign_teacher($pdo, $group_id, $user_id);
        return;
    }

    $pdo->prepare('UPDATE employees SET position = ?, group_id = ? WHERE id = ?')
        ->execute([$position, $group_id, $employee_id]);
}

function admin_render_employee_position_options(PDO $pdo, ?string $selected = null): void
{
    $positions = admin_employee_position_names($pdo);

    if ($selected && !in_array($selected, $positions, true)) {
        echo '<option value="' . htmlspecialchars($selected) . '" selected>'
            . htmlspecialchars($selected) . ' (устаревшая)</option>';
    }

    foreach ($positions as $pos) {
        $sel = ($selected === $pos) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($pos) . '"' . $sel . '>'
            . htmlspecialchars($pos) . '</option>';
    }
}

function admin_render_employee_group_options(PDO $pdo, ?int $selected = null): void
{
    admin_render_group_select_options($pdo, $selected ?? 0, 'Не привязывать');
}

function admin_ensure_employee_record(PDO $pdo, int $user_id, string $position, ?int $group_id = null): int
{
    $stmt = $pdo->prepare('SELECT id FROM employees WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $pdo->prepare('UPDATE employees SET position = ?, group_id = ? WHERE user_id = ?')
            ->execute([$position, $group_id, $user_id]);
        return (int)$existing['id'];
    }

    $pdo->prepare('INSERT INTO employees (user_id, position, group_id) VALUES (?, ?, ?)')
        ->execute([$user_id, $position, $group_id]);
    return (int)$pdo->lastInsertId();
}

function admin_get_clubs(PDO $pdo): array
{
    return $pdo->query('SELECT id, name, schedule, teacher_id FROM clubs ORDER BY name')
        ->fetchAll(PDO::FETCH_ASSOC);
}

function admin_get_employee_club_ids(PDO $pdo, int $employeeId): array
{
    $stmt = $pdo->prepare('SELECT id FROM clubs WHERE teacher_id = ? ORDER BY name');
    $stmt->execute([$employeeId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function admin_collect_employee_club_ids(): array
{
    $ids = [];
    foreach ($_POST['club_ids'] ?? [] as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_values($ids);
}

function admin_sync_employee_clubs(PDO $pdo, int $employeeId, array $clubIds): void
{
    if ($clubIds === []) {
        $pdo->prepare('UPDATE clubs SET teacher_id = NULL WHERE teacher_id = ?')->execute([$employeeId]);
        return;
    }

    $placeholders = implode(',', array_fill(0, count($clubIds), '?'));
    $params = array_merge([$employeeId], $clubIds);

    $pdo->prepare("UPDATE clubs SET teacher_id = NULL WHERE teacher_id = ? AND id NOT IN ($placeholders)")
        ->execute($params);

    $assign = $pdo->prepare('UPDATE clubs SET teacher_id = ? WHERE id = ?');
    foreach ($clubIds as $clubId) {
        $assign->execute([$employeeId, (int)$clubId]);
    }
}

function admin_render_employee_club_fields(PDO $pdo, ?int $employeeId = null): void
{
    $clubs = admin_get_clubs($pdo);
    if ($clubs === []) {
        return;
    }

    $selected = $employeeId ? admin_get_employee_club_ids($pdo, $employeeId) : [];
    ?>
    <div class="mb-3" id="employee-clubs-wrap">
        <label class="form-label fw-semibold">Кружки (руководитель)</label>
        <div class="border rounded p-3 bg-light-subtle" style="max-height: 220px; overflow-y: auto;">
            <?php foreach ($clubs as $club):
                $otherTeacher = !empty($club['teacher_id']) && (int)$club['teacher_id'] !== (int)$employeeId;
            ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="club_ids[]"
                       value="<?= (int)$club['id'] ?>" id="emp-club-<?= (int)$club['id'] ?>"
                       <?= in_array((int)$club['id'], $selected, true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="emp-club-<?= (int)$club['id'] ?>">
                    <?= htmlspecialchars($club['name']) ?>
                    <?php if (!empty($club['schedule'])): ?>
                        <span class="text-muted small">(<?= htmlspecialchars($club['schedule']) ?>)</span>
                    <?php endif; ?>
                    <?php if ($otherTeacher): ?>
                        <span class="text-muted small">— уже назначен другой</span>
                    <?php endif; ?>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}


function admin_default_employee_profile(string $position): array
{
    if ($position === 'Старший воспитатель') {
        return [
            'education'                  => 'Высшее педагогическое. БГПУ, дошкольная педагогика и психология, 2010 г.',
            'retraining'               => '«Методическая работа в дошкольном образовательном учреждении», 2016 г.',
            'qualification_upgrades'   => '«Современные образовательные программы ДОУ и ФГОС ДО»; «Организация методической службы в МДОУ», 2023–2024 г.',
            'experience_total_years'   => 15,
            'experience_pedagogical_years' => 15,
            'experience_specialty_note'  => '10 лет',
            'show_on_public'           => 1,
        ];
    }

    return [
        'education'                  => null,
        'retraining'               => null,
        'qualification_upgrades'   => null,
        'experience_total_years'   => null,
        'experience_pedagogical_years' => null,
        'experience_specialty_note'  => null,
        'show_on_public'           => 1,
    ];
}

function admin_collect_employee_profile(?string $position = null): array
{
    $total = (int)($_POST['experience_total_years'] ?? 0);
    $ped = (int)($_POST['experience_pedagogical_years'] ?? 0);

    $profile = [
        'education'                  => trim($_POST['education'] ?? '') ?: null,
        'retraining'               => trim($_POST['retraining'] ?? '') ?: null,
        'experience_total_years'   => $total > 0 ? $total : null,
        'experience_pedagogical_years' => $ped > 0 ? $ped : null,
        'experience_specialty_note'  => trim($_POST['experience_specialty_note'] ?? '') ?: null,
        'show_on_public'           => !empty($_POST['show_on_public']) ? 1 : 0,
    ];

    if ($position === 'Старший воспитатель') {
        $defaults = admin_default_employee_profile($position);
        foreach ([
            'education', 'retraining',
            'experience_total_years', 'experience_pedagogical_years', 'experience_specialty_note',
        ] as $key) {
            if ($profile[$key] === null || $profile[$key] === '') {
                $profile[$key] = $defaults[$key];
            }
        }
        if (!isset($_POST['show_on_public'])) {
            $profile['show_on_public'] = 1;
        }
    }

    return $profile;
}

function admin_save_employee_profile(PDO $pdo, int $employeeId, array $profile): void
{
    $pdo->prepare('
        UPDATE employees SET
            education = ?, retraining = ?,
            experience_total_years = ?, experience_pedagogical_years = ?,
            experience_specialty_note = ?, show_on_public = ?
        WHERE id = ?
    ')->execute([
        $profile['education'],
        $profile['retraining'],
        $profile['experience_total_years'],
        $profile['experience_pedagogical_years'],
        $profile['experience_specialty_note'],
        $profile['show_on_public'],
        $employeeId,
    ]);
}

function admin_render_employee_profile_fields(?array $emp = null): void
{
    ?>
    <div class="mb-4 p-4 rounded-3 border bg-light-subtle">
        <h3 class="h6 fw-semibold mb-3">Профиль для сайта («Наш коллектив»)</h3>
        <div class="mb-3">
            <label class="form-label fw-semibold">Образование</label>
            <textarea name="education" class="form-control" rows="2"><?= htmlspecialchars($emp['education'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Переподготовка</label>
            <textarea name="retraining" class="form-control" rows="2"><?= htmlspecialchars($emp['retraining'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Повышение квалификации (на сайте)</label>
            <?php if (!empty($emp['id'])): ?>
            <div class="mb-2">
                <a href="employee_qualifications.php?id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-journal-plus me-1"></i>Курс и сроки
                </a>
            </div>
            <?php endif; ?>
            <textarea name="qualification_upgrades" class="form-control" rows="2" readonly
                      placeholder="Заполняется автоматически из учёта курса"><?= htmlspecialchars($emp['qualification_upgrades'] ?? '') ?></textarea>
            <div class="form-text">Текст для «Наш коллектив» обновляется при сохранении курса в разделе «Курс и сроки».</div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Общий стаж (лет)</label>
                <input type="number" name="experience_total_years" class="form-control" min="0" max="60"
                       value="<?= htmlspecialchars((string)($emp['experience_total_years'] ?? '')) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Педагогический стаж (лет)</label>
                <input type="number" name="experience_pedagogical_years" class="form-control" min="0" max="60"
                       value="<?= htmlspecialchars((string)($emp['experience_pedagogical_years'] ?? '')) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Стаж по специальности</label>
                <input type="text" name="experience_specialty_note" class="form-control" placeholder="менее года"
                       value="<?= htmlspecialchars($emp['experience_specialty_note'] ?? '') ?>">
            </div>
        </div>
        <div class="form-check">
            <input type="checkbox" name="show_on_public" value="1" id="show_on_public" class="form-check-input"
                   <?= !isset($emp['show_on_public']) || !empty($emp['show_on_public']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="show_on_public">Показывать на главной странице</label>
        </div>
    </div>
    <?php
}
