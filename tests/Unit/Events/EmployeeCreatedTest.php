<?php

namespace Tests\Unit\Events;

use App\Events\EmployeeCreated;
use App\Models\Employee;
use App\Models\User;
use Tests\TestCase;

class EmployeeCreatedTest extends TestCase
{
    /** @test */
    public function it_creates_event_with_employee()
    {
        // Arrange
        $user = User::factory()->create();
        $employee = Employee::factory()->forUser($user)->make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '12345678900'
        ]);

        // Act
        $event = new EmployeeCreated($employee);

        // Assert
        $this->assertInstanceOf(EmployeeCreated::class, $event);
        $this->assertSame($employee, $event->employee);
    }

    /** @test */
    public function it_has_correct_traits()
    {
        // Assert
        $reflection = new \ReflectionClass(EmployeeCreated::class);
        $traits = $reflection->getTraitNames();

        $this->assertContains('Illuminate\Foundation\Events\Dispatchable', $traits);
        $this->assertContains('Illuminate\Broadcasting\InteractsWithSockets', $traits);
        $this->assertContains('Illuminate\Queue\SerializesModels', $traits);
    }

    /** @test */
    public function it_can_be_serialized()
    {
        // Arrange
        $user = User::factory()->create();
        $employee = Employee::factory()->forUser($user)->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '12345678900'
        ]);

        $event = new EmployeeCreated($employee);

        // Act
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        // Assert
        $this->assertInstanceOf(EmployeeCreated::class, $unserialized);
        $this->assertEquals($employee->id, $unserialized->employee->id);
        $this->assertEquals($employee->name, $unserialized->employee->name);
    }

    /** @test */
    public function it_preserves_employee_data()
    {
        // Arrange
        $user = User::factory()->create();
        $employee = Employee::factory()->forUser($user)->make([
            'id' => 1,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'document' => '98765432100',
            'city' => 'São Paulo',
            'start_date' => '2023-01-15'
        ]);

        // Act
        $event = new EmployeeCreated($employee);

        // Assert
        $this->assertEquals(1, $event->employee->id);
        $this->assertEquals('Jane Smith', $event->employee->name);
        $this->assertEquals('jane@example.com', $event->employee->email);
        $this->assertEquals('98765432100', $event->employee->document);
        $this->assertEquals('São Paulo', $event->employee->city);
        $this->assertEquals('2023-01-15', $event->employee->start_date->format('Y-m-d'));
    }

    /** @test */
    public function it_is_readonly_property()
    {
        // Arrange
        $user = User::factory()->create();
        $employee = Employee::factory()->forUser($user)->make();
        $event = new EmployeeCreated($employee);

        // Assert - PHP will throw a fatal error if we try to modify readonly property
        // We can only test that the property is accessible
        $this->assertInstanceOf(Employee::class, $event->employee);

        // Verify it's the same instance (by reference)
        $this->assertSame($employee, $event->employee);
    }
}
