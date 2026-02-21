<?php ob_start(); $base = defined('BASE_PATH') ? BASE_PATH : ''; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="mb-4">Подать заявку на ремонт</h2>

        <?php if ($success ?? null): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error ?? null): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= $base ?>/requests">
            <div class="mb-3">
                <label class="form-label">Имя клиента <span class="text-danger">*</span></label>
                <input type="text" name="client_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Телефон <span class="text-danger">*</span></label>
                <input type="tel" name="phone" class="form-control" placeholder="+7 900 000-00-00" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Адрес <span class="text-danger">*</span></label>
                <input type="text" name="address" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Описание проблемы <span class="text-danger">*</span></label>
                <textarea name="problem_text" class="form-control" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Отправить заявку</button>
        </form>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <p class="mt-3 text-muted small">
                Вы сотрудник службы? <a href="<?= $base ?>/login">Войти в систему</a>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$title   = 'Создать заявку';
include __DIR__ . '/../layout.php';
