<?php

namespace App\Notifications;

use App\DTO\EmployeeData;
use App\DTO\UserData;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EmployeeUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EmployeeData $employee,
        public User $user,
        public ?EmployeeData $previousEmployee = null
    ) {
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
        $isNew = $this->previousEmployee === null;
        $subject = vsprintf('Funcionário %s - %s', [$isNew ? 'Criado' : 'Atualizado', $this->employee->name]);
        Log::info("Preparing email notification: {$subject}", ['employee' => $this->employee->toArray(), 'previous' => $this->previousEmployee ? $this->previousEmployee->toArray() : null, 'user' => $this->user->toArray()]);

        return (new MailMessage)
            ->subject($subject)
            ->from($this->user->email, $this->user->name)
            ->view(
                'emails.employee-update',
                [
                    'employee' => $this->employee,
                    'user' => $this->user,
                    'previousEmployee' => $this->previousEmployee,
                    'isNew' => $isNew
                ]
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'employee_id' => $this->employee->id,
            'updated_by' => $this->user->id,
            'is_new' => $this->previousEmployee === null,
        ];
    }
}
