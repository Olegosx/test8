<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $connection = null;
    private static string $dsn = '';

    public static function configure(string $path): void
    {
        self::$dsn = $path === ':memory:' ? 'sqlite::memory:' : 'sqlite:' . $path;
        self::$connection = null;
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            if (empty(self::$dsn)) {
                $dbPath = dirname(__DIR__, 2) . '/database/database.sqlite';
                self::$dsn = 'sqlite:' . $dbPath;
            }
            try {
                self::$connection = new PDO(self::$dsn, options: [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$connection->exec('PRAGMA journal_mode=WAL');
                self::$connection->exec('PRAGMA foreign_keys=ON');
            } catch (PDOException $e) {
                throw new RuntimeException('Ошибка подключения к БД: ' . $e->getMessage());
            }
        }

        return self::$connection;
    }

    public static function reset(): void
    {
        self::$connection = null;
        self::$dsn = '';
    }
}
