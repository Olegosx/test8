<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

class AuthService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
    ) {}

    public function authenticate(string $username, string $password): ?User
    {
        $hash = $this->users->getPasswordHash($username);
        if ($hash === null || !password_verify($password, $hash)) {
            return null;
        }
        return $this->users->findByUsername($username);
    }
}
