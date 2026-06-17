<?php

require_once __DIR__ . '/_allergy_fields.php';

function admin_render_child_tnr_fields(?bool $hasTnr): void
{
    $yes = $hasTnr === true;
    $no = $hasTnr !== true;
    ?>
    <div class="child-health-subblock">
        <label class="form-label fw-semibold mb-2">
            <i class="bi bi-chat-square-text me-1 text-primary"></i>ТНР
        </label>
        <p class="small text-muted mb-2">Тяжёлое нарушение речи — отмечается администрацией.</p>
        <div class="d-flex flex-wrap gap-3">
            <div class="form-check">
                <input type="radio" name="has_tnr" value="1" id="tnr_yes" class="form-check-input"<?= $yes ? ' checked' : '' ?>>
                <label class="form-check-label" for="tnr_yes">Да</label>
            </div>
            <div class="form-check">
                <input type="radio" name="has_tnr" value="0" id="tnr_no" class="form-check-input"<?= $no ? ' checked' : '' ?>>
                <label class="form-check-label" for="tnr_no">Нет</label>
            </div>
        </div>
    </div>
    <?php
}

function admin_render_child_health_form_block(array $allergies, array $selectedAllergyIds, ?bool $hasTnr): void
{
    ?>
    <div class="child-health-panel mb-4">
        <div class="child-health-panel-head mb-3">
            <h2 class="h6 fw-semibold mb-1">
                <i class="bi bi-heart-pulse me-1 text-primary"></i>Здоровье и аллергии
            </h2>
            <p class="small text-muted mb-0">
                Здесь указываются данные для питания и учёта. Медицинские справки родитель загружает отдельно — см. блок ниже.
            </p>
        </div>
        <div class="row g-3">
            <div class="col-md-5">
                <?php admin_render_child_tnr_fields($hasTnr); ?>
            </div>
            <div class="col-md-7">
                <?php admin_render_allergy_section($allergies, $selectedAllergyIds, true); ?>
            </div>
        </div>
    </div>
    <?php
}

function admin_render_child_health_documents_block(PDO $pdo, int $childId): void
{
    require_once __DIR__ . '/_child_documents_section.php';
    ?>
    <div class="child-health-panel-docs pt-4 mt-2 border-top">
        <?php admin_render_child_documents($pdo, $childId, true); ?>
    </div>
    <?php
}
