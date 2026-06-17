<?php

require_once __DIR__ . '/org_documents.php';
require_once __DIR__ . '/svedeniya_structure.php';
require_once __DIR__ . '/parents_info_content.php';
require_once __DIR__ . '/safety_content.php';

function svedeniya_org_name(): string
{
    return 'МАДОУ «' . SITE_NAME . '»';
}

function svedeniya_documents_intro(): string
{
    return 'В разделе размещены основные документы ' . svedeniya_org_name() . ': устав, лицензия, свидетельства, '
        . 'приказы, правила внутреннего распорядка и иные локальные акты образовательной организации.';
}

function svedeniya_standards_intro(): string
{
    return 'Образовательная деятельность ' . svedeniya_org_name() . ' осуществляется в соответствии с федеральным '
        . 'государственным образовательным стандартом дошкольного образования, федеральной образовательной программой '
        . 'дошкольного образования и федеральными государственными образовательными требованиями.' . "\n\n"
        . 'Освоение образовательных программ дошкольного образования не сопровождается проведением промежуточных '
        . 'аттестаций и итоговой аттестации обучающихся.';
}

function svedeniya_structure_intro(): string
{
    return 'Структура и органы управления образовательной организации определяются уставом учреждения.';
}

function svedeniya_structure_units(): array
{
    return [
        [
            'title' => 'Руководство',
            'body'  => "## Директор\n\n" . SITE_EMAIL . "\n" . SITE_PHONE,
        ],
        [
            'title' => 'Структурные подразделения',
            'body'  => "• администрация\n"
                . "• возрастные группы\n"
                . "• музыкальный зал\n"
                . "• спортивный зал",
        ],
    ];
}

function svedeniya_upbringing_intro(): string
{
    return 'Воспитательная работа в ' . svedeniya_org_name() . ' направлена на гармоничное развитие личности ребёнка, '
        . 'формирование нравственных ориентиров и сотрудничества с семьёй.' . "\n\n"
        . "## Планирование\n\n"
        . 'Воспитательная работа планируется на учебный год и реализуется в соответствии с образовательной программой '
        . 'дошкольного образования.' . "\n\n"
        . "## Основные направления\n\n"
        . "• патриотическое воспитание\n"
        . "• гражданственное воспитание\n"
        . "• нравственное воспитание\n"
        . "• трудовое воспитание\n"
        . "• экологическое воспитание\n"
        . "• развитие толерантности\n"
        . "• формирование здорового образа жизни\n\n"
        . 'Календарный план воспитательной работы на 2026–2027 уч. г. и план работы с родителями доводятся до родителей '
        . 'на общих собраниях и в группах. Подробные материалы размещены в карточках ниже.';
}

function svedeniya_upbringing_projects_body(): string
{
    return "## Тематические недели и праздники\n\n"
        . "• День защиты детей\n"
        . "• День семьи\n"
        . "• День пожилого человека\n"
        . "• Новый год\n"
        . "• Масленица\n"
        . "• Сагаалган\n"
        . "• День Победы\n\n"
        . "## Проекты\n\n"
        . "• «Моя семья — моё богатство»\n"
        . "• «Экологическая тропа»\n"
        . "• «Безопасная дорога детства»\n\n"
        . "## Совместная деятельность\n\n"
        . "Дети участвуют в утренниках, выставках детского творчества, экскурсиях и встречах с представителями "
        . "библиотеки, музея, МЧС и полиции.\n\n"
        . "Родители приглашаются к совместным мероприятиям: мастер-классам, праздникам, дежурствам в группе.";
}

function svedeniya_upbringing_moral_body(): string
{
    return "## Подход\n\n"
        . "Духовно-нравственное воспитание ведётся через игру, сказку, общение, добрые дела и семейные традиции.\n\n"
        . "## Ценности и навыки\n\n"
        . "• уважение к старшим\n"
        . "• забота о близких\n"
        . "• бережное отношение к природе\n"
        . "• уважение к культуре народов России, в том числе традициям Республики Бурятия\n"
        . "• ответственность, дружба, взаимопомощь\n"
        . "• гордость за свою семью и Родину\n\n"
        . 'Воспитатели учитывают семейные ценности и индивидуальные особенности каждого воспитанника.';
}

function svedeniya_nutrition_intro(): string
{
    return "## Охрана здоровья обучающихся\n\n"
        . "Охрана здоровья обучающихся, в том числе инвалидов и лиц с ограниченными возможностями здоровья, включает:\n\n"
        . "• оказание первичной медико-санитарной помощи в порядке, установленном законодательством в сфере охраны здоровья\n"
        . "• определение оптимальной учебной нагрузки, режима учебных занятий и продолжительности каникул\n"
        . "• пропаганду и обучение навыкам здорового образа жизни, требованиям охраны труда\n"
        . "• организацию и создание условий для профилактики заболеваний и оздоровления, для занятий физической культурой и спортом\n"
        . "• профилактику и запрещение курения, употребления алкогольных, слабоалкогольных напитков, пива, наркотических средств и психотропных веществ\n"
        . "• обеспечение безопасности во время пребывания в организации\n"
        . "• профилактику несчастных случаев во время пребывания в организации\n\n"
        . "## Условия для обучающихся\n\n"
        . "Организация создаёт условия для охраны здоровья обучающихся:\n\n"
        . "• текущий контроль за состоянием здоровья\n"
        . "• проведение санитарно-гигиенических, профилактических и оздоровительных мероприятий\n"
        . "• соблюдение государственных санитарно-эпидемиологических правил и нормативов\n"
        . "• расследование и учёт несчастных случаев с обучающимися в установленном порядке\n\n"
        . "## Организация питания\n\n"
        . 'Организация питания обучающихся в организации не предусмотрена.';
}

function svedeniya_nutrition_units(): array
{
    return [
        [
            'title' => 'Меню',
            'body'  => "## Примерное меню\n\n"
                . 'Примерное меню размещается на сайте и в группах. Актуальное меню доступно в личном кабинете родителей.',
        ],
        [
            'title' => 'Санитарные нормы и рацион',
            'body'  => "## Питание в ДОУ\n\n"
                . 'Организовано 4-разовое сбалансированное питание в соответствии с возрастными нормами '
                . 'и санитарными требованиями.',
        ],
        [
            'title' => 'Аллергенные продукты',
            'body'  => "## Индивидуальные особенности\n\n"
                . 'При наличии индивидуальных особенностей питания учитываются рекомендации медицинских работников '
                . 'и сведения от родителей.',
        ],
        [
            'title' => 'Ответственный за питание',
            'body'  => "## Контакты\n\n"
                . SITE_PHONE . "\n" . SITE_EMAIL,
        ],
    ];
}

function svedeniya_pmpk_intro(): string
{
    return 'Сведения о психолого-медико-педагогической комиссии и сопровождении детей с ограниченными возможностями здоровья '
        . 'в ' . svedeniya_org_name() . '.';
}

function svedeniya_pmpk_units(): array
{
    return [
        [
            'title' => 'Что такое ПМПК',
            'body'  => 'ПМПК — комиссия, которая определяет образовательные потребности ребёнка и рекомендует условия обучения и воспитания.',
        ],
        [
            'title' => 'Взаимодействие ДОУ с ПМПК',
            'body'  => 'Детский сад направляет детей на обследование по обращению родителей и при необходимости реализует рекомендации комиссии.',
        ],
        [
            'title' => 'Адаптированные программы',
            'body'  => 'Информация об адаптированных образовательных программах размещается в разделе «Образование».',
        ],
        [
            'title' => 'Специалисты',
            'body'  => "## Специалисты сопровождения\n\n"
                . "• педагог-психолог\n"
                . "• учитель-логопед\n"
                . "• иные специалисты сопровождения",
        ],
        [
            'title' => 'Порядок обращения родителей',
            'body'  => "## Как обратиться\n\n"
                . 'Обратиться можно к заведующему или специалистам детского сада:' . "\n\n"
                . '• телефон: ' . SITE_PHONE . "\n"
                . '• e-mail: ' . SITE_EMAIL,
        ],
    ];
}

function svedeniya_facilities_intro(): string
{
    return 'Описание помещений, оборудования и условий для обучения и развития воспитанников '
        . 'в ' . svedeniya_org_name() . '.';
}

function svedeniya_facilities_units(): array
{
    return [
        [
            'title' => 'Помещения',
            'body'  => "## Основные помещения\n\n"
                . "• групповые комнаты\n"
                . "• спальни\n"
                . "• музыкальный зал\n"
                . "• спортивный зал\n"
                . "• площадки для прогулок",
        ],
        [
            'title' => 'Оснащённость',
            'body'  => "## Материалы и оборудование\n\n"
                . "• учебные материалы\n"
                . "• игровые материалы\n"
                . "• спортивное оборудование\n"
                . "• развивающие материалы в соответствии с возрастом детей",
        ],
    ];
}

function svedeniya_finance_intro(): string
{
    return 'Сметы, отчёты и финансовая отчётность образовательной организации.';
}

function svedeniya_finance_units(): array
{
    return [
        [
            'title' => 'Финансово-хозяйственная деятельность',
            'body'  => 'Сведения размещаются в соответствии с требованиями законодательства Российской Федерации.',
        ],
    ];
}

function svedeniya_accessibility_intro(): string
{
    return 'Условия для обучения и воспитания детей с ограниченными возможностями здоровья '
        . 'в ' . svedeniya_org_name() . '.';
}

function svedeniya_accessibility_units(): array
{
    return [
        [
            'title' => 'Доступная среда',
            'body'  => 'В детском саду созданы условия для посещения и пребывания детей с ОВЗ с учётом индивидуальных потребностей.',
        ],
        [
            'title' => 'Специальные условия',
            'body'  => "## Сопровождение\n\n"
                . "• сопровождение специалистами при необходимости\n"
                . "• использование адаптированных программ",
        ],
    ];
}

function svedeniya_ktp_intro(): string
{
    return 'Календарно-тематическое планирование воспитателя ДОУ помогает организовать процесс воспитания и образования '
        . 'дошкольников с учётом ФГОС и ФОП ДО.' . "\n\n"
        . "## Возрастные группы\n\n"
        . "• ясли (1,5–3 года)\n"
        . "• младшие (3–4 года)\n"
        . "• средняя (4–5 лет)\n"
        . "• старшие (5–7 лет)";
}

/** @return array{intro: string, units: list<array<string, mixed>>}|null */
function svedeniya_canonical_section(?PDO $pdo, string $slug): ?array
{
    return match ($slug) {
        'basic' => [
            'intro' => '',
            'units' => [
                ['title' => 'Полное наименование', 'body' => 'Муниципальное автономное дошкольное образовательное учреждение «' . SITE_NAME . '»'],
                ['title' => 'Сокращённое наименование', 'body' => svedeniya_org_name()],
                ['title' => 'Адрес', 'body' => SITE_ADDRESS],
                ['title' => 'Телефон', 'body' => SITE_PHONE],
                ['title' => 'E-mail', 'body' => SITE_EMAIL],
                ['title' => 'Режим работы', 'body' => 'понедельник — пятница, 7:00 — 19:00'],
            ],
        ],
        'documents' => [
            'intro' => svedeniya_documents_intro(),
            'units' => [],
        ],
        'standards' => [
            'intro' => svedeniya_standards_intro(),
            'units' => [
                ['title' => 'ФГОС дошкольного образования', 'url' => 'https://fgos.ru/fgos/fgos-do/'],
                ['title' => 'Федеральные государственные требования', 'url' => 'https://www.consultant.ru/document/cons_doc_LAW_140174/dfbe1cf7aa2e2acfd7b8e7ad37cdf71b759c539d/'],
                ['title' => 'Приказ Министерства просвещения РФ от 25 ноября 2022 г. № 1028 «Об утверждении федеральной образовательной программы дошкольного образования»', 'url' => 'https://www.garant.ru/products/ipo/prime/doc/405942493/'],
            ],
        ],
        'structure' => [
            'intro' => svedeniya_structure_intro(),
            'units' => svedeniya_structure_units(),
        ],
        'upbringing' => [
            'intro' => svedeniya_upbringing_intro(),
            'units' => $pdo ? svedeniya_upbringing_units_with_files($pdo) : [
                ['title' => 'Проекты и мероприятия', 'body' => svedeniya_upbringing_projects_body()],
                ['title' => 'Духовно-нравственное развитие', 'body' => svedeniya_upbringing_moral_body()],
            ],
        ],
        'nutrition' => [
            'intro' => svedeniya_nutrition_intro(),
            'units' => svedeniya_nutrition_units(),
        ],
        'parents_info' => [
            'intro' => parents_info_intro_text(),
            'units' => $pdo ? parents_info_canonical_units($pdo) : parents_info_canonical_units_static(),
        ],
        'safety' => [
            'intro' => safety_intro_text(),
            'units' => safety_canonical_units(
                $pdo ? svedeniya_section_file_ids_by_unit_title($pdo, 'safety') : []
            ),
        ],
        'pmpk' => [
            'intro' => svedeniya_pmpk_intro(),
            'units' => svedeniya_pmpk_units(),
        ],
        'facilities' => [
            'intro' => svedeniya_facilities_intro(),
            'units' => svedeniya_facilities_units(),
        ],
        'finance' => [
            'intro' => svedeniya_finance_intro(),
            'units' => svedeniya_finance_units(),
        ],
        'accessibility' => [
            'intro' => svedeniya_accessibility_intro(),
            'units' => svedeniya_accessibility_units(),
        ],
        'ktp' => [
            'intro' => svedeniya_ktp_intro(),
            'units' => [],
        ],
        default => null,
    };
}

/** @return array<string, list<int>> */
function svedeniya_section_file_ids_by_unit_title(PDO $pdo, string $slug): array
{
    $row = org_document_text_get($pdo, $slug);
    if (!$row) {
        return [];
    }
    $parsed = svedeniya_structure_parse((string)$row['content']);
    $map = [];
    foreach ($parsed['units'] as $unit) {
        $title = trim((string)($unit['title'] ?? ''));
        $ids = svedeniya_structure_unit_file_ids($unit);
        if ($title !== '' && $ids !== []) {
            $map[$title] = $ids;
        }
    }

    return $map;
}

/** @return list<array<string, mixed>> */
function svedeniya_upbringing_units_with_files(PDO $pdo): array
{
    $fileIds = svedeniya_section_file_ids_by_unit_title($pdo, 'upbringing');
    $units = [
        ['title' => 'Проекты и мероприятия', 'body' => svedeniya_upbringing_projects_body()],
        ['title' => 'Духовно-нравственное развитие', 'body' => svedeniya_upbringing_moral_body()],
    ];
    foreach ($units as $idx => $unit) {
        if (isset($fileIds[$unit['title']])) {
            $units[$idx]['file_ids'] = $fileIds[$unit['title']];
        }
    }

    return $units;
}

/** @return list<string> */
function svedeniya_canonical_content_slugs(): array
{
    return [
        'basic',
        'documents',
        'standards',
        'structure',
        'upbringing',
        'nutrition',
        'parents_info',
        'safety',
        'pmpk',
        'facilities',
        'finance',
        'accessibility',
        'ktp',
    ];
}
