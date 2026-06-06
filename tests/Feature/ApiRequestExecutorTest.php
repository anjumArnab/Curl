<?php

namespace Tests\Feature;

use App\Enums\AuthType;
use App\Services\Http\ApiRequestExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiRequestExecutorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_substitutes_variables_applies_auth_and_records_history(): void
    {
        Http::fake(['*' => Http::response('{"ok":true}', 200, ['Content-Type' => 'application/json'])]);

        $history = app(ApiRequestExecutor::class)->execute(
            [
                'method' => 'GET',
                'url' => '{{BASE_URL}}/posts/1',
                'headers' => [['name' => 'X-Test', 'value' => '1']],
                'query' => [],
                'auth_type' => AuthType::Bearer,
                'auth_config' => ['token' => '{{TOKEN}}'],
                'body_type' => 'none',
                'body' => '',
                'form_fields' => [],
            ],
            ['BASE_URL' => 'https://api.test', 'TOKEN' => 'secret'],
        );

        $this->assertSame(200, $history->response_status);
        $this->assertNotNull($history->response_time_ms);
        $this->assertDatabaseHas('request_histories', [
            'url' => 'https://api.test/posts/1',
            'response_status' => 200,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.test/posts/1'
                && $request->hasHeader('Authorization', 'Bearer secret')
                && $request->hasHeader('X-Test', '1');
        });
    }

    public function test_it_prepends_environment_base_url_to_relative_paths(): void
    {
        Http::fake(['*' => Http::response('{}', 200)]);

        app(ApiRequestExecutor::class)->execute(
            ['method' => 'GET', 'url' => '/products/1'],
            ['BASE_URL' => 'https://api.test/v1'],
        );

        Http::assertSent(fn ($request) => $request->url() === 'https://api.test/v1/products/1');
    }

    public function test_it_leaves_absolute_urls_untouched(): void
    {
        Http::fake(['*' => Http::response('{}', 200)]);

        app(ApiRequestExecutor::class)->execute(
            ['method' => 'GET', 'url' => 'https://other.test/ping'],
            ['BASE_URL' => 'https://api.test/v1'],
        );

        Http::assertSent(fn ($request) => $request->url() === 'https://other.test/ping');
    }

    public function test_it_blocks_configured_hosts_without_sending(): void
    {
        config(['curl.blocked_hosts' => ['blocked.test']]);
        Http::fake();

        $history = app(ApiRequestExecutor::class)->execute([
            'method' => 'GET',
            'url' => 'https://blocked.test/secret',
        ]);

        $this->assertNotNull($history->error);
        $this->assertNull($history->response_status);
        Http::assertNothingSent();
    }
}
