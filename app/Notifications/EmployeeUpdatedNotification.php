<?php

namespace App\Notifications;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Employee $employee,
        public User $user,
        public ?Employee $previousEmployee = null
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
        $subject = 'Funcionário Atualizado - ' . $this->employee->name;

        return (new MailMessage)
            ->subject($subject)
            ->from($this->user->email ?? config('mail.from.address'), $this->user->name ?? config('mail.from.name'))
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
