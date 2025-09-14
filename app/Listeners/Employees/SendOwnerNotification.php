<?php

namespace App\Listeners\Employees;

use App\Events\EmployeeCreated;
use App\Events\EmployeeUpdated;
use App\Notifications\EmployeeUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOwnerNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(EmployeeUpdated|EmployeeCreated $event): void
    {
        $employee = $event->employee;
        $user = $event->user;

        Log::info("Listener notifying user {$user->email}", ['employee' => $employee->toArray(), 'previous' => $event->previousEmployee ? $event->previousEmployee->toArray() : null]);

        // Enviar a notificação usando o sistema de notificações
        $user->notify(
            new EmployeeUpdatedNotification(
                $employee,
                $user,
                $event->previousEmployee
            )
        );
    }
}
