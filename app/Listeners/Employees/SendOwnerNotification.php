<?php

namespace App\Listeners\Employees;

use App\Events\EmployeeUpdated;
use App\Notifications\EmployeeUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOwnerNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(EmployeeUpdated $event): void
    {
        $employee = $event->employee;
        $user = $employee->user;

        Log::info("Listener notifying user {$user->email} about employee {$employee->name}");

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
