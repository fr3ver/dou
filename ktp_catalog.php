<?php

/** Календарно-тематическое планирование — 4 возрастные группы МАДОУ. */

function ktp_section_slug(): string
{
    return 'ktp';
}

/** @return array<string, array{title: string, subtitle: string}> */
function ktp_group_definitions(): array
{
    return [
        'nursery' => [
            'title'    => 'Ясли (1,5–3 года)',
            'subtitle' => 'Календарно-тематические планы по месяцам',
        ],
        'junior' => [
            'title'    => 'Младшие группы (3–4 года)',
            'subtitle' => 'Тематические недели и планы',
        ],
        'middle' => [
            'title'    => 'Средняя группа (4–5 лет)',
            'subtitle' => 'Тематические недели и планы',
        ],
        'senior' => [
            'title'    => 'Старшие группы (5–7 лет)',
            'subtitle' => 'Тематические недели и планы',
        ],
    ];
}

/** @return list<string> */
function ktp_month_names(): array
{
    return [
        'сентябрь', 'октябрь', 'ноябрь', 'декабрь', 'январь', 'февраль',
        'март', 'апрель', 'май', 'июнь', 'июль', 'август',
    ];
}

/**
 * @return list<array{key: string, group: string, title: string, basename: string, type: string}>
 */
function ktp_catalog_items(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $items = [];

    $add = static function (string $group, string $key, string $title, string $type) use (&$items): void {
        $items[] = [
            'key'      => $key,
            'group'    => $group,
            'title'    => $title,
            'basename' => $key . '.pdf',
            'type'     => $type,
        ];
    };

    $groupLabels = [
        'nursery' => 'в ясельной группе (1,5–3 года)',
        'junior'  => 'в младших группах (3–4 года)',
        'middle'  => 'в средней группе (4–5 лет)',
        'senior'  => 'в старших группах (5–7 лет)',
    ];

    foreach (ktp_month_names() as $month) {
        $add(
            'nursery',
            'nursery_' . $month,
            'План воспитателя на ' . $month . ' ' . $groupLabels['nursery'],
            'monthly'
        );
    }

    $thematic = [
        'junior' => [
            'Тематическая неделя «Любимые игры»',
            'Тематическая неделя «Лесные птицы и звери»',
            'Тематическая неделя «Мир природы»',
            'Тематическая неделя «Мы — друзья»',
            'Тематическая неделя «Мир домашних животных»',
            'Тематическая неделя «По тропинкам сказок»',
        ],
        'middle' => [
            'Тематическая неделя «Счастливое лето»',
            'Тематическая неделя «Мы живём в России»',
            'Тематическая неделя «Деревья нашего края»',
            'Тематическая неделя «В мире насекомых»',
            'Тематическая неделя «День семьи»',
            'Тематическая неделя «Лес, луг, сад»',
        ],
        'senior' => [
            'Тематическая неделя «День защиты детей»',
            'Тематическая неделя «Моя родина — Россия!»',
            'Тематическая неделя «Деревья и кустарники»',
            'Тематическая неделя «Насекомые, закрепление»',
            'Тематическая неделя «Наша дружная семья»',
            'Тематическая неделя «На лугу»',
        ],
    ];

    foreach ($thematic as $group => $titles) {
        foreach ($titles as $i => $title) {
            $add($group, $group . '_w' . ($i + 1), $title . ' ' . $groupLabels[$group], 'week');
        }
    }

    $extras = [
        'nursery' => [
            ['nursery_prog_edu', 'Рабочая программа образования детей 1,5–3 лет', 'program'],
            ['nursery_prog_up', 'Рабочая программа воспитания детей 1,5–3 лет', 'program'],
            ['nursery_persp', 'Перспективный план в ясельной группе', 'perspective'],
            ['nursery_summer', 'Перспективный план на лето в ясельной группе', 'summer'],
        ],
        'junior' => [
            ['junior_prog_edu', 'Рабочая программа образования детей 3–4 лет', 'program'],
            ['junior_prog_up', 'Рабочая программа воспитания детей 3–4 лет', 'program'],
            ['junior_persp', 'Перспективный план в младших группах', 'perspective'],
            ['junior_summer', 'Перспективный план на лето в младших группах', 'summer'],
        ],
        'middle' => [
            ['middle_prog_edu', 'Рабочая программа образования детей 4–5 лет', 'program'],
            ['middle_prog_up', 'Рабочая программа воспитания детей 4–5 лет', 'program'],
            ['middle_persp', 'Перспективный план в средней группе', 'perspective'],
            ['middle_summer', 'Перспективный план на лето в средней группе', 'summer'],
        ],
        'senior' => [
            ['senior_prog_edu', 'Рабочая программа образования детей 5–7 лет', 'program'],
            ['senior_prog_up', 'Рабочая программа воспитания детей 5–7 лет', 'program'],
            ['senior_persp', 'Перспективный план в старших группах', 'perspective'],
            ['senior_summer', 'Перспективный план на лето в старших группах', 'summer'],
        ],
    ];

    foreach ($extras as $group => $rows) {
        foreach ($rows as [$key, $title, $type]) {
            $add($group, $key, $title, $type);
        }
    }

    $cache = $items;

    return $cache;
}

/** @return array<string, list<array<string, mixed>>> */
function ktp_catalog_by_group(): array
{
    $byGroup = [];
    foreach (array_keys(ktp_group_definitions()) as $key) {
        $byGroup[$key] = [];
    }
    foreach (ktp_catalog_items() as $item) {
        $byGroup[$item['group']][] = $item;
    }

    return $byGroup;
}

function ktp_catalog_item_by_key(string $key): ?array
{
    foreach (ktp_catalog_items() as $item) {
        if ($item['key'] === $key) {
            return $item;
        }
    }

    return null;
}

/** @return list<string> */
function ktp_catalog_valid_keys(): array
{
    return array_map(static fn(array $item): string => $item['key'], ktp_catalog_items());
}
