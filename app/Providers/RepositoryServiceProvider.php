<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Eloquent\EmployeeRepository;
use App\Services\Contracts\CsvProcessingServiceInterface;
use App\Services\Contracts\EmployeeServiceInterface;
use App\Services\Contracts\FileUploadServiceInterface;
use App\Services\Csv\CsvProcessingService;
use App\Services\Employees\EmployeeService;
use App\Services\Files\FileUploadService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class RepositoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(EmployeeRepositoryInterface::class, function ($app) {
            return new EmployeeRepository(
                new Employee(),
                $app->make(LoggerInterface::class)
            );
        });

        $this->app->bind(EmployeeServiceInterface::class, function ($app) {
            return new EmployeeService(
                $app->make(EmployeeRepositoryInterface::class),
                $app->make(LoggerInterface::class)
            );
        });

        $this->app->bind(FileUploadServiceInterface::class, FileUploadService::class);

        $this->app->bind(CsvProcessingServiceInterface::class, function ($app) {
            return new CsvProcessingService(
                $app->make(LoggerInterface::class)
            );
        });
    }

    public function provides(): array
    {
        return [
            EmployeeRepositoryInterface::class,
            EmployeeServiceInterface::class,
            FileUploadServiceInterface::class,
            CsvProcessingServiceInterface::class,
        ];
    }
}
