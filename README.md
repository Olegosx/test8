# Система управления заявками ремонтной службы

Веб-приложение для приёма и обработки заявок. Стек: PHP 8.1+ + SQLite.

## Запуск

### Вариант A: Docker Compose

```bash
docker compose up
```

Приложение доступно по адресу: **http://localhost:8083**

### Вариант B: Без Docker

```bash
composer install
php database/migrations/migrate.php
php database/seeds/seed.php
php -S localhost:8083 -t public/ public/index.php
```

Откройте **http://localhost:8083**

### Вариант C: Shared-хостинг (Apache)

1. Распаковать архив в нужный каталог на сервере (например, `test8/`).
2. Дать права на запись директории с базой данных:
   ```bash
   chmod 777 database/
   ```
3. Инициализировать БД через SSH:
   ```bash
   php database/migrations/migrate.php
   php database/seeds/seed.php
   ```
4. Убедиться, что на хостинге включён `mod_rewrite` и `.htaccess` обрабатывается (`AllowOverride All`).

Приложение доступно по адресу вида **http://example.com/test8/**

## Тестовые пользователи

| Логин         | Пароль        | Роль       |
|---------------|---------------|------------|
| `dispatcher1` | `dispatcher1` | Диспетчер  |
| `master1`     | `master1`     | Мастер     |
| `master2`     | `master2`     | Мастер     |

## Запуск тестов

```bash
./vendor/bin/phpunit
./vendor/bin/phpunit tests/Unit/RequestServiceTest.php   # один файл
./vendor/bin/phpunit --testsuite Unit                    # только Unit
```

## Проверка защиты от race condition

### Автоматически (скрипт)

```bash
bash race_test.sh http://localhost:8083 2
```

Скрипт отправляет два параллельных запроса на взятие заявки #2 (статус `assigned`).
Ожидаемый результат: один запрос получает `302` (успех), второй — `409 Conflict`.

### Вручную (два терминала)

**Терминал 1** — авторизуемся и берём заявку:
```bash
curl -c /tmp/s1.txt -b /tmp/s1.txt -X POST http://localhost:8083/login \
     -d "username=master1&password=master1" -L -o /dev/null -s
curl -c /tmp/s1.txt -b /tmp/s1.txt -X POST http://localhost:8083/master/requests/2/take \
     -v -s 2>&1 | grep "< HTTP"
```

**Терминал 2** — одновременно:
```bash
curl -c /tmp/s2.txt -b /tmp/s2.txt -X POST http://localhost:8083/login \
     -d "username=master1&password=master1" -L -o /dev/null -s
curl -c /tmp/s2.txt -b /tmp/s2.txt -X POST http://localhost:8083/master/requests/2/take \
     -v -s 2>&1 | grep "< HTTP"
```

Один из запросов вернёт `HTTP/1.1 302` (перенаправление на успех),
второй — `HTTP/1.1 409 Conflict`.

### Как это работает

Защита реализована через **условный `UPDATE` с транзакцией**:

```sql
BEGIN;
UPDATE service_requests
SET status = 'in_progress', updated_at = datetime('now')
WHERE id = ? AND status = 'assigned' AND assigned_to = ?;
-- rowCount() == 0 → уже взята → 409
COMMIT;
```

SQLite с WAL-режимом сериализует записи: только один из двух параллельных
`UPDATE` получит `rowCount = 1`. Второй увидит 0 затронутых строк и вернёт 409.
Дополнительно: в контроллере вызывается `session_write_close()` **до** запроса
к БД, чтобы PHP-сессионный lock не сериализовал запросы раньше времени.
