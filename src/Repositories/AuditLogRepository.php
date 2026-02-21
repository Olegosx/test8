<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AuditEvent;

class AuditLogRepository
{
    public function log(
        int     $requestId,
        ?int    $userId,
        string  $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $comment = null,
    ): void {
        $stmt = Database::getConnection()->prepare(
            "INSERT INTO audit_log (request_id, user_id, action, old_status, new_status, comment, created_at)
             VALUES (?, ?, ?, ?, ?, ?, datetime('now'))"
        );
        $stmt->execute([$requestId, $userId, $action, $oldStatus, $newStatus, $comment]);
    }

    /** @return AuditEvent[] */
    public function findByRequest(int $requestId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT al.*, u.name AS user_name
             FROM audit_log al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE al.request_id = ?
             ORDER BY al.created_at ASC'
        );
        $stmt->execute([$requestId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    private function hydrate(array $row): AuditEvent
    {
        return new AuditEvent(
            id:        (int) $row['id'],
            requestId: (int) $row['request_id'],
            userId:    $row['user_id'] !== null ? (int) $row['user_id'] : null,
            userName:  $row['user_name'] ?? null,
            action:    $row['action'],
            oldStatus: $row['old_status'],
            newStatus: $row['new_status'],
            comment:   $row['comment'],
            createdAt: $row['created_at'],
        );
    }
}
