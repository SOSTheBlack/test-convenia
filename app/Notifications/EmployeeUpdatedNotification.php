<?php

declare(strict_types=1);

namespace App\Notifications;

use App\DTO\EmployeeData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class EmployeeUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Collection $employees,
        public readonly string $action = 'updated'
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->getSubject();
        $viewData = $this->getViewData($notifiable);

        return (new MailMessage())
            ->subject($subject)
            ->view('emails.employee-update', $viewData);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'action' => $this->action,
            'employee_count' => $this->employees->count(),
            'employees' => $this->employees->map(fn (EmployeeData $employee) => [
                'name' => $employee->name,
                'email' => $employee->email,
                'document' => $employee->document,
            ])->toArray(),
            'notified_at' => now()->toISOString(),
        ];
    }

    private function getSubject(): string
    {
        $count = $this->employees->count();
        $employeeText = $count === 1 ? 'funcionário' : 'funcionários';

        return match ($this->action) {
            'created' => "Novo funcionário criado: {$count} {$employeeText}",
            'updated' => "Funcionário atualizado: {$count} {$employeeText}",
            default => "Funcionários processados: {$count} {$employeeText}",
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function getViewData(object $notifiable): array
    {
        return [
            'user' => $notifiable,
            'employees' => $this->employees,
            'action' => $this->action,
            'employeeCount' => $this->employees->count(),
            'actionText' => $this->getActionText(),
        ];
    }

    private function getActionText(): string
    {
        return match ($this->action) {
            'created' => 'criado(s)',
            'updated' => 'atualizado(s)',
            default => 'processado(s)',
        };
    }
}
