<?php

require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/lk_helpers.php';

function chat_allowed_role_ids(): array
{
    return [1, 2, 3, 4];
}

function chat_require_auth(): void
{
    if (!isset($_SESSION['user_id']) || !in_array((int)($_SESSION['role_id'] ?? 0), chat_allowed_role_ids(), true)) {
        header('Location: ../login.php?next=chat/index.php');
        exit;
    }
}

function chat_json_auth(): void
{
    if (!isset($_SESSION['user_id']) || !in_array((int)($_SESSION['role_id'] ?? 0), chat_allowed_role_ids(), true)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Требуется авторизация'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function chat_is_parent(int $roleId): bool
{
    return $roleId === 1;
}

function chat_is_employee(int $roleId): bool
{
    return $roleId === 2;
}

function chat_is_administration(int $roleId): bool
{
    return role_is_admin_panel($roleId);
}

function chat_conv_type_labels(): array
{
    return [
        'teacher'         => 'Воспитатель',
        'club_teacher'    => 'Кружок',
        'admin'           => 'Администрация',
        'group_broadcast' => 'Общий чат группы',
        'club_broadcast'  => 'Общий чат кружка',
    ];
}

/** @return array{conv_type: string, parent_user_id: int, group_id: int} */
function chat_conv_parts(string $type, ?int $parentUserId, ?int $groupId): array
{
    return [
        'conv_type'      => $type,
        'parent_user_id' => max(0, (int)($parentUserId ?? 0)),
        'group_id'       => max(0, (int)($groupId ?? 0)),
    ];
}

function chat_conv_key(array $conv): string
{
    return implode(':', [
        $conv['conv_type'],
        (int)($conv['parent_user_id'] ?? 0),
        (int)($conv['group_id'] ?? 0),
    ]);
}

/** @return array{conv_type: string, parent_user_id: int, group_id: int}|null */
function chat_parse_conv_key(string $key): ?array
{
    $parts = explode(':', $key, 3);
    if (count($parts) !== 3) {
        return null;
    }

    $type = $parts[0];
    if (!isset(chat_conv_type_labels()[$type])) {
        return null;
    }

    return chat_conv_parts($type, (int)$parts[1], (int)$parts[2]);
}

function chat_conv_where_sql(string $alias = ''): string
{
    $p = $alias !== '' ? $alias . '.' : '';

    return "{$p}conv_type = ? AND {$p}parent_user_id = ? AND {$p}group_id = ?";
}

/** @param list<int|string> $params */
function chat_conv_bind(array &$params, array $conv): void
{
    $params[] = $conv['conv_type'];
    $params[] = (int)$conv['parent_user_id'];
    $params[] = (int)$conv['group_id'];
}

function chat_employee_group_id(PDO $pdo, int $userId): ?int
{
    $stmt = $pdo->prepare("
        SELECT group_id FROM employees
        WHERE user_id = ? AND group_id IS NOT NULL
          AND position IN ('Воспитатель', 'Логопед')
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $groupId = $stmt->fetchColumn();

    return $groupId !== false ? (int)$groupId : null;
}

function chat_employee_can_access_group(PDO $pdo, int $userId, int $groupId): bool
{
    if ($groupId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare("
        SELECT 1 FROM employees
        WHERE user_id = ? AND group_id = ?
          AND position IN ('Воспитатель', 'Логопед')
        LIMIT 1
    ");
    $stmt->execute([$userId, $groupId]);

    return (bool)$stmt->fetchColumn();
}

function chat_employee_can_access_club(PDO $pdo, int $userId, int $clubId): bool
{
    if ($clubId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare('
        SELECT 1 FROM clubs c
        JOIN employees e ON c.teacher_id = e.id
        WHERE c.id = ? AND e.user_id = ?
        LIMIT 1
    ');
    $stmt->execute([$clubId, $userId]);

    return (bool)$stmt->fetchColumn();
}

function chat_parent_has_child_in_club(PDO $pdo, int $parentUserId, int $clubId): bool
{
    if ($clubId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare("
        SELECT 1 FROM club_members cm
        JOIN children ch ON cm.child_id = ch.id
        WHERE cm.club_id = ? AND ch.parent_id = ? AND cm.status = 'enrolled'
        LIMIT 1
    ");
    $stmt->execute([$clubId, $parentUserId]);

    return (bool)$stmt->fetchColumn();
}

function chat_parent_group_ids(PDO $pdo, int $parentUserId): array
{
    return lk_parent_group_ids(lk_parent_children($pdo, $parentUserId));
}

function chat_parent_has_child_in_group(PDO $pdo, int $parentUserId, int $groupId): bool
{
    if ($groupId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT 1 FROM children WHERE parent_id = ? AND group_id = ? LIMIT 1');
    $stmt->execute([$parentUserId, $groupId]);

    return (bool)$stmt->fetchColumn();
}

function chat_user_can_access(PDO $pdo, int $userId, int $roleId, array $conv): bool
{
    $type = $conv['conv_type'] ?? '';
    $parentId = (int)($conv['parent_user_id'] ?? 0);
    $groupId = (int)($conv['group_id'] ?? 0);

    if (chat_is_administration($roleId)) {
        return $type === 'admin';
    }

    return match ($type) {
        'teacher' => chat_is_parent($roleId) && $userId === $parentId
            || chat_is_employee($roleId) && chat_employee_can_access_group($pdo, $userId, $groupId),
        'club_teacher' => chat_is_parent($roleId) && $userId === $parentId
            && chat_parent_has_child_in_club($pdo, $userId, $groupId)
            || chat_is_employee($roleId) && chat_employee_can_access_club($pdo, $userId, $groupId),
        'admin' => chat_is_parent($roleId) && $userId === $parentId,
        'group_broadcast' => chat_is_employee($roleId) && chat_employee_can_access_group($pdo, $userId, $groupId)
            || chat_is_parent($roleId) && chat_parent_has_child_in_group($pdo, $userId, $groupId),
        'club_broadcast' => chat_is_employee($roleId) && chat_employee_can_access_club($pdo, $userId, $groupId)
            || chat_is_parent($roleId) && chat_parent_has_child_in_club($pdo, $userId, $groupId),
        default => false,
    };
}

function chat_user_can_send(PDO $pdo, int $userId, int $roleId, array $conv): bool
{
    if (!chat_user_can_access($pdo, $userId, $roleId, $conv)) {
        return false;
    }

    if (chat_is_administration($roleId)) {
        return ($conv['conv_type'] ?? '') === 'admin';
    }

    $type = $conv['conv_type'] ?? '';

    return match ($type) {
        'teacher', 'club_teacher', 'admin' => true,
        'group_broadcast', 'club_broadcast' => chat_is_employee($roleId) || chat_is_parent($roleId),
        default => false,
    };
}

/** @return list<array{conv_type: string, parent_user_id: int, group_id: int}> */
function chat_virtual_conversations(PDO $pdo, int $userId, int $roleId): array
{
    $convs = [];
    $seen = [];

    $add = static function (array $conv) use (&$convs, &$seen): void {
        $key = chat_conv_key($conv);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $convs[] = $conv;
        }
    };

    if (chat_is_parent($roleId)) {
        $add(chat_conv_parts('admin', $userId, 0));
        foreach (chat_parent_group_ids($pdo, $userId) as $groupId) {
            $add(chat_conv_parts('teacher', $userId, $groupId));
            $add(chat_conv_parts('group_broadcast', 0, $groupId));
        }
        $stmt = $pdo->prepare("
            SELECT DISTINCT cm.club_id
            FROM club_members cm
            JOIN children ch ON cm.child_id = ch.id
            WHERE ch.parent_id = ? AND cm.status = 'enrolled'
        ");
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $clubId) {
            $add(chat_conv_parts('club_teacher', $userId, (int)$clubId));
            $add(chat_conv_parts('club_broadcast', 0, (int)$clubId));
        }
    } elseif (chat_is_employee($roleId)) {
        $groupId = chat_employee_group_id($pdo, $userId);
        if ($groupId !== null) {
            $add(chat_conv_parts('group_broadcast', 0, $groupId));
            $stmt = $pdo->prepare('SELECT DISTINCT parent_id FROM children WHERE group_id = ? AND parent_id IS NOT NULL');
            $stmt->execute([$groupId]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $parentId) {
                $add(chat_conv_parts('teacher', (int)$parentId, $groupId));
            }
        }

        $stmt = $pdo->prepare('SELECT id FROM employees WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $employeeId = (int)$stmt->fetchColumn();
        if ($employeeId > 0) {
            $clubStmt = $pdo->prepare('SELECT id FROM clubs WHERE teacher_id = ?');
            $clubStmt->execute([$employeeId]);
            foreach ($clubStmt->fetchAll(PDO::FETCH_COLUMN) as $clubId) {
                $clubId = (int)$clubId;
                $add(chat_conv_parts('club_broadcast', 0, $clubId));
                $parentStmt = $pdo->prepare("
                    SELECT DISTINCT ch.parent_id
                    FROM club_members cm
                    JOIN children ch ON cm.child_id = ch.id
                    WHERE cm.club_id = ? AND cm.status = 'enrolled' AND ch.parent_id IS NOT NULL
                ");
                $parentStmt->execute([$clubId]);
                foreach ($parentStmt->fetchAll(PDO::FETCH_COLUMN) as $parentId) {
                    $add(chat_conv_parts('club_teacher', (int)$parentId, $clubId));
                }
            }
        }
    } elseif (chat_is_administration($roleId)) {
        $stmt = $pdo->query('SELECT id FROM users WHERE role_id = 1');
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $parentId) {
            $add(chat_conv_parts('admin', (int)$parentId, 0));
        }
    }

    $rows = $pdo->query("
        SELECT DISTINCT conv_type, parent_user_id, group_id
        FROM chat
        WHERE row_kind = 'message'
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $conv = chat_conv_parts($row['conv_type'], (int)$row['parent_user_id'], (int)$row['group_id']);
        if (chat_user_can_access($pdo, $userId, $roleId, $conv)) {
            $add($conv);
        }
    }

    return $convs;
}

function chat_club_name(PDO $pdo, int $clubId): string
{
    if ($clubId <= 0) {
        return '';
    }

    $stmt = $pdo->prepare('SELECT name FROM clubs WHERE id = ?');
    $stmt->execute([$clubId]);
    $name = $stmt->fetchColumn();

    return $name !== false ? (string)$name : '';
}

function chat_club_teacher_name(PDO $pdo, int $clubId): string
{
    if ($clubId <= 0) {
        return 'Преподаватель кружка';
    }

    $stmt = $pdo->prepare('
        SELECT u.full_name
        FROM clubs c
        JOIN employees e ON c.teacher_id = e.id
        JOIN users u ON e.user_id = u.id
        WHERE c.id = ?
        LIMIT 1
    ');
    $stmt->execute([$clubId]);
    $name = $stmt->fetchColumn();

    return $name !== false && $name !== '' ? (string)$name : 'Преподаватель кружка';
}

function chat_parent_children_in_club(PDO $pdo, int $parentUserId, int $clubId): string
{
    $stmt = $pdo->prepare("
        SELECT ch.full_name
        FROM club_members cm
        JOIN children ch ON cm.child_id = ch.id
        WHERE cm.club_id = ? AND ch.parent_id = ? AND cm.status = 'enrolled'
        ORDER BY ch.full_name
    ");
    $stmt->execute([$clubId, $parentUserId]);
    $names = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $names !== [] ? implode(', ', $names) : '';
}

function chat_group_name(PDO $pdo, int $groupId): string
{
    if ($groupId <= 0) {
        return '';
    }

    $stmt = $pdo->prepare('SELECT name FROM `groups` WHERE id = ?');
    $stmt->execute([$groupId]);
    $name = $stmt->fetchColumn();

    return $name !== false ? (string)$name : '';
}

function chat_user_name(PDO $pdo, int $userId): string
{
    $stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $name = $stmt->fetchColumn();

    return $name !== false ? (string)$name : 'Пользователь';
}

function chat_conversation_title(PDO $pdo, array $conv, int $viewerUserId, int $viewerRoleId): string
{
    $type = $conv['conv_type'] ?? '';
    $labels = chat_conv_type_labels();

    return match ($type) {
        'teacher' => chat_teacher_conversation_title($pdo, $conv, $viewerUserId, $viewerRoleId),
        'club_teacher' => chat_club_teacher_conversation_title($pdo, $conv, $viewerUserId, $viewerRoleId),
        'admin' => chat_is_parent($viewerRoleId)
            ? 'Администрация'
            : chat_admin_conversation_title($pdo, (int)$conv['parent_user_id']),
        'group_broadcast' => 'Общий чат · ' . chat_group_name($pdo, (int)$conv['group_id']),
        'club_broadcast' => 'Общий чат · ' . chat_club_name($pdo, (int)$conv['group_id']),
        default => $labels[$type] ?? 'Чат',
    };
}

function chat_club_teacher_conversation_title(PDO $pdo, array $conv, int $viewerUserId, int $viewerRoleId): string
{
    $clubId = (int)$conv['group_id'];
    $clubName = chat_club_name($pdo, $clubId);

    if (chat_is_parent($viewerRoleId)) {
        $teacherName = chat_club_teacher_name($pdo, $clubId);

        return $teacherName . ($clubName !== '' ? ' · ' . $clubName : '');
    }

    return chat_user_name($pdo, (int)$conv['parent_user_id'])
        . ($clubName !== '' ? ' · ' . $clubName : '');
}

function chat_teacher_conversation_title(PDO $pdo, array $conv, int $viewerUserId, int $viewerRoleId): string
{
    $groupName = chat_group_name($pdo, (int)$conv['group_id']);

    if (chat_is_parent($viewerRoleId)) {
        $staff = lk_group_staff($pdo, (int)$conv['group_id']);
        $teacherName = $staff[0]['full_name'] ?? 'Воспитатель';

        return $teacherName . ($groupName !== '' ? ' · ' . $groupName : '');
    }

    return chat_user_name($pdo, (int)$conv['parent_user_id'])
        . ($groupName !== '' ? ' · ' . $groupName : '');
}

function chat_parent_children_list(PDO $pdo, int $parentUserId): string
{
    $children = lk_parent_children($pdo, $parentUserId);
    if ($children === []) {
        return '';
    }

    return implode(', ', array_map(
        static fn(array $c): string => (string)$c['full_name'],
        $children
    ));
}

function chat_admin_conversation_title(PDO $pdo, int $parentUserId): string
{
    $parentName = chat_user_name($pdo, $parentUserId);
    $children = lk_parent_children($pdo, $parentUserId);
    $groups = [];

    foreach ($children as $child) {
        if (!empty($child['group_name'])) {
            $groups[$child['group_name']] = true;
        }
    }

    $groupPart = implode(', ', array_keys($groups));
    if ($groupPart === '') {
        return $parentName;
    }

    return $parentName . ' · ' . $groupPart;
}

function chat_parent_children_summary(PDO $pdo, int $parentUserId): string
{
    $children = lk_parent_children($pdo, $parentUserId);
    if ($children === []) {
        return '';
    }

    $names = array_map(static fn(array $c): string => (string)$c['full_name'], $children);
    if (count($names) === 1) {
        return $names[0];
    }
    if (count($names) === 2) {
        return $names[0] . ', ' . $names[1];
    }

    return $names[0] . ', ' . $names[1] . '…';
}

function chat_conversation_subtitle(PDO $pdo, array $conv, int $viewerUserId, int $viewerRoleId): string
{
    $type = $conv['conv_type'] ?? '';

    return match ($type) {
        'admin' => chat_is_administration($viewerRoleId)
            ? chat_parent_children_list($pdo, (int)$conv['parent_user_id'])
            : 'Сообщения для администрации',
        'teacher' => chat_is_parent($viewerRoleId)
            ? chat_group_name($pdo, (int)$conv['group_id'])
            : chat_parent_children_summary($pdo, (int)$conv['parent_user_id']),
        'club_teacher' => chat_is_parent($viewerRoleId)
            ? chat_club_name($pdo, (int)$conv['group_id'])
            : chat_parent_children_in_club($pdo, (int)$conv['parent_user_id'], (int)$conv['group_id']),
        'group_broadcast' => chat_is_employee($viewerRoleId)
            ? 'Сообщение всем родителям группы'
            : chat_group_name($pdo, (int)$conv['group_id']),
        'club_broadcast' => chat_is_employee($viewerRoleId)
            ? 'Сообщение всем родителям кружка'
            : chat_club_name($pdo, (int)$conv['group_id']),
        default => '',
    };
}

function chat_last_message(PDO $pdo, array $conv): ?array
{
    $params = [];
    chat_conv_bind($params, $conv);

    $stmt = $pdo->prepare("
        SELECT c.id, c.body, c.created_at, c.user_id AS sender_id, u.full_name AS sender_name
        FROM chat c
        JOIN users u ON u.id = c.user_id
        WHERE c.row_kind = 'message' AND " . chat_conv_where_sql('c') . "
        ORDER BY c.id DESC
        LIMIT 1
    ");
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function chat_unread_count(PDO $pdo, array $conv, int $userId): int
{
    $params = [];
    chat_conv_bind($params, $conv);
    $params[] = $userId;

    $stmt = $pdo->prepare("
        SELECT created_at FROM chat
        WHERE row_kind = 'read' AND " . chat_conv_where_sql() . " AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute($params);
    $lastRead = $stmt->fetchColumn();

    $msgParams = [];
    chat_conv_bind($msgParams, $conv);
    $msgParams[] = $userId;

    if ($lastRead === false) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM chat
            WHERE row_kind = 'message' AND " . chat_conv_where_sql() . " AND user_id <> ?
        ");
        $stmt->execute($msgParams);

        return (int)$stmt->fetchColumn();
    }

    $msgParams[] = $lastRead;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM chat
        WHERE row_kind = 'message' AND " . chat_conv_where_sql() . "
          AND user_id <> ? AND created_at > ?
    ");
    $stmt->execute($msgParams);

    return (int)$stmt->fetchColumn();
}

function chat_total_unread(PDO $pdo, int $userId, int $roleId): int
{
    $total = 0;
    foreach (chat_list_conversations($pdo, $userId, $roleId) as $conv) {
        $total += (int)($conv['unread_count'] ?? 0);
    }

    return $total;
}

/** @return list<array<string, mixed>> */
function chat_list_conversations(PDO $pdo, int $userId, int $roleId): array
{
    $result = [];

    foreach (chat_virtual_conversations($pdo, $userId, $roleId) as $conv) {
        $last = chat_last_message($pdo, $conv);
        $updatedAt = $last['created_at'] ?? '1970-01-01 00:00:00';

        $result[] = [
            'id'           => chat_conv_key($conv),
            'conv_type'    => $conv['conv_type'],
            'title'        => chat_conversation_title($pdo, $conv, $userId, $roleId),
            'subtitle'     => chat_conversation_subtitle($pdo, $conv, $userId, $roleId),
            'updated_at'   => $updatedAt,
            'last_message' => $last ? [
                'body'        => $last['body'],
                'created_at'  => $last['created_at'],
                'sender_name' => $last['sender_name'],
                'is_mine'     => (int)$last['sender_id'] === $userId,
            ] : null,
            'unread_count' => chat_unread_count($pdo, $conv, $userId),
        ];
    }

    usort($result, static function (array $a, array $b): int {
        $cmp = strcmp($b['updated_at'], $a['updated_at']);
        if ($cmp !== 0) {
            return $cmp;
        }

        return strcmp($a['title'], $b['title']);
    });

    return $result;
}

/** @return list<array<string, mixed>> */
function chat_get_messages(PDO $pdo, array $conv, int $userId, int $roleId, int $afterId = 0, int $limit = 100): array
{
    if (!chat_user_can_access($pdo, $userId, $roleId, $conv)) {
        return [];
    }

    $limit = max(1, min(200, $limit));
    $params = [];
    chat_conv_bind($params, $conv);

    $afterSql = '';
    if ($afterId > 0) {
        $afterSql = ' AND c.id > ?';
        $params[] = $afterId;
    }

    $stmt = $pdo->prepare("
        SELECT c.id, c.body, c.created_at, c.user_id AS sender_id, u.full_name AS sender_name
        FROM chat c
        JOIN users u ON u.id = c.user_id
        WHERE c.row_kind = 'message' AND " . chat_conv_where_sql('c') . "{$afterSql}
        ORDER BY c.id ASC
        LIMIT {$limit}
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['id'] = (int)$row['id'];
        $row['sender_id'] = (int)$row['sender_id'];
        $row['is_mine'] = (int)$row['sender_id'] === $userId;
    }
    unset($row);

    return $rows;
}

function chat_send_message(PDO $pdo, array $conv, int $userId, int $roleId, string $body): array
{
    $body = trim($body);
    if ($body === '') {
        throw InvalidArgumentException('Введите сообщение');
    }
    if (mb_strlen($body) > 4000) {
        throw InvalidArgumentException('Сообщение слишком длинное (максимум 4000 символов)');
    }

    if (!chat_user_can_send($pdo, $userId, $roleId, $conv)) {
        throw RuntimeException('Нет доступа к этому чату');
    }

    $params = ['message', $conv['conv_type'], (int)$conv['parent_user_id'], (int)$conv['group_id'], $userId, $body];
    $stmt = $pdo->prepare("
        INSERT INTO chat (row_kind, conv_type, parent_user_id, group_id, user_id, body)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute($params);
    $messageId = (int)$pdo->lastInsertId();

    chat_mark_read($pdo, $conv, $userId);

    $stmt = $pdo->prepare("
        SELECT c.id, c.body, c.created_at, c.user_id AS sender_id, u.full_name AS sender_name
        FROM chat c
        JOIN users u ON u.id = c.user_id
        WHERE c.id = ?
    ");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    $message['id'] = (int)$message['id'];
    $message['sender_id'] = (int)$message['sender_id'];
    $message['is_mine'] = true;

    return $message;
}

function chat_mark_read(PDO $pdo, array $conv, int $userId): void
{
    $pdo->prepare("
        INSERT INTO chat (row_kind, conv_type, parent_user_id, group_id, user_id, body, created_at)
        VALUES ('read', ?, ?, ?, ?, NULL, NOW())
        ON DUPLICATE KEY UPDATE created_at = NOW()
    ")->execute([
        $conv['conv_type'],
        (int)$conv['parent_user_id'],
        (int)$conv['group_id'],
        $userId,
    ]);
}

function chat_format_time(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }

    $today = strtotime('today');
    if ($ts >= $today) {
        return date('H:i', $ts);
    }
    if ($ts >= $today - 86400) {
        return 'Вчера ' . date('H:i', $ts);
    }

    return date('d.m.Y H:i', $ts);
}

function chat_url(array $conv, string $base = '..'): string
{
    return $base . '/chat/index.php?conv=' . rawurlencode(chat_conv_key($conv));
}

function chat_back_url(int $roleId): string
{
    return match ($roleId) {
        3, 4 => '../admin/dashboard.php',
        2    => '../employee/dashboard.php',
        default => '../parent/dashboard.php',
    };
}
