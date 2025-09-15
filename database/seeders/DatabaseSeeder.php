<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Throwable;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        try {
            $user = User::where('email', 'admin@example.com')->firstOrFail();
        } catch (Throwable $exception) {
            $user = User::factory([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
            ])->create();
        }
    }
}
