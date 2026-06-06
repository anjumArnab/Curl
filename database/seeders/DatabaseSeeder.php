<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'Team Member',
            'email' => 'member@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->seedCatApi($admin);
    }

    protected function seedCatApi(User $admin): void
    {
        $project = Project::create([
            'name' => 'The Cat API',
            'slug' => 'the-cat-api',
            'description' => "Live, interactive documentation for [The Cat API](https://thecatapi.com).\n\n"
                ."The **Images**, **Breeds** and **Categories** endpoints work out of the box — just pick the "
                ."`Development` environment and hit **Send**.\n\n"
                ."For **Favourites** and **Votes** you need a free key from "
                ."[thecatapi.com/signup](https://thecatapi.com/signup): paste it into the `API_KEY` "
                ."variable of the `Development` environment and it is sent as the `x-api-key` header.",
            'visibility' => 'private',
            'created_by' => $admin->id,
        ]);

        $project->environments()->create([
            'name' => 'Development',
            'variables' => ['BASE_URL' => 'https://api.thecatapi.com/v1', 'API_KEY' => ''],
            'is_active' => true,
        ]);

        $images = Category::create(['project_id' => $project->id, 'name' => 'Images', 'sort_order' => 1]);
        $breeds = Category::create(['project_id' => $project->id, 'name' => 'Breeds', 'sort_order' => 2]);
        $categories = Category::create(['project_id' => $project->id, 'name' => 'Categories', 'sort_order' => 3]);
        $favourites = Category::create(['project_id' => $project->id, 'name' => 'Favourites', 'sort_order' => 4]);
        $votes = Category::create(['project_id' => $project->id, 'name' => 'Votes', 'sort_order' => 5]);

        $apiKeyAuth = [
            'auth_type' => 'api_key',
            'auth_config' => ['key' => 'x-api-key', 'value' => '{{API_KEY}}', 'in' => 'header'],
            'headers' => [
                ['name' => 'x-api-key', 'required' => true, 'description' => 'Your Cat API key'],
            ],
        ];

        /* ---- Images (no key required) ---- */
        $this->makeEndpoint($project->id, $images->id, 'Search Images', 'GET', '/images/search', [
            'description' => 'Returns one or more random cat images. Works without an API key.',
            'query_params' => [
                ['name' => 'limit', 'type' => 'integer', 'required' => false, 'description' => 'How many images to return (1 without a key)'],
                ['name' => 'breed_ids', 'type' => 'string', 'required' => false, 'description' => 'Comma-separated breed ids, e.g. beng'],
                ['name' => 'size', 'type' => 'string', 'required' => false, 'description' => 'thumb | small | med | full'],
                ['name' => 'has_breeds', 'type' => 'boolean', 'required' => false, 'description' => 'Only return images with breed data'],
            ],
            'responses' => [
                ['status' => '200', 'description' => 'OK', 'example' => "[\n  {\n    \"id\": \"0XYvRd7oD\",\n    \"url\": \"https://cdn2.thecatapi.com/images/0XYvRd7oD.jpg\",\n    \"width\": 1204,\n    \"height\": 1445\n  }\n]"],
            ],
        ]);

        $this->makeEndpoint($project->id, $images->id, 'Get Image', 'GET', '/images/{image_id}', [
            'description' => 'Fetch a single image by its id.',
            'path_params' => [
                ['name' => 'image_id', 'type' => 'string', 'required' => true, 'description' => 'The image id, e.g. 0XYvRd7oD'],
            ],
            'responses' => [
                ['status' => '200', 'description' => 'OK', 'example' => "{\n  \"id\": \"0XYvRd7oD\",\n  \"url\": \"https://cdn2.thecatapi.com/images/0XYvRd7oD.jpg\",\n  \"width\": 1204,\n  \"height\": 1445\n}"],
                ['status' => '404', 'description' => 'Not found', 'example' => "{\n  \"message\": \"NOT_FOUND\"\n}"],
            ],
        ]);

        /* ---- Breeds (no key required) ---- */
        $this->makeEndpoint($project->id, $breeds->id, 'List Breeds', 'GET', '/breeds', [
            'description' => 'Returns the full list of cat breeds.',
            'query_params' => [
                ['name' => 'limit', 'type' => 'integer', 'required' => false, 'description' => 'Items per page'],
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Page number'],
            ],
            'responses' => [
                ['status' => '200', 'description' => 'OK', 'example' => "[\n  {\n    \"id\": \"abys\",\n    \"name\": \"Abyssinian\",\n    \"temperament\": \"Active, Energetic, Independent\",\n    \"origin\": \"Egypt\"\n  }\n]"],
            ],
        ]);

        $this->makeEndpoint($project->id, $breeds->id, 'Search Breeds', 'GET', '/breeds/search', [
            'description' => 'Search breeds by name.',
            'query_params' => [
                ['name' => 'q', 'type' => 'string', 'required' => true, 'description' => 'Search term, e.g. beng'],
            ],
            'responses' => [
                ['status' => '200', 'description' => 'OK', 'example' => "[\n  {\n    \"id\": \"beng\",\n    \"name\": \"Bengal\",\n    \"origin\": \"United States\"\n  }\n]"],
            ],
        ]);

        /* ---- Categories (no key required) ---- */
        $this->makeEndpoint($project->id, $categories->id, 'List Categories', 'GET', '/categories', [
            'description' => 'Returns the image categories (hats, boxes, etc.) you can filter searches by.',
            'responses' => [
                ['status' => '200', 'description' => 'OK', 'example' => "[\n  { \"id\": 1, \"name\": \"hats\" },\n  { \"id\": 5, \"name\": \"boxes\" }\n]"],
            ],
        ]);

        /* ---- Favourites (API key required) ---- */
        $this->makeEndpoint($project->id, $favourites->id, 'List Favourites', 'GET', '/favourites', array_merge($apiKeyAuth, [
            'description' => 'List the images you have favourited. **Requires an API key.**',
            'responses' => [
                ['status' => '200', 'description' => 'OK', 'example' => "[\n  {\n    \"id\": 123,\n    \"image_id\": \"0XYvRd7oD\",\n    \"sub_id\": \"demo-user\"\n  }\n]"],
                ['status' => '401', 'description' => 'Missing/invalid key', 'example' => "{\n  \"message\": \"AUTHENTICATION_ERROR\"\n}"],
            ],
        ]));

        $this->makeEndpoint($project->id, $favourites->id, 'Create Favourite', 'POST', '/favourites', array_merge($apiKeyAuth, [
            'description' => 'Favourite an image. **Requires an API key.**',
            'request_body' => ['type' => 'json', 'content' => "{\n  \"image_id\": \"0XYvRd7oD\",\n  \"sub_id\": \"demo-user\"\n}"],
            'parameters' => [
                ['name' => 'image_id', 'type' => 'string', 'required' => true, 'description' => 'Image to favourite'],
                ['name' => 'sub_id', 'type' => 'string', 'required' => false, 'description' => 'Your own user identifier'],
            ],
            'responses' => [
                ['status' => '200', 'description' => 'Created', 'example' => "{\n  \"message\": \"SUCCESS\",\n  \"id\": 123\n}"],
            ],
        ]));

        $this->makeEndpoint($project->id, $favourites->id, 'Delete Favourite', 'DELETE', '/favourites/{favourite_id}', array_merge($apiKeyAuth, [
            'description' => 'Remove a favourite. **Requires an API key.**',
            'path_params' => [
                ['name' => 'favourite_id', 'type' => 'integer', 'required' => true, 'description' => 'The favourite id returned by Create Favourite'],
            ],
            'responses' => [
                ['status' => '200', 'description' => 'Deleted', 'example' => "{\n  \"message\": \"SUCCESS\"\n}"],
            ],
        ]));

        /* ---- Votes (API key required) ---- */
        $this->makeEndpoint($project->id, $votes->id, 'Create Vote', 'POST', '/votes', array_merge($apiKeyAuth, [
            'description' => 'Vote an image up (1) or down (0). **Requires an API key.**',
            'request_body' => ['type' => 'json', 'content' => "{\n  \"image_id\": \"0XYvRd7oD\",\n  \"sub_id\": \"demo-user\",\n  \"value\": 1\n}"],
            'parameters' => [
                ['name' => 'image_id', 'type' => 'string', 'required' => true, 'description' => 'Image to vote on'],
                ['name' => 'value', 'type' => 'integer', 'required' => true, 'description' => '1 (up) or 0 (down)'],
            ],
            'responses' => [
                ['status' => '200', 'description' => 'Created', 'example' => "{\n  \"message\": \"SUCCESS\",\n  \"id\": 456\n}"],
            ],
        ]));

        $this->makeEndpoint($project->id, $votes->id, 'List Votes', 'GET', '/votes', array_merge($apiKeyAuth, [
            'description' => 'List your votes. **Requires an API key.**',
            'responses' => [
                ['status' => '200', 'description' => 'OK', 'example' => "[\n  {\n    \"id\": 456,\n    \"image_id\": \"0XYvRd7oD\",\n    \"value\": 1\n  }\n]"],
            ],
        ]));
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function makeEndpoint(int $projectId, int $categoryId, string $name, string $method, string $url, array $attributes): void
    {
        Endpoint::create(array_merge([
            'project_id' => $projectId,
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => Str::slug($name),
            'method' => $method,
            'url' => $url,
            'auth_type' => 'none',
            'auth_config' => [],
            'parameters' => [],
            'headers' => [],
            'query_params' => [],
            'path_params' => [],
            'request_body' => ['type' => 'none', 'content' => ''],
            'responses' => [],
        ], $attributes));
    }
}
