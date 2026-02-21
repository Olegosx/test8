<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;

class UserRepository
{
    public function findByUsername(string $username): ?User
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT id, name, username, role, created_at FROM users WHERE username = ?'
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findById(int $id): ?User
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT id, name, username, role, created_at FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** @return User[] */
    public function findByRole(string $role): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT id, name, username, role, created_at FROM users WHERE role = ? ORDER BY name'
        );
        $stmt->execute([$role]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function getPasswordHash(string $username): ?string
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT password_hash FROM users WHERE username = ?'
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ? $row['password_hash'] : null;
    }

    private function hydrate(array $row): User
    {
        return new User(
            id:        (int) $row['id'],
            name:      $row['name'],
            username:  $row['username'],
            role:      $row['role'],
            createdAt: $row['created_at'],
        );
    }
}
