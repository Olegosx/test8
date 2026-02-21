<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Ремонтная служба', ENT_QUOTES) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php $base = defined('BASE_PATH') ? BASE_PATH : ''; ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?>/">🔧 Ремонтная служба</a>
        <div class="navbar-nav ms-auto d-flex flex-row align-items-center gap-3">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="navbar-text">
                    <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>
                    <span class="badge bg-secondary ms-1">
                        <?= $_SESSION['user_role'] === 'dispatcher' ? 'Диспетчер' : 'Мастер' ?>
                    </span>
                </span>
                <form method="POST" action="<?= $base ?>/logout" class="d-inline">
                    <button type="submit" class="btn btn-outline-light btn-sm">Выйти</button>
                </form>
            <?php else: ?>
                <a class="nav-link text-white" href="<?= $base ?>/login">Войти</a>
                <a class="nav-link text-white" href="<?= $base ?>/requests/create">Подать заявку</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <?= $content ?? '' ?>
</div>
</body>
</html>
