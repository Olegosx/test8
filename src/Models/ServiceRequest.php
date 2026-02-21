<?php

declare(strict_types=1);

namespace App\Models;

class ServiceRequest
{
    public const STATUS_NEW         = 'new';
    public const STATUS_ASSIGNED    = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE        = 'done';
    public const STATUS_CANCELED    = 'canceled';

    public const STATUS_LABELS = [
        'new'         => 'Новая',
        'assigned'    => 'Назначена',
        'in_progress' => 'В работе',
        'done'        => 'Выполнена',
        'canceled'    => 'Отменена',
    ];

    public function __construct(
        public readonly int     $id,
        public readonly string  $clientName,
        public readonly string  $phone,
        public readonly string  $address,
        public readonly string  $problemText,
        public readonly string  $status,
        public readonly ?int    $assignedTo,
        public readonly ?string $assignedToName,
        public readonly string  $createdAt,
        public readonly string  $updatedAt,
    ) {}

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
