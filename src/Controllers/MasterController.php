<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Repositories\ServiceRequestRepository;
use App\Services\RequestService;

class MasterController extends BaseController
{
    private RequestService            $service;
    private ServiceRequestRepository  $requests;

    public function __construct()
    {
        $this->service  = new RequestService();
        $this->requests = new ServiceRequestRepository();
    }

    public function panel(): void
    {
        $this->requireRole('master');

        $masterId = (int) Session::userId();
        $requests = $this->requests->findByMaster($masterId);

        $this->render('master/panel', [
            'requests' => $requests,
            'success'  => Session::getFlash('success'),
            'error'    => Session::getFlash('error'),
        ]);
    }

    public function take(string $id): void
    {
        $this->requireRole('master');

        $requestId = (int) $id;
        $masterId  = (int) Session::userId();

        // Освобождаем session lock до критической секции БД —
        // позволяет параллельным запросам пройти одновременно.
        session_write_close();

        $taken = $this->service->takeInProgress($requestId, $masterId);

        if ($taken) {
            // Переоткрываем сессию только для flash-сообщения при успехе.
            session_start();
            Session::flash('success', 'Заявка взята в работу.');
            $this->redirect('/master');
        }

        // 409 Conflict: PHP автоматически меняет код на 302 при header(Location:),
        // поэтому на конфликт рендерим страницу напрямую — без редиректа.
        http_response_code(409);
        $requests = $this->requests->findByMaster($masterId);
        $this->render('master/panel', [
            'requests' => $requests,
            'error'    => 'Заявка уже взята в работу или недоступна (409 Conflict).',
            'success'  => null,
        ]);
    }

    public function complete(string $id): void
    {
        $this->requireRole('master');

        $requestId = (int) $id;
        $masterId  = (int) Session::userId();

        $completed = $this->service->complete($requestId, $masterId);
        if ($completed) {
            Session::flash('success', 'Заявка завершена.');
        } else {
            Session::flash('error', 'Не удалось завершить заявку.');
        }
        $this->redirect('/master');
    }
}
