<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Services\RequestService;

class RequestController extends BaseController
{
    private RequestService $service;

    public function __construct()
    {
        $this->service = new RequestService();
    }

    public function showCreate(): void
    {
        $this->render('requests/create', [
            'success' => Session::getFlash('success'),
            'error'   => Session::getFlash('error'),
        ]);
    }

    public function store(): void
    {
        $clientName  = trim((string) $this->post('client_name', ''));
        $phone       = trim((string) $this->post('phone', ''));
        $address     = trim((string) $this->post('address', ''));
        $problemText = trim((string) $this->post('problem_text', ''));

        $errors = [];
        if ($clientName === '')  $errors[] = 'Укажите имя клиента.';
        if ($phone === '')       $errors[] = 'Укажите телефон.';
        if ($address === '')     $errors[] = 'Укажите адрес.';
        if ($problemText === '') $errors[] = 'Опишите проблему.';

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('/requests/create');
        }

        $this->service->create($clientName, $phone, $address, $problemText, Session::userId());
        Session::flash('success', 'Заявка успешно создана!');
        $this->redirect('/requests/create');
    }
}
