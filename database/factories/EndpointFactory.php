<?php

namespace Database\Factories;

use App\Enums\AuthType;
use App\Enums\HttpMethod;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Endpoint>
 */
class EndpointFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->words(2, true));

        return [
            'project_id' => Project::factory(),
            'category_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'method' => fake()->randomElement(HttpMethod::cases())->value,
            'url' => '{{BASE_URL}}/'.Str::slug(fake()->word()),
            'description' => fake()->sentence(),
            'auth_type' => AuthType::None->value,
            'auth_config' => [],
            'parameters' => [],
            'headers' => [],
            'query_params' => [],
            'path_params' => [],
            'request_body' => ['type' => 'none', 'content' => ''],
            'responses' => [],
            'sort_order' => 0,
        ];
    }
}
