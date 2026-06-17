<?php

function child_allergies_sql(string $child_alias = 'c'): string
{
    return "(SELECT GROUP_CONCAT(a.name ORDER BY a.name SEPARATOR ', ')
             FROM child_allergies ca
             INNER JOIN allergies a ON a.id = ca.allergy_id
             WHERE ca.child_id = {$child_alias}.id)";
}

function get_allergies(PDO $pdo): array
{
    return $pdo->query('SELECT id, name FROM allergies ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
}

function get_child_allergy_ids(PDO $pdo, int $child_id): array
{
    $stmt = $pdo->prepare('SELECT allergy_id FROM child_allergies WHERE child_id = ? ORDER BY allergy_id');
    $stmt->execute([$child_id]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/** @return list<int> */
function entity_allergy_ids_parse(mixed $json): array
{
    if ($json === null || $json === '') {
        return [];
    }

    if (is_array($json)) {
        $decoded = $json;
    } else {
        $decoded = json_decode((string) $json, true);
    }

    if (!is_array($decoded)) {
        return [];
    }

    $ids = [];
    foreach ($decoded as $value) {
        $id = (int) $value;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }

    return array_values($ids);
}

function sync_child_allergies(PDO $pdo, int $child_id, array $allergy_ids): void
{
    $allergy_ids = array_values(array_unique(array_filter(array_map('intval', $allergy_ids), static fn(int $id): bool => $id > 0)));

    $pdo->prepare('DELETE FROM child_allergies WHERE child_id = ?')->execute([$child_id]);

    if ($allergy_ids === []) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO child_allergies (child_id, allergy_id) VALUES (?, ?)');
    foreach ($allergy_ids as $allergyId) {
        $stmt->execute([$child_id, $allergyId]);
    }
}

function count_children_with_allergy(PDO $pdo, int $allergyId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(DISTINCT child_id) FROM child_allergies WHERE allergy_id = ?');
    $stmt->execute([$allergyId]);

    return (int) $stmt->fetchColumn();
}
