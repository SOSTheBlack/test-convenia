<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // Default test password
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state([
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with a specific email
     */
    public function withEmail(string $email): self
    {
        return $this->state([
            'email' => $email,
        ]);
    }

    /**
     * Create a user with a specific password
     */
    public function withPassword(string $password): self
    {
        return $this->state([
            'password' => Hash::make($password),
        ]);
    }
}
