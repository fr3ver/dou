<?php



require_once __DIR__ . '/group_labels.php';



/**

 * @param list<int> $childIds

 * @return list<int>

 */

function seed_pick_club_members(PDO $pdo, array $childIds, string $clubAge, int $count, int $step = 3): array

{

    $picked = [];

    $stmt = $pdo->prepare('SELECT date_of_birth FROM children WHERE id = ?');



    for ($offset = 0; $offset < $step && count($picked) < $count; $offset++) {

        for ($i = $offset; $i < count($childIds) && count($picked) < $count; $i += $step) {

            $childId = $childIds[$i];

            $stmt->execute([$childId]);

            $dob = $stmt->fetchColumn();

            if (!$dob || !dou_child_age_fits_club($dob, $clubAge)) {

                continue;

            }

            $picked[] = (int) $childId;

        }

    }



    return $picked;

}



/** Удалить записи, где возраст ребёнка не подходит кружку. */

function seed_purge_invalid_club_members(PDO $pdo): int

{

    $rows = $pdo->query('

        SELECT cm.club_id, cm.child_id, c.date_of_birth, cl.age_category

        FROM club_members cm

        JOIN children c ON c.id = cm.child_id

        JOIN clubs cl ON cl.id = cm.club_id

    ')->fetchAll(PDO::FETCH_ASSOC);



    $delete = $pdo->prepare('DELETE FROM club_members WHERE club_id = ? AND child_id = ?');

    $removed = 0;



    foreach ($rows as $row) {

        if (!dou_child_age_fits_club($row['date_of_birth'], $row['age_category'])) {

            $delete->execute([(int) $row['club_id'], (int) $row['child_id']]);

            $removed++;

        }

    }



    return $removed;

}



/**

 * @return array{removed: int, clubs: list<array{club_id: int, name: string, added: int}>}

 */

function seed_apply_club_members(PDO $pdo, bool $replace = true): array

{

    $removed = seed_purge_invalid_club_members($pdo);



    if ($replace) {

        $pdo->exec('DELETE FROM club_members');

        $removed = 0;

    }



    $childIds = $pdo->query('SELECT id FROM children ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

    $clubs = $pdo->query('SELECT id, name, age_category, max_participants FROM clubs ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);



    $targets = [

        'изобраз' => 6,

        'музык'   => 5,

        'англий'  => 5,

    ];



    $insert = $pdo->prepare("

        INSERT INTO club_members (club_id, child_id, status, enrolled_at, created_at)

        VALUES (?, ?, 'enrolled', CURDATE(), NOW())

    ");



    $result = [];



    foreach ($clubs as $club) {

        $clubId = (int) $club['id'];

        $age = $club['age_category'] ?? '';

        $want = 5;

        $lower = mb_strtolower($club['name']);

        foreach ($targets as $needle => $n) {

            if (str_contains($lower, $needle)) {

                $want = $n;

                break;

            }

        }



        $max = $club['max_participants'] !== null ? (int) $club['max_participants'] : null;

        if ($max !== null) {

            $want = min($want, max(0, $max - 2));

        }



        $picked = seed_pick_club_members($pdo, $childIds, $age, $want);

        $added = 0;



        foreach ($picked as $childId) {

            if (!$replace) {

                $check = $pdo->prepare('SELECT 1 FROM club_members WHERE club_id = ? AND child_id = ?');

                $check->execute([$clubId, $childId]);

                if ($check->fetch()) {

                    continue;

                }

            }

            $insert->execute([$clubId, $childId]);

            $added++;

        }



        $result[] = [

            'club_id' => $clubId,

            'name'    => $club['name'],

            'added'   => $added,

        ];

    }



    return ['removed' => $removed, 'clubs' => $result];

}


