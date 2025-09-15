<?php

namespace App\Console\Commands\Employees;

use App\Notifications\EmployeeUpdatedNotification;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class UpdateNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:employees:update-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar notificações para os donos dos funcionários que foram criados/atualizados recentemente';

    /**
     * Execute the console command.
     */
    public function handle(EmployeeRepositoryInterface $employeeRepository): int
    {
        try {
            $employeeRepository
                ->findToNotify()
                ->groupBy('user_id')
                ->each(function (Collection $employeeGroup) {
                    $employeeGroup
                        ->first()
                        ->user
                        ->notifyNow(new EmployeeUpdatedNotification($employeeGroup));

                    $this->info('Notificações enviadas para o usuário: ' . $employeeGroup->first()->user->email);
                });

                Log::info('Notificações enviadas com sucesso!');

            $this->info('Notificações enviadas com sucesso!');
            return Command::SUCCESS;
        } catch(ModelNotFoundException $modelNotFoundException) {
            $this->alert('Nenhum funcionário encontrado para notificar.');
            return Command::INVALID;
        } catch (Exception $exception) {
            $this->error('Erro ao enviar notificações: ' . $exception->getMessage());
            return Command::FAILURE;
        }
    }
}
