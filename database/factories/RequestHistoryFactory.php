<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\RequestHistory>
 */
class RequestHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'endpoint_id' => null,
            'method' => 'GET',
            'url' => fake()->url(),
            'request_headers' => [],
            'request_body' => null,
            'response_status' => 200,
            'response_headers' => ['Content-Type' => 'application/json'],
            'response_body' => '{"ok":true}',
            'response_time_ms' => fake()->numberBetween(20, 500),
            'error' => null,
        ];
    }
}
