<?php
require_once '_auth.php';
require_once '_club_helpers.php';

$id = (int) ($_GET['id'] ?? 0);
$club = admin_club_find($pdo, $id);

if (!$club) {
    admin_flash('error', 'Кружок не найден');
    header('Location: clubs.php');
    exit;
}

$members = admin_club_members($pdo, $id);
$available = admin_club_children_available($pdo, $id);
$memberCount = count($members);
$max = $club['max_participants'] !== null ? (int) $club['max_participants'] : null;
$seatsFull = $max !== null && $memberCount >= $max;

admin_page_start('Состав кружка', $club['name']);
?>

<a href="clubs.php" class="admin-back-link d-inline-flex mb-3">
    <i class="bi bi-arrow-left"></i> К списку кружков
</a>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3"><?= htmlspecialchars($club['name']) ?></h2>
                <?php if (!empty($club['description'])): ?>
                    <p class="text-muted small"><?= nl2br(htmlspecialchars($club['description'])) ?></p>
                <?php endif; ?>
                <ul class="list-unstyled small mb-0">
                    <?php if (!empty($club['age_category'])): ?>
                        <li class="mb-2"><i class="bi bi-person me-2 text-muted"></i><?= htmlspecialchars($club['age_category']) ?></li>
                    <?php endif; ?>
                    <?php if (!empty($club['schedule'])): ?>
                        <li class="mb-2"><i class="bi bi-clock me-2 text-muted"></i><?= htmlspecialchars($club['schedule']) ?></li>
                    <?php endif; ?>
                    <li class="mb-2">
                        <i class="bi bi-person-badge me-2 text-muted"></i>
                        <?= $club['teacher_name'] ? htmlspecialchars($club['teacher_name']) : 'Руководитель не назначен' ?>
                    </li>
                    <li>
                        <i class="bi bi-people me-2 text-muted"></i>
                        <strong><?= admin_club_seats_label($memberCount, $max) ?></strong> участников
                    </li>
                </ul>
                <hr>
                <a href="edit_club.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i>Настройки кружка
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if (!$seatsFull && $available !== []): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h3 class="h6 fw-semibold mb-3">Записать ребёнка</h3>
                <form action="club_member_add.php" method="POST" class="row g-2 align-items-end">
                    <input type="hidden" name="club_id" value="<?= $id ?>">
                    <div class="col-md-9">
                        <label class="form-label small text-muted mb-1">Ребёнок</label>
                        <select name="child_id" class="form-select-club-child" required>
                            <?php admin_render_club_child_options($available); ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-accent w-100">
                            <i class="bi bi-plus-lg me-1"></i>Записать
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php elseif ($seatsFull): ?>
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i>Лимит участников заполнен. Увеличьте «Макс. участников» в настройках кружка или исключите кого-то из состава.
            </div>
        <?php elseif ($available === []): ?>
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <i class="bi bi-info-circle me-2"></i>Все дети сада уже записаны в этот кружок или ожидают рассмотрения заявки.
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 fw-semibold mb-0">Участники</h3>
                    <span class="badge badge-news"><?= $memberCount ?> чел.</span>
                </div>

                <?php if ($members === []): ?>
                    <p class="text-muted mb-0">Пока никто не записан. Выберите ребёнка выше, одобрите <a href="club_applications.php">заявку родителя</a> или запишите вручную.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 admin-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Ребёнок</th>
                                    <th>Группа</th>
                                    <th>Родитель</th>
                                    <th>Записан</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $m): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($m['full_name']) ?></td>
                                    <td><?= htmlspecialchars($m['group_name'] ?? '—') ?></td>
                                    <td class="small"><?= htmlspecialchars($m['parent_name'] ?? '—') ?></td>
                                    <td class="small text-muted">
                                        <?= !empty($m['enrolled_at']) ? date('d.m.Y', strtotime($m['enrolled_at'])) : '—' ?>
                                    </td>
                                    <td class="text-end">
                                        <form action="club_member_remove.php" method="POST" class="d-inline"
                                              onsubmit="return confirm('Исключить «<?= htmlspecialchars($m['full_name'], ENT_QUOTES) ?>» из кружка?')">
                                            <input type="hidden" name="club_id" value="<?= $id ?>">
                                            <input type="hidden" name="child_id" value="<?= (int) $m['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Исключить">
                                                <i class="bi bi-person-dash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php admin_page_end(); ?>
