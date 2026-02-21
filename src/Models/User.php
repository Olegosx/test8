<?php

declare(strict_types=1);

namespace App\Models;

class User
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly string $username,
        public readonly string $role,
        public readonly string $createdAt,
    ) {}

    public function isDispatcher(): bool
    {
        return $this->role === 'dispatcher';
    }

    public function isMaster(): bool
    {
        return $this->role === 'master';
    }
}
