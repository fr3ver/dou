<?php

/**
 * Единые подписи возрастных групп (ясли, младшие, средняя, старшая).
 */

/** Значения для поля groups.age_category в БД и в формах админки. */
function dou_age_categories(): array
{
    return [
        'Ясли (1,5–3 года)',
        'Младшие группы (3–4 года)',
        'Средняя группа (4–5 лет)',
        'Старшая группа (5–7 лет)',
        'Логопедическая (5–7 лет)',
    ];
}

/** Ключ возрастной категории для сортировки и optgroup. */
function dou_age_band(?string $ageCategory): string
{
    $age = mb_strtolower($ageCategory ?? '');
    if (str_contains($age, 'ясл')) {
        return 'nursery';
    }
    if (str_contains($age, 'младш')) {
        return 'junior';
    }
    if (str_contains($age, 'средн')) {
        return 'middle';
    }
    if (str_contains($age, 'логопед')) {
        return 'speech';
    }
    if (str_contains($age, 'подготов') || str_contains($age, 'старш')) {
        return 'senior';
    }

    return 'other';
}

/** @return list<string> */
function dou_age_band_order(): array
{
    return ['nursery', 'junior', 'middle', 'senior', 'speech', 'other'];
}

function dou_age_band_meta(string $band): array
{
    return match ($band) {
        'nursery' => [
            'title' => 'Ясли',
            'range' => '1,5–3 года',
            'icon'  => 'bi-balloon-heart',
        ],
        'junior' => [
            'title' => 'Младшие группы',
            'range' => '3–4 года',
            'icon'  => 'bi-emoji-smile',
        ],
        'middle' => [
            'title' => 'Средняя группа',
            'range' => '4–5 лет',
            'icon'  => 'bi-puzzle',
        ],
        'senior' => [
            'title' => 'Старшие группы',
            'range' => '5–7 лет',
            'icon'  => 'bi-mortarboard',
        ],
        'speech' => [
            'title' => 'Логопедическая',
            'range' => '5–7 лет',
            'icon'  => 'bi-mic',
        ],
        default => [
            'title' => 'Группа',
            'range' => '',
            'icon'  => 'bi-collection',
        ],
    };
}

function dou_age_band_meta_by_category(?string $ageCategory): array
{
    return dou_age_band_meta(dou_age_band($ageCategory));
}

/** Подпись для UI: «Ясли · 1,5–3 года». */
function dou_age_display(?string $ageCategory): string
{
    if ($ageCategory === null || trim($ageCategory) === '') {
        return '—';
    }

    $meta = dou_age_band_meta_by_category($ageCategory);
    if ($meta['range'] !== '') {
        return $meta['title'] . ' · ' . $meta['range'];
    }

    return trim($ageCategory);
}

/** Заголовок блока на главной (без возраста в скобках). */
function dou_age_section_title(?string $ageCategory): string
{
    return dou_age_band_meta_by_category($ageCategory)['title'];
}

/** Возраст одной строкой под заголовком. */
function dou_age_section_range(?string $ageCategory): string
{
    return dou_age_band_meta_by_category($ageCategory)['range'];
}

/** Подпись для &lt;optgroup&gt; — с заглавной буквы. */
function dou_age_optgroup_label(string $band): string
{
    return dou_age_band_meta($band)['title'];
}

/**
 * @param array<int, array<string, mixed>> $groups
 * @return array<string, list<array>>
 */
/**
 * @param array<int, array<string, mixed>> $groups
 * @return array<string, list<array>>
 */
function dou_groups_by_age_band(array $groups): array
{
    $byBand = [];
    foreach (dou_age_band_order() as $band) {
        $byBand[$band] = [];
    }

    foreach ($groups as $group) {
        $band = dou_age_band($group['age_category'] ?? '');
        $byBand[$band][] = $group;
    }

    foreach ($byBand as &$items) {
        usort($items, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
    }
    unset($items);

    return array_filter($byBand, static fn($items) => $items !== []);
}

/**
 * @return array<int, array<string, mixed>>
 */
function dou_groups_sorted_by_band(PDO $pdo): array
{
    $groups = $pdo->query('SELECT id, name, age_category, care_mode FROM `groups`')->fetchAll(PDO::FETCH_ASSOC);
    $order = array_flip(dou_age_band_order());

    foreach ($groups as &$g) {
        $g['age_band'] = dou_age_band($g['age_category'] ?? '');
    }
    unset($g);

    usort($groups, static function ($a, $b) use ($order) {
        $ba = $order[$a['age_band']] ?? 99;
        $bb = $order[$b['age_band']] ?? 99;
        if ($ba !== $bb) {
            return $ba <=> $bb;
        }

        return strnatcasecmp($a['name'], $b['name']);
    });

    return $groups;
}

/**
 * @param bool|string $emptyOption false — без пустой строки; true — «Выберите группу»; string — свой текст
 */
function dou_render_group_select_options(PDO $pdo, int $selectedId = 0, bool|string $emptyOption = false): void
{
    $groups = dou_groups_sorted_by_band($pdo);
    $byBand = [];
    foreach ($groups as $g) {
        $byBand[$g['age_band']][] = $g;
    }

    if ($emptyOption !== false) {
        $emptyLabel = $emptyOption === true ? 'Выберите группу' : (string) $emptyOption;
        $emptySel = $selectedId === 0 ? ' selected' : '';
        echo '<option value=""' . $emptySel . '>' . htmlspecialchars($emptyLabel) . '</option>';
    }

    foreach (dou_age_band_order() as $band) {
        if (empty($byBand[$band])) {
            continue;
        }
        $meta = dou_age_band_meta($band);
        $optLabel = $meta['range'] !== ''
            ? $meta['title'] . ' (' . $meta['range'] . ')'
            : $meta['title'];
        echo '<optgroup label="' . htmlspecialchars($optLabel) . '">';
        foreach ($byBand[$band] as $g) {
            $sel = $selectedId === (int)$g['id'] ? ' selected' : '';
            echo '<option value="' . (int)$g['id'] . '"' . $sel . '>'
                . htmlspecialchars($g['name']) . '</option>';
        }
        echo '</optgroup>';
    }
}

function dou_render_group_age_options(?string $selected = null): void
{
    $selected = $selected ?? '';
    $categories = dou_age_categories();

    if ($selected !== '' && !in_array($selected, $categories, true)) {
        echo '<option value="' . htmlspecialchars($selected) . '" selected>'
            . htmlspecialchars(dou_age_display($selected)) . '</option>';
    }

    foreach ($categories as $cat) {
        $sel = $selected === $cat ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($cat) . '"' . $sel . '>'
            . htmlspecialchars(dou_age_display($cat)) . '</option>';
    }
}

/**
 * Допустимый возраст (в годах) для возрастной категории группы.
 *
 * @return array{0: float, 1: float}
 */
function dou_age_years_bounds(?string $ageCategory): array
{
    return match (dou_age_band($ageCategory)) {
        'nursery'     => [1.5, 3.0],
        'junior'      => [3.0, 4.0],
        'middle'      => [4.0, 5.0],
        'senior'      => [5.0, 7.0],
        'speech'      => [5.0, 7.0],
        default       => [0.0, 18.0],
    };
}

/** Возраст ребёнка в годах на указанную дату. */
function dou_child_age_years(string $dateOfBirth, ?DateTimeInterface $reference = null): float
{
    $ref = $reference instanceof DateTimeInterface
        ? DateTimeImmutable::createFromInterface($reference)
        : new DateTimeImmutable('today');
    $born = new DateTimeImmutable($dateOfBirth);
    if ($born > $ref) {
        return 0.0;
    }
    $diff = $born->diff($ref);

    return $diff->y + $diff->m / 12 + $diff->d / 365.25;
}

function dou_child_age_fits_group(string $dateOfBirth, ?string $ageCategory, ?DateTimeInterface $reference = null): bool
{
    if ($ageCategory === null || trim($ageCategory) === '') {
        return true;
    }
    [$min, $max] = dou_age_years_bounds($ageCategory);
    $age = dou_child_age_years($dateOfBirth, $reference);

    return $age >= $min - 0.05 && $age <= $max + 0.05;
}

/**
 * Диапазон возраста кружка из строки вида «4–6 лет», «3-5».
 *
 * @return array{0: float, 1: float}|null
 */
function dou_club_age_bounds(?string $ageCategory): ?array
{
    if ($ageCategory === null || trim($ageCategory) === '') {
        return null;
    }

    if (!preg_match('/(\d+(?:[.,]\d+)?)\s*[–\-—]\s*(\d+(?:[.,]\d+)?)/u', $ageCategory, $m)) {
        return null;
    }

    $min = (float) str_replace(',', '.', $m[1]);
    $max = (float) str_replace(',', '.', $m[2]);

    return $min <= $max ? [$min, $max] : [$max, $min];
}

function dou_child_age_fits_club(string $dateOfBirth, ?string $clubAgeCategory, ?DateTimeInterface $reference = null): bool
{
    $bounds = dou_club_age_bounds($clubAgeCategory);
    if ($bounds === null) {
        return true;
    }

    [$min, $max] = $bounds;
    $age = dou_child_age_years($dateOfBirth, $reference);

    return $age >= $min - 0.05 && $age <= $max + 0.05;
}

/**
 * Дата рождения, соответствующая возрастной категории группы (с лёгким разбросом по детям).
 */
function dou_birth_date_for_group_age(?string $ageCategory, int $childId = 0, ?DateTimeInterface $reference = null): string
{
    $ref = $reference instanceof DateTimeInterface
        ? DateTimeImmutable::createFromInterface($reference)
        : new DateTimeImmutable('today');

    [$min, $max] = dou_age_years_bounds($ageCategory);
    $mid = ($min + $max) / 2;
    $spread = max(0.15, ($max - $min) * 0.35);
    $slot = $childId > 0 ? (($childId % 9) - 4) / 4.0 : 0.0;
    $years = max($min + 0.08, min($max - 0.08, $mid + $spread * $slot));

    $wholeYears = (int) floor($years);
    $months = (int) round(($years - $wholeYears) * 12);

    return $ref->modify("-{$wholeYears} years")->modify("-{$months} months")->format('Y-m-d');
}

/** Нормализация старых значений age_category в БД. */
function dou_age_category_canonical(?string $ageCategory): ?string
{
    if ($ageCategory === null || trim($ageCategory) === '') {
        return null;
    }

    $map = [
        'ясл' => 'Ясли (1,5–3 года)',
        'младш' => 'Младшие группы (3–4 года)',
        'средн' => 'Средняя группа (4–5 лет)',
        'старш' => 'Старшая группа (5–7 лет)',
        'подготов' => 'Старшая группа (5–7 лет)',
        'логопед' => 'Логопедическая (5–7 лет)',
    ];

    $lower = mb_strtolower($ageCategory);
    if (str_contains($lower, 'логопед')) {
        return $map['логопед'];
    }
    if (str_contains($lower, 'подготов')) {
        return $map['подготов'];
    }
    foreach ($map as $needle => $canonical) {
        if ($needle === 'подготов' || $needle === 'логопед') {
            continue;
        }
        if (str_contains($lower, $needle)) {
            return $canonical;
        }
    }

    return trim($ageCategory);
}
