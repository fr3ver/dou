<?php

/** @var array|null $employee */

/** @var array $qualificationCourses */

/** @var array|null $qualificationLatest */

/** @var string $qualificationStatus */



if (!$employee) {

    return;

}



$course = $qualificationCourses[0] ?? null;

?>

<div class="lk-panel lk-block mb-4">

        <h2 class="h5 fw-bold mb-3"><i class="bi bi-mortarboard me-2"></i>Повышение квалификации</h2>



        <?php if ($qualificationLatest): ?>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

            <span class="text-muted">Следующий срок:</span>

            <strong><?= admin_qualification_format_date($qualificationLatest['next_due_on'] ?? null) ?></strong>

            <span class="badge <?= match ($qualificationStatus) {

                'overdue' => 'bg-danger',

                'soon'    => 'bg-warning text-dark',

                'ok'      => 'bg-success',

                default   => 'bg-secondary',

            } ?>"><?= htmlspecialchars(admin_qualification_status_label($qualificationStatus)) ?></span>

        </div>

        <?php if ($qualificationStatus === 'soon' || $qualificationStatus === 'overdue'): ?>

        <div class="alert alert-warning py-2 small mb-3">

            <?php if ($qualificationStatus === 'overdue'): ?>

            Срок повышения квалификации истёк. Обратитесь к старшему воспитателю или заведующей для записи на курс.

            <?php else: ?>

            До срока следующего повышения квалификации осталось менее <?= QUALIFICATION_REMINDER_DAYS ?> дней.

            <?php endif; ?>

        </div>

        <?php endif; ?>

        <?php else: ?>

        <p class="text-muted small mb-3">Данные о курсе пока не внесены. Уточните у администрации.</p>

        <?php endif; ?>



        <?php if ($course): ?>

        <div class="small">

            <strong><?= htmlspecialchars($course['title']) ?></strong>

            <?php if (!empty($course['organization'])): ?>

            <span class="text-muted"> — <?= htmlspecialchars($course['organization']) ?></span>

            <?php endif; ?>

            <br>

            <span class="text-muted">Окончание: <?= admin_qualification_format_date($course['completed_on']) ?></span>

            <?php if (!empty($course['hours'])): ?>

            <span class="text-muted"> · <?= (int)$course['hours'] ?> ч.</span>

            <?php endif; ?>

        </div>

        <?php endif; ?>

</div>

