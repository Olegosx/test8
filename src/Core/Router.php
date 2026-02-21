<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<string, array<string, callable|array<mixed>>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path     = parse_url($uri, PHP_URL_PATH) ?? '/';
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';

        // Убираем префикс базового пути (например, '/test8') перед матчингом маршрутов.
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = rtrim($path, '/') ?: '/';

        if (isset($this->routes[$method][$path])) {
            call_user_func($this->routes[$method][$path]);
            return;
        }

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = preg_replace('/\{(\w+)\}/', '([^/]+)', $pattern);
            if (preg_match('#^' . $regex . '$#', $path, $matches)) {
                array_shift($matches);
                call_user_func_array($handler, $matches);
                return;
            }
        }

        http_response_code(404);
        echo '<h1>404 — Страница не найдена</h1>';
    }
}
