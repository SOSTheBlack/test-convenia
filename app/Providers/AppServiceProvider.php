<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Gate definitions will be handled in AuthServiceProvider
    }

    public function boot(): void
    {
        Gate::define('employee', 'App\Policies\EmployeePolicy@authorize');
    }
}
