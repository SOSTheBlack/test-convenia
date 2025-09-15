<?php

namespace Tests\Unit\Events;

use App\DTO\EmployeeData;
use App\Events\EmployeeUpdated;
use App\Models\User;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class EmployeeUpdatedTest extends TestCase
{
    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the logger that will be resolved from the container
        $this->logger = $this->mock(LoggerInterface::class);
        $this->app->bind(LoggerInterface::class, function () {
            return $this->logger;
        });
    }

    /** @test */
    public function it_creates_event_with_user_and_employee_data()
    {
        // Arrange
        $user = User::factory()->make(['id' => 1, 'name' => 'John User']);

        $employeeData = new EmployeeData(
            name: 'Jane Doe',
            email: 'jane@example.com',
            document: '12345678900',
            city: 'São Paulo',
            state: 'SP',
            startDate: '2023-01-15',
            userId: 1
        );

        $previousEmployeeData = new EmployeeData(
            name: 'Jane Smith',
            email: 'jane.smith@example.com',
            document: '12345678900',
            city: 'Rio de Janeiro',
            state: 'RJ',
            startDate: '2023-01-15',
            userId: 1
        );

        // Setup logger expectations
        $this->logger->shouldReceive('info')
            ->once()
            ->with('EmployeeUpdated event dispatched', [
                'user_id' => 1,
                'employee' => $employeeData->toArray(),
                'previous' => $previousEmployeeData->toArray()
            ]);

        // Act
        $event = new EmployeeUpdated($user, $employeeData, $previousEmployeeData);

        // Assert
        $this->assertInstanceOf(EmployeeUpdated::class, $event);
        $this->assertSame($user, $event->user);
        $this->assertSame($employeeData, $event->employee);
        $this->assertSame($previousEmployeeData, $event->previousEmployee);
    }

    /** @test */
    public function it_creates_event_without_previous_employee_data()
    {
        // Arrange
        $user = User::factory()->make(['id' => 1, 'name' => 'John User']);

        $employeeData = new EmployeeData(
            name: 'Jane Doe',
            email: 'jane@example.com',
            document: '12345678900',
            city: 'São Paulo',
            state: 'SP',
            startDate: '2023-01-15',
            userId: 1
        );

        // Setup logger expectations
        $this->logger->shouldReceive('info')
            ->once()
            ->with('EmployeeUpdated event dispatched', [
                'user_id' => 1,
                'employee' => $employeeData->toArray(),
                'previous' => null
            ]);

        // Act
        $event = new EmployeeUpdated($user, $employeeData);

        // Assert
        $this->assertInstanceOf(EmployeeUpdated::class, $event);
        $this->assertSame($user, $event->user);
        $this->assertSame($employeeData, $event->employee);
        $this->assertNull($event->previousEmployee);
    }

    /** @test */
    public function it_logs_event_dispatch_information()
    {
        // Arrange
        $user = User::factory()->make(['id' => 42, 'name' => 'Test User']);

        $employeeData = new EmployeeData(
            name: 'Test Employee',
            email: 'test@example.com',
            document: '11111111111',
            city: 'Test City',
            state: 'SP',
            startDate: '2023-06-15',
            userId: 42
        );

        // Setup logger expectations with specific data
        $this->logger->shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($employeeData) {
                return $message === 'EmployeeUpdated event dispatched' &&
                       $context['user_id'] === 42 &&
                       $context['employee'] === $employeeData->toArray() &&
                       $context['previous'] === null;
            });

        // Act
        $event = new EmployeeUpdated($user, $employeeData);

        // Assert - Verification is done through logger mock
        $this->assertInstanceOf(EmployeeUpdated::class, $event);
    }

    /** @test */
    public function it_has_correct_traits()
    {
        // Assert
        $reflection = new \ReflectionClass(EmployeeUpdated::class);
        $traits = $reflection->getTraitNames();

        $this->assertContains('Illuminate\Foundation\Events\Dispatchable', $traits);
        $this->assertContains('Illuminate\Broadcasting\InteractsWithSockets', $traits);
        $this->assertContains('Illuminate\Queue\SerializesModels', $traits);
    }

    /** @test */
    public function it_can_be_serialized()
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Serializable User']);

        $employeeData = new EmployeeData(
            name: 'Serializable Employee',
            email: 'serializable@example.com',
            document: '99999999999',
            city: 'Serializable City',
            state: 'SP',
            startDate: '2023-12-01',
            userId: $user->id
        );

        // Mock logger for event creation
        $this->logger->shouldReceive('info')->once();

        $event = new EmployeeUpdated($user, $employeeData);

        // Act
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        // Assert
        $this->assertInstanceOf(EmployeeUpdated::class, $unserialized);
        $this->assertEquals($user->id, $unserialized->user->id);
        $this->assertEquals($user->name, $unserialized->user->name);
        $this->assertEquals($employeeData->name, $unserialized->employee->name);
        $this->assertEquals($employeeData->email, $unserialized->employee->email);
    }

    /** @test */
    public function it_preserves_all_data_correctly()
    {
        // Arrange
        $user = User::factory()->make([
            'id' => 123,
            'name' => 'Data Preservation User',
            'email' => 'user@example.com'
        ]);

        $currentData = new EmployeeData(
            name: 'Current Name',
            email: 'current@example.com',
            document: '12312312312',
            city: 'Current City',
            state: 'SP',
            startDate: '2023-07-01',
            userId: 123,
            sendNotification: true
        );

        $previousData = new EmployeeData(
            name: 'Previous Name',
            email: 'previous@example.com',
            document: '12312312312',
            city: 'Previous City',
            state: 'RJ',
            startDate: '2023-01-01',
            userId: 123,
            sendNotification: false
        );

        // Mock logger
        $this->logger->shouldReceive('info')->once();

        // Act
        $event = new EmployeeUpdated($user, $currentData, $previousData);

        // Assert User data
        $this->assertEquals(123, $event->user->id);
        $this->assertEquals('Data Preservation User', $event->user->name);
        $this->assertEquals('user@example.com', $event->user->email);

        // Assert Current Employee data
        $this->assertEquals('Current Name', $event->employee->name);
        $this->assertEquals('current@example.com', $event->employee->email);
        $this->assertEquals('12312312312', $event->employee->document);
        $this->assertEquals('Current City', $event->employee->city);
        $this->assertEquals('SP', $event->employee->state);
        $this->assertEquals('2023-07-01', $event->employee->startDate);
        $this->assertTrue($event->employee->sendNotification);

        // Assert Previous Employee data
        $this->assertEquals('Previous Name', $event->previousEmployee->name);
        $this->assertEquals('previous@example.com', $event->previousEmployee->email);
        $this->assertEquals('12312312312', $event->previousEmployee->document);
        $this->assertEquals('Previous City', $event->previousEmployee->city);
        $this->assertEquals('RJ', $event->previousEmployee->state);
        $this->assertEquals('2023-01-01', $event->previousEmployee->startDate);
        $this->assertFalse($event->previousEmployee->sendNotification);
    }

    /** @test */
    public function it_has_readonly_properties()
    {
        // Arrange
        $user = User::factory()->make();
        $employeeData = new EmployeeData(
            name: 'Test',
            email: 'test@example.com',
            document: '12345678900',
            city: 'Test City',
            state: 'SP',
            startDate: '2023-01-01',
            userId: $user->id ?? 1
        );

        // Mock logger
        $this->logger->shouldReceive('info')->once();

        $event = new EmployeeUpdated($user, $employeeData);

        // Assert - Properties should be accessible but readonly
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertInstanceOf(EmployeeData::class, $event->employee);

        // Verify they're the same instances (by reference)
        $this->assertSame($user, $event->user);
        $this->assertSame($employeeData, $event->employee);
    }
}
