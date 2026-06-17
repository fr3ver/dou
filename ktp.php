<?php

require_once __DIR__ . '/org_documents.php';
require_once __DIR__ . '/ktp_catalog.php';

function ktp_intro_text(): string
{
    require_once __DIR__ . '/svedeniya_sections_content.php';

    return svedeniya_ktp_intro();
}

/** @return list<array<string, mixed>> */
function ktp_documents(PDO $pdo): array
{
    return org_documents_for_section($pdo, ktp_section_slug(), true);
}

/** @param list<array<string, mixed>> $docs @return array<string, array<string, mixed>> key => doc */
function ktp_documents_by_key(array $docs): array
{
    $map = [];
    foreach ($docs as $doc) {
        $key = trim((string)($doc['category'] ?? ''));
        if ($key !== '') {
            $map[$key] = $doc;
        }
    }

    return $map;
}

/**
 * @param list<array<string, mixed>> $docs
 * @return array<string, list<array<string, mixed>>> group => docs
 */
function ktp_documents_grouped(PDO $pdo, array $docs): array
{
    $byKey = ktp_documents_by_key($docs);
    $groups = ktp_group_definitions();
    $result = [];

    foreach (array_keys($groups) as $groupKey) {
        $result[$groupKey] = [];
        foreach (ktp_catalog_by_group()[$groupKey] ?? [] as $item) {
            if (isset($byKey[$item['key']])) {
                $result[$groupKey][] = $byKey[$item['key']];
            }
        }
    }

    return $result;
}

function ktp_staff_can_access(int $roleId): bool
{
    return in_array($roleId, [2, 3, 4], true);
}

/** @param array<string, mixed> $doc */
function ktp_render_card(array $doc, string $base = ''): void
{
    $title = (string)($doc['title'] ?? '');
    $url = $base . org_document_download_url((int)($doc['id'] ?? 0), true);
    $size = org_document_format_size((int)($doc['file_size'] ?? 0));
    ?>
    <article class="ktp-card">
        <a href="<?= htmlspecialchars($url) ?>" class="ktp-card-link" target="_blank" rel="noopener">
            <div class="ktp-card-cover" aria-hidden="true">
                <i class="bi bi-file-earmark-pdf"></i>
                <span class="ktp-card-cover-label">PDF</span>
            </div>
            <h3 class="ktp-card-title"><?= htmlspecialchars($title) ?></h3>
            <p class="ktp-card-meta"><?= htmlspecialchars($size) ?></p>
        </a>
    </article>
    <?php
}

/** @param array<string, list<array<string, mixed>>> $grouped */
function ktp_render_grid(array $grouped, string $base = ''): void
{
    $groups = ktp_group_definitions();
    $hasAny = false;
    foreach ($grouped as $docs) {
        if ($docs !== []) {
            $hasAny = true;
            break;
        }
    }

    if (!$hasAny) {
        echo '<p class="text-muted mb-0">Материалы календарно-тематического планирования будут опубликованы администрацией.</p>';

        return;
    }

    foreach ($groups as $groupKey => $meta) {
        $docs = $grouped[$groupKey] ?? [];
        if ($docs === []) {
            continue;
        }
        ?>
        <section class="ktp-group scroll-margin-top" id="ktp-<?= htmlspecialchars($groupKey) ?>">
            <h2 class="ktp-group-title"><?= htmlspecialchars($meta['title']) ?></h2>
            <?php if ($meta['subtitle'] !== ''): ?>
            <p class="ktp-group-subtitle"><?= htmlspecialchars($meta['subtitle']) ?></p>
            <?php endif; ?>
            <div class="ktp-grid">
                <?php foreach ($docs as $doc): ?>
                    <?php ktp_render_card($doc, $base); ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}
