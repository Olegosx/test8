<?php ob_start(); $base = defined('BASE_PATH') ? BASE_PATH : ''; ?>

<div class="row justify-content-center">
    <div class="col-md-4">
        <h2 class="mb-4">Вход в систему</h2>

        <?php if ($error ?? null): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= $base ?>/login">
            <div class="mb-3">
                <label class="form-label">Имя пользователя</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Пароль</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Войти</button>
        </form>

        <div class="mt-4 p-3 bg-light rounded small text-muted">
            <strong>Тестовые пользователи:</strong>
            <ul class="mb-0 mt-1">
                <li><code>dispatcher1</code> / <code>dispatcher1</code> — Диспетчер</li>
                <li><code>master1</code> / <code>master1</code> — Мастер Алексей</li>
                <li><code>master2</code> / <code>master2</code> — Мастер Сергей</li>
            </ul>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title   = 'Вход';
include __DIR__ . '/../layout.php';
