<?php

namespace App\Listeners;

use App\Events\EmployeeUpdated;
use App\Mail\EmployeeUpdateNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendEmployeeUpdateNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(EmployeeUpdated $event): void
    {
        $employee = $event->employee;
        $user = $employee->user;

        if ($user && $user->email) {
            Mail::to($user->email)->send(
                new EmployeeUpdateNotification(
                    $employee,
                    $user,
                    $event->previousEmployee
                )
            );
        }
    }
}