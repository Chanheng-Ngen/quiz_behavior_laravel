<?php

namespace Database\Factories;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'set_time_limit' => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'password' => $this->faker->optional()->password(),
            'creator_id' => \App\Models\User::factory(),
        ];
    }
}
