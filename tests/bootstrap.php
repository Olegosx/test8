<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;

// Используем in-memory SQLite для всех тестов
Database::configure(':memory:');

$pdo = Database::getConnection();
$pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS users (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        name          TEXT NOT NULL,
        username      TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role          TEXT NOT NULL CHECK(role IN ('dispatcher', 'master')),
        created_at    TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS service_requests (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        client_name  TEXT NOT NULL,
        phone        TEXT NOT NULL,
        address      TEXT NOT NULL,
        problem_text TEXT NOT NULL,
        status       TEXT NOT NULL DEFAULT 'new'
                     CHECK(status IN ('new', 'assigned', 'in_progress', 'done', 'canceled')),
        assigned_to  INTEGER REFERENCES users(id),
        created_at   TEXT DEFAULT (datetime('now')),
        updated_at   TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS audit_log (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        request_id INTEGER NOT NULL REFERENCES service_requests(id),
        user_id    INTEGER REFERENCES users(id),
        action     TEXT NOT NULL,
        old_status TEXT,
        new_status TEXT,
        comment    TEXT,
        created_at TEXT DEFAULT (datetime('now'))
    );
SQL);
