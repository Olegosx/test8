<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Services\AuthService;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/');
        }
        $this->render('auth/login', [
            'error' => Session::getFlash('error'),
        ]);
    }

    public function login(): void
    {
        $username = trim((string) $this->post('username', ''));
        $password = (string) $this->post('password', '');

        if ($username === '' || $password === '') {
            Session::flash('error', 'Введите имя пользователя и пароль.');
            $this->redirect('/login');
        }

        $user = $this->authService->authenticate($username, $password);
        if ($user === null) {
            Session::flash('error', 'Неверное имя пользователя или пароль.');
            $this->redirect('/login');
        }

        Session::set('user_id',   $user->id);
        Session::set('user_role', $user->role);
        Session::set('user_name', $user->name);

        $this->redirect($user->isDispatcher() ? '/dispatcher' : '/master');
    }

    public function logout(): void
    {
        Session::destroy();
        $this->redirect('/login');
    }
}
