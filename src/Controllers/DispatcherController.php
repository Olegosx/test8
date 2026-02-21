<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Repositories\AuditLogRepository;
use App\Repositories\ServiceRequestRepository;
use App\Repositories\UserRepository;
use App\Services\RequestService;

class DispatcherController extends BaseController
{
    private RequestService              $service;
    private ServiceRequestRepository   $requests;
    private UserRepository             $users;
    private AuditLogRepository         $auditLog;

    public function __construct()
    {
        $this->service  = new RequestService();
        $this->requests = new ServiceRequestRepository();
        $this->users    = new UserRepository();
        $this->auditLog = new AuditLogRepository();
    }

    public function panel(): void
    {
        $this->requireRole('dispatcher');

        $statusFilter = $this->query('status') ?: null;
        $requests     = $this->requests->findAll($statusFilter);
        $masters      = $this->users->findByRole('master');

        $this->render('dispatcher/panel', [
            'requests'     => $requests,
            'masters'      => $masters,
            'statusFilter' => $statusFilter,
            'success'      => Session::getFlash('success'),
            'error'        => Session::getFlash('error'),
        ]);
    }

    public function assign(string $id): void
    {
        $this->requireRole('dispatcher');

        $requestId = (int) $id;
        $masterId  = (int) $this->post('master_id', 0);

        if ($masterId === 0) {
            Session::flash('error', 'Выберите мастера.');
            $this->redirect('/dispatcher');
        }

        $assigned = $this->service->assign($requestId, $masterId, (int) Session::userId());
        if ($assigned) {
            Session::flash('success', 'Мастер успешно назначен.');
        } else {
            Session::flash('error', 'Не удалось назначить мастера. Заявка уже не в статусе «Новая».');
        }
        $this->redirect('/dispatcher');
    }

    public function cancel(string $id): void
    {
        $this->requireRole('dispatcher');

        $canceled = $this->service->cancel((int) $id, (int) Session::userId());
        if ($canceled) {
            Session::flash('success', 'Заявка отменена.');
        } else {
            Session::flash('error', 'Не удалось отменить заявку. Возможно, она уже завершена или отменена.');
        }
        $this->redirect('/dispatcher');
    }
}
