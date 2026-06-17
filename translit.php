<?php

function dou_translit_char_map(): array
{
    return [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];
}

function dou_translit_text(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $map = dou_translit_char_map();
    $out = '';

    for ($i = 0, $len = mb_strlen($text, 'UTF-8'); $i < $len; $i++) {
        $ch = mb_substr($text, $i, 1, 'UTF-8');
        if (isset($map[$ch])) {
            $out .= $map[$ch];
        } elseif (preg_match('/[a-z0-9]/', $ch)) {
            $out .= $ch;
        }
    }

    return $out;
}

function dou_username_from_full_name(string $fullName): string
{
    $parts = preg_split('/\s+/u', trim($fullName), 2);
    return dou_translit_text($parts[0] ?? $fullName);
}

function dou_make_unique_username(string $base, array $used): string
{
    if ($base === '') {
        $base = 'user';
    }

    $username = $base;
    $n = 2;
    while (isset($used[$username])) {
        $username = $base . $n;
        $n++;
    }

    return $username;
}
