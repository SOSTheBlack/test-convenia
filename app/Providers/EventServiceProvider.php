<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\EmployeeCreated;
use App\Events\EmployeeUpdated;
use App\Listeners\Employees\SendOwnerNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        EmployeeCreated::class => [
            SendOwnerNotification::class,
        ],
        EmployeeUpdated::class => [
            SendOwnerNotification::class,
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
