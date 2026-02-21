<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Models\ServiceRequest;
use App\Repositories\AuditLogRepository;
use App\Repositories\ServiceRequestRepository;
use App\Services\RequestService;
use PHPUnit\Framework\TestCase;

class RequestServiceTest extends TestCase
{
    private RequestService            $service;
    private ServiceRequestRepository  $repo;
    private AuditLogRepository        $auditLog;
    private int                       $masterId;
    private int                       $dispatcherId;

    protected function setUp(): void
    {
        $pdo = Database::getConnection();
        $pdo->exec('DELETE FROM audit_log');
        $pdo->exec('DELETE FROM service_requests');
        $pdo->exec('DELETE FROM users');

        $pdo->exec("INSERT INTO users (name, username, password_hash, role) VALUES
            ('Диспетчер', 'disp', 'hash', 'dispatcher'),
            ('Мастер', 'master', 'hash', 'master')");

        $this->dispatcherId = (int) $pdo->query("SELECT id FROM users WHERE username = 'disp'")->fetchColumn();
        $this->masterId     = (int) $pdo->query("SELECT id FROM users WHERE username = 'master'")->fetchColumn();

        $this->service  = new RequestService();
        $this->repo     = new ServiceRequestRepository();
        $this->auditLog = new AuditLogRepository();
    }

    public function testCreateRequestHasStatusNew(): void
    {
        $request = $this->service->create('Иван Петров', '+7 900 111-22-33', 'ул. Тестовая, 1', 'Течёт кран', null);

        $this->assertInstanceOf(ServiceRequest::class, $request);
        $this->assertEquals(ServiceRequest::STATUS_NEW, $request->status);
        $this->assertEquals('Иван Петров', $request->clientName);
        $this->assertNull($request->assignedTo);
    }

    public function testCreateRequestWritesAuditLog(): void
    {
        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', $this->dispatcherId);

        $events = $this->auditLog->findByRequest($request->id);
        $this->assertCount(1, $events);
        $this->assertEquals('created', $events[0]->action);
        $this->assertEquals(ServiceRequest::STATUS_NEW, $events[0]->newStatus);
    }

    public function testAssignMasterChangesStatus(): void
    {
        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', null);

        $result  = $this->service->assign($request->id, $this->masterId, $this->dispatcherId);
        $updated = $this->repo->findById($request->id);

        $this->assertTrue($result);
        $this->assertEquals(ServiceRequest::STATUS_ASSIGNED, $updated->status);
        $this->assertEquals($this->masterId, $updated->assignedTo);
    }

    public function testAssignFailsIfNotNew(): void
    {
        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', null);
        $this->service->assign($request->id, $this->masterId, $this->dispatcherId);

        $result = $this->service->assign($request->id, $this->masterId, $this->dispatcherId);

        $this->assertFalse($result);
    }

    public function testTakeInProgressChangesStatus(): void
    {
        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', null);
        $this->service->assign($request->id, $this->masterId, $this->dispatcherId);

        $result  = $this->service->takeInProgress($request->id, $this->masterId);
        $updated = $this->repo->findById($request->id);

        $this->assertTrue($result);
        $this->assertEquals(ServiceRequest::STATUS_IN_PROGRESS, $updated->status);
    }

    public function testCompleteRequestChangesStatusToDone(): void
    {
        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', null);
        $this->service->assign($request->id, $this->masterId, $this->dispatcherId);
        $this->service->takeInProgress($request->id, $this->masterId);

        $result  = $this->service->complete($request->id, $this->masterId);
        $updated = $this->repo->findById($request->id);

        $this->assertTrue($result);
        $this->assertEquals(ServiceRequest::STATUS_DONE, $updated->status);
    }

    public function testCancelNewRequest(): void
    {
        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', null);

        $result  = $this->service->cancel($request->id, $this->dispatcherId);
        $updated = $this->repo->findById($request->id);

        $this->assertTrue($result);
        $this->assertEquals(ServiceRequest::STATUS_CANCELED, $updated->status);
    }

    public function testFullAuditTrail(): void
    {
        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', $this->dispatcherId);
        $this->service->assign($request->id, $this->masterId, $this->dispatcherId);
        $this->service->takeInProgress($request->id, $this->masterId);
        $this->service->complete($request->id, $this->masterId);

        $events  = $this->auditLog->findByRequest($request->id);
        $actions = array_column($events, 'action');

        $this->assertContains('created',          $actions);
        $this->assertContains('assigned',         $actions);
        $this->assertContains('taken_in_progress', $actions);
        $this->assertContains('completed',        $actions);
    }
}
