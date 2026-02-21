<?php

use App\Models\ServiceRequest;

ob_start();

$base         = defined('BASE_PATH') ? BASE_PATH : '';
$badgeClasses = [
    'assigned'    => 'bg-info text-dark',
    'in_progress' => 'bg-warning text-dark',
];
?>

<h2 class="mb-4">Панель мастера</h2>

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

<?php if (empty($requests)): ?>
    <div class="alert alert-info">Назначенных заявок нет.</div>
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
            <?= htmlspecialchars(mb_substr($req->problemText, 0, 60)) ?>
            <?= mb_strlen($req->problemText) > 60 ? '…' : '' ?>
        </td>
        <td>
            <span class="badge <?= $badgeClasses[$req->status] ?? 'bg-secondary' ?>">
                <?= htmlspecialchars($req->statusLabel()) ?>
            </span>
        </td>
        <td class="text-muted"><?= htmlspecialchars($req->createdAt) ?></td>
        <td>
            <?php if ($req->status === 'assigned'): ?>
                <form method="POST" action="<?= $base ?>/master/requests/<?= $req->id ?>/take">
                    <button type="submit" class="btn btn-sm btn-warning">
                        ▶ Взять в работу
                    </button>
                </form>
            <?php elseif ($req->status === 'in_progress'): ?>
                <form method="POST" action="<?= $base ?>/master/requests/<?= $req->id ?>/complete"
                      onsubmit="return confirm('Завершить заявку #<?= $req->id ?>?')">
                    <button type="submit" class="btn btn-sm btn-success">
                        ✓ Завершить
                    </button>
                </form>
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
$title   = 'Панель мастера';
include __DIR__ . '/../layout.php';
