<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ServiceRequest;
use App\Repositories\AuditLogRepository;
use App\Repositories\ServiceRequestRepository;
use App\Repositories\UserRepository;

class RequestService
{
    public function __construct(
        private readonly ServiceRequestRepository $requests  = new ServiceRequestRepository(),
        private readonly AuditLogRepository       $auditLog  = new AuditLogRepository(),
        private readonly UserRepository           $users     = new UserRepository(),
    ) {}

    public function create(
        string $clientName,
        string $phone,
        string $address,
        string $problemText,
        ?int   $actorId = null,
    ): ServiceRequest {
        $id = $this->requests->create($clientName, $phone, $address, $problemText);
        $this->auditLog->log($id, $actorId, 'created', null, ServiceRequest::STATUS_NEW);
        return $this->requests->findById($id);
    }

    public function assign(int $requestId, int $masterId, int $actorId): bool
    {
        $request = $this->requests->findById($requestId);
        if ($request === null || $request->status !== ServiceRequest::STATUS_NEW) {
            return false;
        }
        $updated = $this->requests->assignMaster($requestId, $masterId);
        if ($updated) {
            $master = $this->users->findById($masterId);
            $this->auditLog->log(
                $requestId,
                $actorId,
                'assigned',
                ServiceRequest::STATUS_NEW,
                ServiceRequest::STATUS_ASSIGNED,
                'Назначен мастер: ' . ($master?->name ?? (string) $masterId),
            );
        }
        return $updated;
    }

    public function cancel(int $requestId, int $actorId): bool
    {
        $request = $this->requests->findById($requestId);
        if ($request === null) {
            return false;
        }
        $oldStatus = $request->status;
        $updated   = $this->requests->cancel($requestId);
        if ($updated) {
            $this->auditLog->log(
                $requestId,
                $actorId,
                'canceled',
                $oldStatus,
                ServiceRequest::STATUS_CANCELED,
            );
        }
        return $updated;
    }

    /**
     * Атомарное взятие заявки в работу. Возвращает false при гонке.
     */
    public function takeInProgress(int $requestId, int $masterId): bool
    {
        $taken = $this->requests->takeInProgress($requestId, $masterId);
        if ($taken) {
            $this->auditLog->log(
                $requestId,
                $masterId,
                'taken_in_progress',
                ServiceRequest::STATUS_ASSIGNED,
                ServiceRequest::STATUS_IN_PROGRESS,
            );
        }
        return $taken;
    }

    public function complete(int $requestId, int $masterId): bool
    {
        $updated = $this->requests->complete($requestId, $masterId);
        if ($updated) {
            $this->auditLog->log(
                $requestId,
                $masterId,
                'completed',
                ServiceRequest::STATUS_IN_PROGRESS,
                ServiceRequest::STATUS_DONE,
            );
        }
        return $updated;
    }
}
