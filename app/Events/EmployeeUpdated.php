<?php

namespace App\Events;

use App\DTO\EmployeeData;
use App\DTO\UserData;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EmployeeUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public EmployeeData $employee,
        public ?EmployeeData $previousEmployee = null
    ) {
        Log::info('event Updated dispatched', ['employee' => $employee->toArray(), 'previous' => $previousEmployee ? $previousEmployee->toArray() : null]);
    }
}
