<?php

namespace App\Repositories\Eloquent;

use App\DTO\EmployeeData;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    private Employee $model;

    public function __construct()
    {
        $this->model = new Employee();
    }

    public function findByDocument(string $document): ?Employee
    {
        return $this->model->where('document', $document)->firstOrFail();
    }

    public function createOrUpdate(EmployeeData $data): Employee
    {
        return $this->model->updateOrCreate(
            ['document' => $data->document],
            $data->toArray()
        );
    }

    public function create(EmployeeData $data): Employee
    {
        $model = $this->model->create($data->toArray());

        if (!$model) {
            throw new \Exception('Falha ao criar o modelo');
        }

        return $model;
    }

    public function createOrUpdateMany(Collection $data): void
    {
        if ($data->isEmpty()) {
            Log::error('IS EMPTYYYYYYYYYYYYY');
            return;
        }

        $this->model->upsert(
            $data->map(fn (EmployeeData $item) => $item->toModelArray())->toArray(),
            ['document'],
            $this->model->getFillable()
        );

        Log::alert('SALVOOOOOOOOOOOOOOOOOOOOOOOOU');
    }

    public function findByUser(int $userId, array $filters = [], int $perPage = 15)
    {
        $query = $this->model->where('user_id', $userId);

        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        return $query->paginate($perPage);
    }
}
