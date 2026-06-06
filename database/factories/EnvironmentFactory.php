<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Environment>
 */
class EnvironmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->randomElement(['Development', 'Staging', 'Production']),
            'variables' => ['BASE_URL' => fake()->url()],
            'is_active' => false,
        ];
    }
}
