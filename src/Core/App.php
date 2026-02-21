<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\DispatcherController;
use App\Controllers\MasterController;
use App\Controllers\RequestController;

class App
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $auth       = new AuthController();
        $req        = new RequestController();
        $dispatcher = new DispatcherController();
        $master     = new MasterController();

        $this->router->get('/', fn() => $this->home());

        $this->router->get('/login',  [$auth, 'showLogin']);
        $this->router->post('/login', [$auth, 'login']);
        $this->router->post('/logout', [$auth, 'logout']);

        $this->router->get('/requests/create', [$req, 'showCreate']);
        $this->router->post('/requests',       [$req, 'store']);

        $this->router->get('/dispatcher',                                    [$dispatcher, 'panel']);
        $this->router->post('/dispatcher/requests/{id}/assign', [$dispatcher, 'assign']);
        $this->router->post('/dispatcher/requests/{id}/cancel', [$dispatcher, 'cancel']);

        $this->router->get('/master',                               [$master, 'panel']);
        $this->router->post('/master/requests/{id}/take',     [$master, 'take']);
        $this->router->post('/master/requests/{id}/complete', [$master, 'complete']);
    }

    private function home(): never
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('/requests/create');
        }
        $this->redirect(Session::userRole() === 'dispatcher' ? '/dispatcher' : '/master');
    }

    private function redirect(string $url): never
    {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        header('Location: ' . $base . $url);
        exit;
    }

    public function run(): void
    {
        $this->router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    }
}
