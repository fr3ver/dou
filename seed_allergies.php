<?php



/**

 * Справочник аллергенов (по меню сада) и привязка к детям.

 */



/** @return list<string> */

function seed_allergy_dictionary(): array

{

    return [

        'Молоко',

        'Глютен',

        'Яйца',

        'Рыба',

        'Орехи',

        'Мёд',

        'Соя',

        'Цитрусовые',

        'Какао',

        'Клубника',

        'Пшеница',

        'Арахис',

        'Шоколад',

        'Ягоды',

    ];

}



/** @return array<string, int> */

function seed_ensure_allergies(PDO $pdo): array

{

    $stmt = $pdo->prepare('INSERT INTO allergies (name) VALUES (?) ON DUPLICATE KEY UPDATE name = name');

    foreach (seed_allergy_dictionary() as $name) {

        $stmt->execute([$name]);

    }



    $map = [];

    foreach ($pdo->query('SELECT id, name FROM allergies ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) as $row) {

        $map[$row['name']] = (int) $row['id'];

    }



    return $map;

}



/**

 * Сколько аллергий у каждого ребёнка: 16×1, 9×2, 5×3, остальные — 0.

 *

 * @return list<int>

 */

function seed_allergy_count_slots(int $totalChildren): array

{

    $slots = array_merge(

        array_fill(0, 16, 1),

        array_fill(0, 9, 2),

        array_fill(0, 5, 3),

    );



    if (count($slots) > $totalChildren) {

        throw new RuntimeException('Слишком много детей с аллергиями для выборки');

    }



    while (count($slots) < $totalChildren) {

        $slots[] = 0;

    }



    shuffle($slots);



    return $slots;

}



/** @param array<string, int> $allergyMap */

function seed_allergy_ids_for_child(int $childIndex, int $allergyCount, array $allergyMap): array

{

    if ($allergyCount <= 0) {

        return [];

    }



    $names = seed_allergy_dictionary();

    $picked = [];

    $offset = ($childIndex * 3) % count($names);



    for ($i = 0; count($picked) < $allergyCount && $i < count($names) * 2; $i++) {

        $name = $names[($offset + $i) % count($names)];

        $id = $allergyMap[$name] ?? 0;

        if ($id > 0 && !in_array($id, $picked, true)) {

            $picked[] = $id;

        }

    }



    return $picked;

}



/**

 * @return array{children: int, with_allergies: int, links: int, one: int, two: int, three: int}

 */

function seed_apply_child_allergies(PDO $pdo): array

{

    require_once __DIR__ . '/allergies.php';



    $map = seed_ensure_allergies($pdo);

    $children = $pdo->query('SELECT id FROM children ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

    $slots = seed_allergy_count_slots(count($children));



    $withAllergies = 0;

    $links = 0;

    $one = $two = $three = 0;



    foreach ($children as $i => $childId) {

        $count = $slots[$i] ?? 0;

        $ids = seed_allergy_ids_for_child((int) $i, $count, $map);

        sync_child_allergies($pdo, (int) $childId, $ids);



        if ($ids !== []) {

            $withAllergies++;

            $links += count($ids);

            match (count($ids)) {

                1       => $one++,

                2       => $two++,

                3       => $three++,

                default => null,

            };

        }

    }



    return [

        'children'       => count($children),

        'with_allergies' => $withAllergies,

        'links'          => $links,

        'one'            => $one,

        'two'            => $two,

        'three'          => $three,

    ];

}


