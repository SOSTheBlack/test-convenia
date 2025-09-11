<?php

namespace App\Listeners;

use App\Events\EmployeeUpdated;
use App\Mail\EmployeeUpdateNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmployeeUpdateNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(EmployeeUpdated $event): void
    {
        $employee = $event->employee;
        $user = $employee->user;

        // Enviar o e-mail de notificação
        Mail::to($user->email)->send(
            new EmployeeUpdateNotification(
                $employee,
                $user,
                $event->previousEmployee
            )
        );
    }
}
