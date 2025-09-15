<?php

declare(strict_types=1);

namespace App\Events;

use App\DTO\EmployeeData;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

class EmployeeUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly EmployeeData $employee,
        public readonly ?EmployeeData $previousEmployee = null
    ) {
        app(LoggerInterface::class)->info('EmployeeUpdated event dispatched', [
            'user_id' => $this->user->id,
            'employee' => $this->employee->toArray(),
            'previous' => $this->previousEmployee?->toArray()
        ]);
    }
}
