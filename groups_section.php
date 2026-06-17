<?php

/**

 * @var array $groups_list

 * @var array $clubs_list

 */

$groupsByBand = public_groups_by_age_band($groups_list);

$hasGroups = $groupsByBand !== [];

$hasClubs = $clubs_list !== [];

if (!$hasGroups && !$hasClubs) {

    return;

}

?>

<section id="groups" class="section-padding bg-section-warm">

    <div class="container">

        <div class="text-center mb-5 section-header">

            <h2 class="section-title">Группы</h2>

            <p class="section-subtitle mx-auto">Возрастные группы и кружки дополнительного образования</p>

        </div>



        <?php foreach (dou_age_band_order() as $band):

            if (empty($groupsByBand[$band])) {

                continue;

            }

            $meta = dou_age_band_meta($band);

        ?>

        <div class="subdiv-block mb-5">

            <div class="subdiv-block-head">

                <div class="subdiv-block-icon" aria-hidden="true">

                    <i class="bi <?= htmlspecialchars($meta['icon']) ?>"></i>

                </div>

                <div>

                    <h3 class="subdiv-block-title"><?= htmlspecialchars($meta['title']) ?></h3>

                    <?php if ($meta['range'] !== ''): ?>

                        <p class="subdiv-block-range mb-0"><?= htmlspecialchars($meta['range']) ?></p>

                    <?php endif; ?>

                </div>

            </div>



            <div class="row g-3">

                <?php foreach ($groupsByBand[$band] as $group): ?>

                <div class="col-md-6">

                    <a href="group.php?id=<?= (int)$group['id'] ?>" class="subdiv-card">

                        <div class="subdiv-card-main">

                            <h4 class="subdiv-card-name"><?= htmlspecialchars($group['name']) ?></h4>

                            <span class="subdiv-card-label">Воспитатели</span>

                            <p class="subdiv-card-person mb-0">

                                <?= !empty($group['teachers_names'])

                                    ? htmlspecialchars($group['teachers_names'])

                                    : (!empty($group['teacher_name'])

                                        ? htmlspecialchars($group['teacher_name'])

                                        : '<span class="text-muted">Назначается</span>') ?>

                            </p>

                        </div>

                        <span class="subdiv-card-go" aria-hidden="true">

                            <i class="bi bi-chevron-right"></i>

                        </span>

                    </a>

                </div>

                <?php endforeach; ?>

            </div>

        </div>

        <?php endforeach; ?>



        <?php if ($hasClubs): ?>

        <div class="subdiv-block mb-0" id="clubs">

            <div class="subdiv-block-head">

                <div class="subdiv-block-icon subdiv-block-icon--clubs" aria-hidden="true">

                    <i class="bi bi-palette"></i>

                </div>

                <div>

                    <h3 class="subdiv-block-title">Кружки и секции</h3>

                    <p class="subdiv-block-range mb-0">Дополнительные занятия для детей</p>

                </div>

            </div>



            <div class="row g-3">

                <?php foreach ($clubs_list as $club): ?>

                <div class="col-md-6">

                    <a href="club.php?id=<?= (int)$club['id'] ?>" class="subdiv-card">

                        <div class="subdiv-card-main">

                            <h4 class="subdiv-card-name"><?= htmlspecialchars($club['name']) ?></h4>

                            <?php if (!empty($club['age_category'])): ?>

                                <p class="subdiv-card-meta small text-muted mb-1">

                                    <?= htmlspecialchars($club['age_category']) ?>

                                </p>

                            <?php endif; ?>

                            <span class="subdiv-card-label">Руководитель</span>

                            <p class="subdiv-card-person mb-0">

                                <?= !empty($club['teacher_name'])

                                    ? htmlspecialchars($club['teacher_name'])

                                    : '<span class="text-muted">Назначается</span>' ?>

                            </p>

                            <?php if (!empty($club['schedule'])): ?>

                                <p class="subdiv-card-schedule small text-muted mb-0 mt-2">

                                    <i class="bi bi-clock me-1"></i><?= htmlspecialchars($club['schedule']) ?>

                                </p>

                            <?php endif; ?>

                        </div>

                        <span class="subdiv-card-go" aria-hidden="true">

                            <i class="bi bi-chevron-right"></i>

                        </span>

                    </a>

                </div>

                <?php endforeach; ?>

            </div>

        </div>

        <?php endif; ?>

    </div>

</section>


