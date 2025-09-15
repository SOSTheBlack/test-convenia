<?php

namespace App\Notifications;

use App\DTO\EmployeeData;
use App\DTO\UserData;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EmployeeUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Collection $employees,
        // public User $user,
        // public ?EmployeeData $previousEmployee = null
    ) {
    }

    public function afterCommit()
    {
        // Após enviar a notificação, atualizar o campo send_notification para false
        $employeeRepository = app(EmployeeRepositoryInterface::class);
        $employeeRepository->updateNotificationStatus($this->employees->pluck('id')->toArray(), false);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->employees->first()->user;
        $this->employees->map(fn(Employee $employee) => $employee->isNew = $employee->created_at->equalTo($employee->updated_at));

        $mail = (new MailMessage)
            ->subject('Funcionários Criados/Atualizados')
            ->from($user->email, $user->name)
            ->view(
                'emails.employee-update',
                [
                    'employees' => $this->employees,
                    'user' => $user,
                ]
            );

        // Após enviar a notificação, atualizar o campo send_notification para false
        $employeeRepository = app(EmployeeRepositoryInterface::class);
        $employeeRepository->updateNotificationStatus($this->employees->pluck('id')->toArray(), false);

        return $mail;


    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $user = $this->employees->first()->user;

        return [
            'employee_ids' => $this->employees->pluck('id')->toArray(),
            'updated_by' => $user->id,
            'new_employees_count' => $this->employees->filter(function($employee) {
                return $employee->created_at->format('Y-m-d H:i:s') === $employee->updated_at->format('Y-m-d H:i:s');
            })->count(),
            'updated_employees_count' => $this->employees->filter(function($employee) {
                return $employee->created_at->format('Y-m-d H:i:s') !== $employee->updated_at->format('Y-m-d H:i:s');
            })->count(),
        ];
    }
}
