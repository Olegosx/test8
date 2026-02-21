<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Models\ServiceRequest;
use App\Repositories\ServiceRequestRepository;
use App\Services\RequestService;
use PHPUnit\Framework\TestCase;

class StatusTransitionTest extends TestCase
{
    private RequestService           $service;
    private ServiceRequestRepository $repo;
    private int                      $masterId;
    private int                      $dispatcherId;

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

        $this->service = new RequestService();
        $this->repo    = new ServiceRequestRepository();
    }

    public function testCannotTakeNewRequest(): void
    {
        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', null);

        $result = $this->service->takeInProgress($request->id, $this->masterId);

        $this->assertFalse($result, 'Нельзя взять в работу заявку со статусом "new"');
        $updated = $this->repo->findById($request->id);
        $this->assertEquals(ServiceRequest::STATUS_NEW, $updated->status);
    }

    public function testCannotCancelDoneRequest(): void
    {
        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', null);
        $this->service->assign($request->id, $this->masterId, $this->dispatcherId);
        $this->service->takeInProgress($request->id, $this->masterId);
        $this->service->complete($request->id, $this->masterId);

        $result = $this->service->cancel($request->id, $this->dispatcherId);

        $this->assertFalse($result, 'Нельзя отменить завершённую заявку');
    }

    public function testCannotCompleteAssignedRequest(): void
    {
        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', null);
        $this->service->assign($request->id, $this->masterId, $this->dispatcherId);

        $result = $this->service->complete($request->id, $this->masterId);

        $this->assertFalse($result, 'Нельзя завершить заявку со статусом "assigned"');
    }

    public function testSecondTakeReturnsFalse(): void
    {
        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', null);
        $this->service->assign($request->id, $this->masterId, $this->dispatcherId);

        $first  = $this->service->takeInProgress($request->id, $this->masterId);
        $second = $this->service->takeInProgress($request->id, $this->masterId);

        $this->assertTrue($first,   'Первый запрос должен успешно взять заявку');
        $this->assertFalse($second, 'Второй запрос должен вернуть false (заявка уже взята)');
    }

    public function testCannotTakeWithWrongMaster(): void
    {
        $pdo = Database::getConnection();
        $pdo->exec("INSERT INTO users (name, username, password_hash, role) VALUES ('Другой', 'other', 'hash', 'master')");
        $otherId = (int) $pdo->lastInsertId();

        $request = $this->service->create('Клиент', '+7 900 000-00-00', 'Адрес', 'Проблема', null);
        $this->service->assign($request->id, $this->masterId, $this->dispatcherId);

        $result = $this->service->takeInProgress($request->id, $otherId);

        $this->assertFalse($result, 'Другой мастер не должен брать чужую заявку');
    }
}
