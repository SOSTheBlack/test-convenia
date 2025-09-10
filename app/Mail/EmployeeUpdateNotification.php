<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeUpdateNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public User $user,
        public ?Employee $previousEmployee = null
    ) {
    }

    public function build()
    {
        $subject = 'Funcionário Atualizado - ' . $this->employee->name;
        
        return $this->subject($subject)
                    ->view('emails.employee-update')
                    ->with([
                        'employee' => $this->employee,
                        'user' => $this->user,
                        'previousEmployee' => $this->previousEmployee,
                        'isNew' => $this->previousEmployee === null
                    ]);
    }
}