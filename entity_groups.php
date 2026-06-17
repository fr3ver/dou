<?php

require_once __DIR__ . '/group_labels.php';

/** @return list<int> */
function entity_groups_collect_post_ids(string $field = 'group_ids'): array
{
    $raw = $_POST[$field] ?? [];
    if (!is_array($raw)) {
        return [];
    }

    $ids = [];
    foreach ($raw as $value) {
        $id = (int)$value;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }

    return array_values($ids);
}

/** @param list<int> $groupIds */
function entity_groups_json(array $groupIds): ?string
{
    $groupIds = array_values(array_unique(array_filter(array_map('intval', $groupIds), static fn(int $id): bool => $id > 0)));
    if ($groupIds === []) {
        return null;
    }

    return json_encode($groupIds, JSON_THROW_ON_ERROR);
}

/** @return list<int> */
function entity_groups_parse(mixed $json): array
{
    if ($json === null || $json === '') {
        return [];
    }

    if (is_array($json)) {
        $decoded = $json;
    } else {
        $decoded = json_decode((string)$json, true);
    }

    if (!is_array($decoded)) {
        return [];
    }

    $ids = [];
    foreach ($decoded as $value) {
        $id = (int)$value;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }

    return array_values($ids);
}

/** @return list<int> */
function entity_groups_from_row(?array $row): array
{
    if (!$row) {
        return [];
    }

    return entity_groups_parse($row['target_group_ids'] ?? null);
}

function entity_groups_target_empty_sql(string $alias, string $column = 'target_group_ids'): string
{
    return "({$alias}.{$column} IS NULL OR JSON_LENGTH({$alias}.{$column}) = 0)";
}

function entity_groups_names_sql(string $alias, string $column = 'target_group_ids'): string
{
    return "(SELECT GROUP_CONCAT(gx.name ORDER BY gx.name SEPARATOR ', ')
            FROM `groups` gx
            WHERE {$alias}.{$column} IS NOT NULL
              AND JSON_LENGTH({$alias}.{$column}) > 0
              AND JSON_CONTAINS({$alias}.{$column}, CAST(gx.id AS CHAR), '$'))";
}

function entity_groups_event_names_sql(string $eventAlias = 'e'): string
{
    return entity_groups_names_sql($eventAlias);
}

function entity_groups_news_names_sql(string $newsAlias = 'n'): string
{
    return entity_groups_names_sql($newsAlias);
}

function entity_groups_news_is_for_all(array $row): bool
{
    return entity_groups_from_row($row) === [];
}

/** @return array{0: string, 1: list<mixed>} */
function entity_groups_visibility_sql(array $groupIds, bool $onlyAssigned, string $alias, bool $includeForAllFlag = false): array
{
    if ($onlyAssigned && $groupIds === []) {
        return ['0=1', []];
    }

    $emptySql = entity_groups_target_empty_sql($alias);

    if ($groupIds === []) {
        if ($includeForAllFlag) {
            return ["({$alias}.for_all_groups = 1 OR {$emptySql})", []];
        }

        return [$emptySql, []];
    }

    $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
    $containsSql = "EXISTS (
        SELECT 1 FROM `groups` g
        WHERE g.id IN ({$placeholders})
          AND JSON_CONTAINS({$alias}.target_group_ids, CAST(g.id AS CHAR), '$')
    )";

    if ($includeForAllFlag) {
        $sql = "({$alias}.for_all_groups = 1 OR {$emptySql} OR {$containsSql})";
    } else {
        $sql = "({$emptySql} OR {$containsSql})";
    }

    return [$sql, $groupIds];
}

/** @return array{0: string, 1: list<mixed>} */
function entity_groups_event_visibility_sql(array $groupIds, bool $onlyAssigned, string $alias = 'e'): array
{
    return entity_groups_visibility_sql($groupIds, $onlyAssigned, $alias, true);
}

/** @return array{0: string, 1: list<mixed>} */
function entity_groups_news_visibility_sql(array $groupIds, bool $onlyAssigned, string $alias = 'n'): array
{
    return entity_groups_visibility_sql($groupIds, $onlyAssigned, $alias, false);
}

function entity_groups_public_news_sql(string $alias = 'n'): string
{
    return entity_groups_target_empty_sql($alias);
}

function entity_groups_format_event_groups(array $row, string $emptyLabel = '—'): string
{
    if (!empty($row['for_all_groups'])) {
        return 'Все группы';
    }

    $names = trim((string)($row['group_names'] ?? ''));
    if ($names !== '') {
        return $names;
    }

    return $emptyLabel;
}

function entity_groups_format_news_groups(array $row, string $emptyLabel = 'Все группы'): string
{
    $names = trim((string)($row['group_names'] ?? ''));
    if ($names !== '') {
        return $names;
    }

    return $emptyLabel;
}

/** @param list<int> $selectedIds */
function admin_render_group_checkbox_list(PDO $pdo, array $selectedIds, string $wrapId = 'group-checkboxes', bool $disabled = false): void
{
    $selected = array_flip(array_map('intval', $selectedIds));
    $groups = dou_groups_sorted_by_band($pdo);
    $byBand = dou_groups_by_age_band($groups);
    $disabledAttr = $disabled ? ' disabled' : '';

    echo '<div id="' . htmlspecialchars($wrapId) . '" class="border rounded p-3 bg-white entity-group-checkboxes" style="max-height:220px;overflow-y:auto">';
    foreach (dou_age_band_order() as $band) {
        if (empty($byBand[$band])) {
            continue;
        }
        $meta = dou_age_band_meta($band);
        $bandLabel = $meta['range'] !== ''
            ? $meta['title'] . ' (' . $meta['range'] . ')'
            : $meta['title'];
        echo '<div class="small fw-semibold text-muted mt-2 mb-1">' . htmlspecialchars($bandLabel) . '</div>';
        foreach ($byBand[$band] as $g) {
            $gid = (int)$g['id'];
            $checked = isset($selected[$gid]) ? ' checked' : '';
            $inputId = $wrapId . '-g' . $gid;
            echo '<div class="form-check form-check-sm">';
            echo '<input type="checkbox" class="form-check-input" name="group_ids[]" value="' . $gid . '" id="' . $inputId . '"' . $checked . $disabledAttr . '>';
            echo '<label class="form-check-label" for="' . $inputId . '">' . htmlspecialchars($g['name']) . '</label>';
            echo '</div>';
        }
    }
    echo '</div>';
}
