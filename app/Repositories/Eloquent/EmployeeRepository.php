<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTO\EmployeeData;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(
        private readonly Employee $model,
        private readonly LoggerInterface $logger
    ) {
    }

    public function findByDocument(string $document): Employee
    {
        return $this->model->where('document', $document)->firstOrFail();
    }

    public function create(EmployeeData $data): Employee
    {
        $model = $this->model->create($data->toModelArray());

        if (!$model) {
            throw new Exception('Falha ao criar o modelo');
        }

        return $model;
    }

    public function createOrUpdateMany(Collection $data): void
    {
        if ($data->isEmpty()) {
            $this->logger->error('Tentativa de criar/atualizar com coleção vazia');
            return;
        }

        $this->model->upsert(
            $data->map(function (EmployeeData $item) {
                $this->logger->info('Processando item para upsert', $item->toModelArray());
                return $item->toModelArray();
            })->toArray(),
            ['document'],
            $this->model->getFillable()
        );

        $this->logger->info('Operação de upsert concluída com sucesso');
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function findByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('user_id', $userId);

        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        return $query->paginate($perPage);
    }

    public function findToNotify(?int $userId = null, int $days = self::NOTIFICATION_DAYS): Collection
    {
        $query = $this->model
            ->where('send_notification', true)
            ->where('updated_at', '>=', now()->subDays($days));

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $results = $query->get();

        if ($results->isEmpty()) {
            throw new ModelNotFoundException('Nenhum registro encontrado.');
        }

        return $results;
    }

    /**
     * @param array<int> $employeeIds
     */
    public function updateNotificationStatus(array $employeeIds, bool $status): void
    {
        $this->model
            ->whereIn('id', $employeeIds)
            ->update(['send_notification' => $status]);
    }
}
