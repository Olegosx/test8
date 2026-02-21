<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ServiceRequest;

class ServiceRequestRepository
{
    private const BASE_SELECT = '
        SELECT sr.*, u.name AS assigned_to_name
        FROM service_requests sr
        LEFT JOIN users u ON sr.assigned_to = u.id
    ';

    public function create(
        string $clientName,
        string $phone,
        string $address,
        string $problemText,
    ): int {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO service_requests (client_name, phone, address, problem_text, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'new', datetime('now'), datetime('now'))"
        );
        $stmt->execute([$clientName, $phone, $address, $problemText]);
        return (int) $pdo->lastInsertId();
    }

    public function findById(int $id): ?ServiceRequest
    {
        $stmt = Database::getConnection()->prepare(self::BASE_SELECT . 'WHERE sr.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** @return ServiceRequest[] */
    public function findAll(?string $status = null): array
    {
        $sql    = self::BASE_SELECT;
        $params = [];
        if ($status !== null) {
            $sql    .= 'WHERE sr.status = ? ';
            $params[] = $status;
        }
        $sql .= 'ORDER BY sr.created_at DESC';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    /** @return ServiceRequest[] */
    public function findByMaster(int $masterId): array
    {
        $stmt = Database::getConnection()->prepare(
            self::BASE_SELECT .
            "WHERE sr.assigned_to = ? AND sr.status IN ('assigned', 'in_progress')
             ORDER BY sr.created_at DESC"
        );
        $stmt->execute([$masterId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function assignMaster(int $requestId, int $masterId): bool
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE service_requests
             SET assigned_to = ?, status = 'assigned', updated_at = datetime('now')
             WHERE id = ? AND status = 'new'"
        );
        $stmt->execute([$masterId, $requestId]);
        return $stmt->rowCount() > 0;
    }

    public function cancel(int $requestId): bool
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE service_requests
             SET status = 'canceled', updated_at = datetime('now')
             WHERE id = ? AND status IN ('new', 'assigned')"
        );
        $stmt->execute([$requestId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Атомарный перевод assigned → in_progress.
     * Защита от гонки: условный UPDATE + транзакция.
     * Возвращает true при успехе, false если заявка уже взята.
     */
    public function takeInProgress(int $requestId, int $masterId): bool
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "UPDATE service_requests
                 SET status = 'in_progress', updated_at = datetime('now')
                 WHERE id = ? AND status = 'assigned' AND assigned_to = ?"
            );
            $stmt->execute([$requestId, $masterId]);
            $affected = $stmt->rowCount();
            $pdo->commit();
            return $affected > 0;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function complete(int $requestId, int $masterId): bool
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE service_requests
             SET status = 'done', updated_at = datetime('now')
             WHERE id = ? AND status = 'in_progress' AND assigned_to = ?"
        );
        $stmt->execute([$requestId, $masterId]);
        return $stmt->rowCount() > 0;
    }

    private function hydrate(array $row): ServiceRequest
    {
        return new ServiceRequest(
            id:             (int) $row['id'],
            clientName:     $row['client_name'],
            phone:          $row['phone'],
            address:        $row['address'],
            problemText:    $row['problem_text'],
            status:         $row['status'],
            assignedTo:     $row['assigned_to'] !== null ? (int) $row['assigned_to'] : null,
            assignedToName: $row['assigned_to_name'] ?? null,
            createdAt:      $row['created_at'],
            updatedAt:      $row['updated_at'],
        );
    }
}
