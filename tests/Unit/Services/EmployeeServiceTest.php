<?php

namespace Tests\Unit\Services;

use App\DTO\EmployeeData;
use App\Events\EmployeeCreated;
use App\Events\EmployeeUpdated;
use App\Exceptions\CsvImportException;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Employees\EmployeeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    private EmployeeService $service;
    private EmployeeRepositoryInterface&MockInterface $repository;
    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->mock(EmployeeRepositoryInterface::class);
        $this->logger = $this->mock(LoggerInterface::class);
        $this->service = new EmployeeService($this->repository, $this->logger);
    }

    /** @test */
    public function it_creates_new_employee_when_not_exists()
    {
        // Arrange
        $employeeData = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '12345678900',
            city: 'São Paulo',
            state: 'SP',
            startDate: '2023-01-15',
            userId: 1
        );

        $expectedEmployee = Employee::factory()->make([
            'id' => 1,
            'user_id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '12345678900'
        ]);

        // Setup mocks
        $this->logger->shouldReceive('info')->times(2);

        $this->repository->shouldReceive('findByDocument')
            ->once()
            ->with('12345678900')
            ->andThrow(new ModelNotFoundException());

        $this->repository->shouldReceive('create')
            ->once()
            ->with($employeeData)
            ->andReturn($expectedEmployee);

        // Act
        $result = $this->service->createOrUpdateEmployee($employeeData);

        // Assert
        $this->assertInstanceOf(Employee::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
        $this->assertEquals('12345678900', $result->document);
    }

    /** @test */
    public function it_returns_null_when_employee_has_no_changes()
    {
        // Arrange
        $employeeData = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '12345678900',
            city: 'São Paulo',
            state: 'SP',
            startDate: '2023-01-15',
            userId: 1
        );

        $existingEmployee = $this->mock(Employee::class);

        // Setup mocks
        $this->logger->shouldReceive('info')->once();

        $this->repository->shouldReceive('findByDocument')
            ->once()
            ->with('12345678900')
            ->andReturn($existingEmployee);

        $existingEmployee->shouldReceive('fill')
            ->once()
            ->with($employeeData->toModelArray())
            ->andReturnSelf();

        $existingEmployee->shouldReceive('isDirty')
            ->once()
            ->andReturn(false);

        // Act
        $result = $this->service->createOrUpdateEmployee($employeeData);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_when_exception_occurs()
    {
        // Arrange
        $employeeData = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '12345678900',
            city: 'São Paulo',
            state: 'SP',
            startDate: '2023-01-15',
            userId: 1
        );

        // Setup mocks
        $this->logger->shouldReceive('info')->once();
        $this->logger->shouldReceive('error')->once();

        $this->repository->shouldReceive('findByDocument')
            ->once()
            ->with('12345678900')
            ->andThrow(new \Exception('Database error'));

        // Act
        $result = $this->service->createOrUpdateEmployee($employeeData);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_creates_or_updates_many_employees()
    {
        // Arrange
        $employeesData = new Collection([
            new EmployeeData(
                name: 'John Doe',
                email: 'john@example.com',
                document: '12345678900',
                city: 'São Paulo',
                state: 'SP',
                startDate: '2023-01-15',
                userId: 1
            ),
            new EmployeeData(
                name: 'Jane Doe',
                email: 'jane@example.com',
                document: '98765432100',
                city: 'Rio de Janeiro',
                state: 'RJ',
                startDate: '2023-01-16',
                userId: 1
            )
        ]);

        // Setup mocks
        $this->repository->shouldReceive('createOrUpdateMany')
            ->once()
            ->with($employeesData);

        // Act
        $this->service->createOrUpdateMany($employeesData);

        // Assert
        $this->expectNotToPerformAssertions();
    }

    /** @test */
    public function it_gets_employees_by_user()
    {
        // Arrange
        $userId = 1;
        $filters = ['department' => 'IT'];
        $perPage = 10;

        $expectedPaginator = $this->mock(LengthAwarePaginator::class);

        // Setup mocks
        $this->repository->shouldReceive('findByUser')
            ->once()
            ->with($userId, $filters, $perPage)
            ->andReturn($expectedPaginator);

        // Act
        $result = $this->service->getEmployeesByUser($userId, $filters, $perPage);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame($expectedPaginator, $result);
    }

    /** @test */
    public function it_finds_employee_by_document()
    {
        // Arrange
        $document = '12345678900';
        $expectedEmployee = Employee::factory()->make(['document' => $document]);

        // Setup mocks
        $this->repository->shouldReceive('findByDocument')
            ->once()
            ->with($document)
            ->andReturn($expectedEmployee);

        // Act
        $result = $this->service->findByDocument($document);

        // Assert
        $this->assertInstanceOf(Employee::class, $result);
        $this->assertEquals($document, $result->document);
    }

    /** @test */
    public function it_returns_null_when_employee_not_found_by_document()
    {
        // Arrange
        $document = '12345678900';

        // Setup mocks
        $this->repository->shouldReceive('findByDocument')
            ->once()
            ->with($document)
            ->andThrow(new ModelNotFoundException());

        // Act
        $result = $this->service->findByDocument($document);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_gets_employees_to_notify()
    {
        // Arrange
        $userId = 1;
        $expectedCollection = new Collection([
            Employee::factory()->make(['user_id' => $userId, 'send_notification' => true])
        ]);

        // Setup mocks
        $this->repository->shouldReceive('findToNotify')
            ->once()
            ->with($userId)
            ->andReturn($expectedCollection);

        // Act
        $result = $this->service->getEmployeesToNotify($userId);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_returns_empty_collection_when_no_employees_to_notify()
    {
        // Arrange
        $userId = 1;

        // Setup mocks
        $this->repository->shouldReceive('findToNotify')
            ->once()
            ->with($userId)
            ->andThrow(new ModelNotFoundException());

        // Act
        $result = $this->service->getEmployeesToNotify($userId);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    /** @test */
    public function it_marks_notifications_as_sent()
    {
        // Arrange
        $employeeIds = [1, 2, 3];

        // Setup mocks
        $this->repository->shouldReceive('updateNotificationStatus')
            ->once()
            ->with($employeeIds, false);

        // Act
        $this->service->markNotificationsSent($employeeIds);

        // Assert
        $this->expectNotToPerformAssertions();
    }
}
