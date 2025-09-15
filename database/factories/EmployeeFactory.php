<?php

namespace Database\Factories;

use App\Enums\BrazilianState;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'document' => $this->faker->unique()->numerify('###########'),
            'city' => $this->faker->city,
            'state' => $this->faker->randomElement(BrazilianState::cases())->value,
            'start_date' => $this->faker->date('Y-m-d'),
            'send_notification' => $this->faker->boolean(30), // 30% chance of true
        ];
    }

    /**
     * Create an employee for a specific user
     */
    public function forUser(User $user): self
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create an employee with specific document
     */
    public function withDocument(string $document): self
    {
        return $this->state([
            'document' => preg_replace('/[^0-9]/', '', $document),
        ]);
    }

    /**
     * Create an employee that should receive notifications
     */
    public function withNotifications(): self
    {
        return $this->state([
            'send_notification' => true,
        ]);
    }

    /**
     * Create an employee that should not receive notifications
     */
    public function withoutNotifications(): self
    {
        return $this->state([
            'send_notification' => false,
        ]);
    }
}
