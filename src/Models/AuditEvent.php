<?php

declare(strict_types=1);

namespace App\Models;

class AuditEvent
{
    public function __construct(
        public readonly int     $id,
        public readonly int     $requestId,
        public readonly ?int    $userId,
        public readonly ?string $userName,
        public readonly string  $action,
        public readonly ?string $oldStatus,
        public readonly ?string $newStatus,
        public readonly ?string $comment,
        public readonly string  $createdAt,
    ) {}
}
