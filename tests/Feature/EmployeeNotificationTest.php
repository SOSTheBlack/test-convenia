<?php

namespace Tests\Feature;

use App\Events\EmployeeUpdated;
use App\Listeners\SendEmployeeUpdateNotification;
use App\Mail\EmployeeUpdateNotification;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmployeeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_update_notification_is_sent()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'user@example.com'
        ]);

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'name' => 'John Doe Updated',
            'email' => 'john.updated@example.com'
        ]);

        $previousEmployee = Employee::factory()->make([
            'user_id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $event = new EmployeeUpdated($employee, $previousEmployee);
        $listener = new SendEmployeeUpdateNotification();

        $listener->handle($event);

        Mail::assertSent(EmployeeUpdateNotification::class, function ($mail) use ($user, $employee, $previousEmployee) {
            return $mail->hasTo($user->email) &&
                   $mail->employee->id === $employee->id &&
                   $mail->previousEmployee->id === $previousEmployee->id;
        });
    }

    public function test_employee_update_notification_not_sent_when_user_has_no_email()
    {
        Mail::fake();

        // Create user with empty email 
        $user = User::factory()->create([
            'email' => ''
        ]);

        $employee = Employee::factory()->create([
            'user_id' => $user->id
        ]);

        $event = new EmployeeUpdated($employee);
        $listener = new SendEmployeeUpdateNotification();

        $listener->handle($event);

        Mail::assertNothingSent();
    }

    public function test_employee_update_notification_mail_content()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com'
        ]);

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'name' => 'John Doe Updated',
            'email' => 'john.updated@example.com',
            'document' => '11144477735',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ'
        ]);

        $previousEmployee = Employee::factory()->make([
            'user_id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'city' => 'São Paulo',
            'state' => 'SP'
        ]);

        $mail = new EmployeeUpdateNotification($employee, $user, $previousEmployee);
        $mailData = $mail->build();

        $this->assertEquals('Funcionário Atualizado - John Doe Updated', $mailData->subject);
        $this->assertEquals('emails.employee-update', $mailData->view);
        
        $viewData = $mailData->viewData;
        $this->assertEquals($employee, $viewData['employee']);
        $this->assertEquals($user, $viewData['user']);
        $this->assertEquals($previousEmployee, $viewData['previousEmployee']);
        $this->assertFalse($viewData['isNew']);
    }

    public function test_employee_creation_notification_mail_content()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com'
        ]);

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $mail = new EmployeeUpdateNotification($employee, $user, null);

        $mailData = $mail->build();
        $viewData = $mailData->viewData;
        
        $this->assertEquals($employee, $viewData['employee']);
        $this->assertEquals($user, $viewData['user']);
        $this->assertNull($viewData['previousEmployee']);
        $this->assertTrue($viewData['isNew']);
    }
}