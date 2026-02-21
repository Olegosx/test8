<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;

abstract class BaseController
{
    protected function requireRole(string $role): void
    {
        if (!Session::isLoggedIn() || Session::userRole() !== $role) {
            $this->redirect('/login');
        }
    }

    protected function redirect(string $url): never
    {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        header('Location: ' . $base . $url);
        exit;
    }

    protected function render(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }
}
