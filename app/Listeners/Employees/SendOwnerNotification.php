<?php

declare(strict_types=1);

namespace App\Listeners\Employees;

use App\Events\EmployeeCreated;
use App\Events\EmployeeUpdated;
use App\Notifications\EmployeeUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

class SendOwnerNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(EmployeeUpdated|EmployeeCreated $event): void
    {
        $this->logger->info('Sending notification to user', [
            'user_id' => $event->user->id,
            'event_type' => get_class($event)
        ]);

        try {
            if ($event instanceof EmployeeUpdated) {
                $this->handleEmployeeUpdated($event);
            } elseif ($event instanceof EmployeeCreated) {
                $this->handleEmployeeCreated($event);
            }

            $this->logger->info('Notification sent successfully', [
                'user_id' => $event->user->id
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send notification', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleEmployeeUpdated(EmployeeUpdated $event): void
    {
        $event->user->notify(
            new EmployeeUpdatedNotification(
                collect([$event->employee]),
                'updated'
            )
        );
    }

    private function handleEmployeeCreated(EmployeeCreated $event): void
    {
        $employeeData = \App\DTO\EmployeeData::fromModel($event->employee);

        /** @var \App\Models\User $user */
        $user = $event->employee->user;

        if ($user) {
            $user->notify(
                new EmployeeUpdatedNotification(
                    collect([$employeeData]),
                    'created'
                )
            );
        }
    }
}
