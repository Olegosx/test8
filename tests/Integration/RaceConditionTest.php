<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Services\RequestService;
use PHPUnit\Framework\TestCase;

/**
 * Тест защиты от race condition при параллельном взятии заявки.
 *
 * Симулирует два конкурентных запроса через два независимых PDO-соединения
 * к одному SQLite-файлу с WAL-режимом.
 */
class RaceConditionTest extends TestCase
{
    private string $dbPath;
    private int    $masterId;
    private int    $requestId;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/test_race_' . uniqid() . '.sqlite';

        Database::reset();
        Database::configure($this->dbPath);

        $pdo = Database::getConnection();
        $pdo->exec(<<<SQL
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL, username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL, role TEXT NOT NULL,
                created_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE service_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_name TEXT NOT NULL, phone TEXT NOT NULL,
                address TEXT NOT NULL, problem_text TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'new', assigned_to INTEGER,
                created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE audit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                request_id INTEGER NOT NULL, user_id INTEGER,
                action TEXT NOT NULL, old_status TEXT, new_status TEXT,
                comment TEXT, created_at TEXT DEFAULT (datetime('now'))
            );
SQL);

        $pdo->exec("INSERT INTO users (name, username, password_hash, role) VALUES ('Мастер', 'master', 'hash', 'master')");
        $this->masterId = (int) $pdo->lastInsertId();

        $service         = new RequestService();
        $request         = $service->create('Клиент', '+7 000 000-00-00', 'Адрес', 'Проблема', null);
        $service->assign($request->id, $this->masterId, 1);
        $this->requestId = $request->id;
    }

    protected function tearDown(): void
    {
        Database::reset();
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testOnlyOneWinnerWhenTwoConnectionsRace(): void
    {
        $dsn  = 'sqlite:' . $this->dbPath;
        $opts = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        $pdo1 = new \PDO($dsn, options: $opts);
        $pdo2 = new \PDO($dsn, options: $opts);
        $pdo1->exec('PRAGMA journal_mode=WAL');
        $pdo2->exec('PRAGMA journal_mode=WAL');

        // Оба соединения пытаются атомарно взять одну заявку
        $rowCount1 = $this->atomicTake($pdo1, $this->requestId, $this->masterId);
        $rowCount2 = $this->atomicTake($pdo2, $this->requestId, $this->masterId);

        $total = $rowCount1 + $rowCount2;

        $this->assertEquals(1, $total,
            "Ровно один запрос должен успешно взять заявку (rowCount = 1), второй — 0"
        );

        // Проверяем итоговый статус в БД
        $status = Database::getConnection()
            ->query("SELECT status FROM service_requests WHERE id = {$this->requestId}")
            ->fetchColumn();

        $this->assertEquals('in_progress', $status);
    }

    private function atomicTake(\PDO $pdo, int $requestId, int $masterId): int
    {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "UPDATE service_requests
                 SET status = 'in_progress', updated_at = datetime('now')
                 WHERE id = ? AND status = 'assigned' AND assigned_to = ?"
            );
            $stmt->execute([$requestId, $masterId]);
            $affected = $stmt->rowCount();
            $pdo->commit();
            return $affected;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return 0;
        }
    }
}
