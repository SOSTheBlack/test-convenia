<?php

namespace App\Listeners;

use App\Events\EmployeeUpdated;
use App\Models\User;
use App\Notifications\EmployeeUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendEmployeeUpdateNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(EmployeeUpdated $event): void
    {
        $employee = $event->employee;
        $user = $employee->user;

        if (!$user) {
            // Se não houver usuário associado, tente encontrar o primeiro usuário do sistema
            $user = User::first();

            if (!$user) {
                // Não há usuários no sistema, não podemos enviar a notificação
                Log::warning('Não foi possível enviar notificação de funcionário atualizado: nenhum usuário encontrado');
                return;
            }
        }

        // Enviar a notificação usando o sistema de notificações
        $user->notify(
            new EmployeeUpdatedNotification(
                $employee,
                $user,
                $event->previousEmployee
            )
        );
    }
}
