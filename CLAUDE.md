# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Веб-приложение для управления заявками ремонтной службы. Стек: **PHP + SQLite**.

Роли: **диспетчер** и **мастер**. Авторизация — простая (выбор пользователя или логин/пароль по сидам в БД).

## Status Transitions

```
new → assigned (диспетчер назначает мастера)
assigned → in_progress (мастер берёт в работу — race-safe!)
in_progress → done (мастер завершает)
new/assigned → canceled (диспетчер отменяет)
```

Действие «Взять в работу» (`assigned → in_progress`) обязано быть атомарным — защита от race condition. При конкурентном запросе второй получает `409 Conflict`.

## Run Commands

**Docker:**
```bash
docker compose up
```

**Без Docker:**
```bash
composer install
php migrate.php      # или аналогичный скрипт миграций
php seed.php         # 1 диспетчер, 2 мастера, тестовые заявки
php -S localhost:8080 -t public/
```

**Тесты:**
```bash
./vendor/bin/phpunit
./vendor/bin/phpunit tests/SomeTest.php   # один тест
```

**Проверка race condition:**
```bash
bash race_test.sh
```

## Architecture

Слоистая архитектура:
- `public/` — точка входа (index.php, фронт-контроллер)
- `src/` — исходники (Controllers, Services, Repositories, Models)
- `database/migrations/` — миграции SQLite
- `database/seeds/` — сиды (диспетчер, мастера, заявки)
- `tests/` — автотесты (PHPUnit, минимум 5)

Модель `Request` (заявка): `clientName`, `phone`, `address`, `problemText`, `status`, `assignedTo`, `createdAt`, `updatedAt`.

История действий по заявке хранится в отдельной таблице (audit log / events).

## Required Files in Repo

- `README.md` — запуск, тестовые пользователи, инструкция по проверке гонки
- `DECISIONS.md` — 5–7 ключевых архитектурных решений
- `PROMPTS.md` — обязательно
- `race_test.sh` — скрипт параллельного тестирования `take`
- Миграции и сиды
