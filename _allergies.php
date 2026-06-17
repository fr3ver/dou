<?php
require_once __DIR__ . '/../includes/allergies.php';

function admin_get_allergies(PDO $pdo): array { return get_allergies($pdo); }
function admin_get_child_allergy_ids(PDO $pdo, int $child_id): array { return get_child_allergy_ids($pdo, $child_id); }
function admin_sync_child_allergies(PDO $pdo, int $child_id, array $allergy_ids): void { sync_child_allergies($pdo, $child_id, $allergy_ids); }
function admin_child_allergies_sql(string $child_alias = 'c'): string { return child_allergies_sql($child_alias); }
