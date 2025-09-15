<?php

namespace Tests\Unit;

use App\DTO\EmployeeData;
use App\Events\EmployeeUpdated;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Employees\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EmployeeService $service;
    protected $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(EmployeeRepositoryInterface::class);
        $this->service = new EmployeeService($this->repositoryMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_employee_does_not_trigger_update_event()
    {
        Event::fake();

        $data = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '12345678901',
            city: 'São Paulo',
            state: 'SP',
            start_date: '2024-01-01',
            user_id: 1
        );

        $employee = new Employee($data->toArray());
        $employee->id = 1;

        $this->repositoryMock
            ->shouldReceive('findByDocument')
            ->with('12345678901')
            ->andReturn(null);

        $this->repositoryMock
            ->shouldReceive('createOrUpdate')
            ->with($data)
            ->andReturn($employee);

        $result = $this->service->createOrUpdateEmployee($data);

        $this->assertEquals($employee, $result);
        Event::assertNotDispatched(EmployeeUpdated::class);
    }

    public function test_update_employee_triggers_update_event()
    {
        Event::fake();

        $data = new EmployeeData(
            name: 'John Doe Updated',
            email: 'john.updated@example.com',
            document: '12345678901',
            city: 'Rio de Janeiro',
            state: 'RJ',
            start_date: '2024-01-01',
            user_id: 1
        );

        $existingEmployee = new Employee([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '12345678901',
            'city' => 'São Paulo',
            'state' => 'SP',
            'start_date' => '2024-01-01',
            'user_id' => 1
        ]);
        $existingEmployee->id = 1;

        $updatedEmployee = new Employee($data->toArray());
        $updatedEmployee->id = 1;

        $this->repositoryMock
            ->shouldReceive('findByDocument')
            ->with('12345678901')
            ->andReturn($existingEmployee);

        $this->repositoryMock
            ->shouldReceive('createOrUpdate')
            ->with($data)
            ->andReturn($updatedEmployee);

        $result = $this->service->createOrUpdateEmployee($data);

        $this->assertEquals($updatedEmployee, $result);
        Event::assertDispatched(EmployeeUpdated::class, function ($event) use ($updatedEmployee, $existingEmployee) {
            return $event->employee->id === $updatedEmployee->id &&
                   $event->previousEmployee->id === $existingEmployee->id;
        });
    }

    public function test_get_employees_by_user_calls_repository()
    {
        $userId = 1;
        $filters = ['department' => 'IT'];
        $perPage = 20;
        $expectedResult = 'paginated_result';

        $this->repositoryMock
            ->shouldReceive('findByUser')
            ->with($userId, $filters, $perPage)
            ->andReturn($expectedResult);

        $result = $this->service->getEmployeesByUser($userId, $filters, $perPage);

        $this->assertEquals($expectedResult, $result);
    }
}
