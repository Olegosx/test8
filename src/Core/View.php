<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $path = dirname(__DIR__) . '/Views/' . $template . '.php';
        if (!file_exists($path)) {
            throw new \RuntimeException("Шаблон не найден: {$template}");
        }
        include $path;
    }
}
