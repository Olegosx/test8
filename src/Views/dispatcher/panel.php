<?php

use App\Models\ServiceRequest;

ob_start();

$base         = defined('BASE_PATH') ? BASE_PATH : '';
$statusLabels = ServiceRequest::STATUS_LABELS;
$badgeClasses = [
    'new'         => 'bg-secondary',
    'assigned'    => 'bg-info text-dark',
    'in_progress' => 'bg-warning text-dark',
    'done'        => 'bg-success',
    'canceled'    => 'bg-danger',
];
?>

<h2 class="mb-4">Панель диспетчера</h2>

<?php if ($success ?? null): ?>
    <div class="alert alert-success alert-dismissible">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error ?? null): ?>
    <div class="alert alert-danger alert-dismissible">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="GET" action="<?= $base ?>/dispatcher" class="mb-3 d-flex gap-2 align-items-center flex-wrap">
    <label class="form-label mb-0 fw-semibold">Фильтр по статусу:</label>
    <select name="status" class="form-select w-auto">
        <option value="">Все статусы</option>
        <?php foreach ($statusLabels as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($statusFilter ?? '') === $value ? 'selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-outline-secondary btn-sm">Применить</button>
    <?php if ($statusFilter ?? null): ?>
        <a href="<?= $base ?>/dispatcher" class="btn btn-outline-danger btn-sm">Сбросить</a>
    <?php endif; ?>
    <span class="ms-auto text-muted small">Заявок: <?= count($requests) ?></span>
</form>

<?php if (empty($requests)): ?>
    <p class="text-muted">Заявок не найдено.</p>
<?php else: ?>

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle small">
    <thead class="table-dark">
    <tr>
        <th>#</th>
        <th>Клиент</th>
        <th>Телефон</th>
        <th>Адрес</th>
        <th>Проблема</th>
        <th>Статус</th>
        <th>Мастер</th>
        <th>Создана</th>
        <th>Действия</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($requests as $req): ?>
    <tr>
        <td class="text-muted"><?= $req->id ?></td>
        <td><?= htmlspecialchars($req->clientName) ?></td>
        <td><?= htmlspecialchars($req->phone) ?></td>
        <td><?= htmlspecialchars($req->address) ?></td>
        <td title="<?= htmlspecialchars($req->problemText) ?>">
            <?= htmlspecialchars(mb_substr($req->problemText, 0, 50)) ?>
            <?= mb_strlen($req->problemText) > 50 ? '…' : '' ?>
        </td>
        <td>
            <span class="badge <?= $badgeClasses[$req->status] ?? 'bg-secondary' ?>">
                <?= htmlspecialchars($req->statusLabel()) ?>
            </span>
        </td>
        <td><?= htmlspecialchars($req->assignedToName ?? '—') ?></td>
        <td class="text-muted"><?= htmlspecialchars($req->createdAt) ?></td>
        <td>
            <?php if ($req->status === 'new'): ?>
                <form method="POST" action="<?= $base ?>/dispatcher/requests/<?= $req->id ?>/assign"
                      class="d-flex gap-1 align-items-center mb-1">
                    <select name="master_id" class="form-select form-select-sm" required style="min-width:130px">
                        <option value="">Выбрать мастера…</option>
                        <?php foreach ($masters as $master): ?>
                            <option value="<?= $master->id ?>">
                                <?= htmlspecialchars($master->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Назначить</button>
                </form>
                <form method="POST" action="<?= $base ?>/dispatcher/requests/<?= $req->id ?>/cancel"
                      onsubmit="return confirm('Отменить заявку #<?= $req->id ?>?')">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Отменить</button>
                </form>
            <?php elseif ($req->status === 'assigned'): ?>
                <form method="POST" action="<?= $base ?>/dispatcher/requests/<?= $req->id ?>/cancel"
                      onsubmit="return confirm('Отменить заявку #<?= $req->id ?>?')">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Отменить</button>
                </form>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
$content = ob_get_clean();
$title   = 'Панель диспетчера';
include __DIR__ . '/../layout.php';
