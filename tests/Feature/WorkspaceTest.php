<?php

namespace Tests\Feature;

use App\Livewire\Workspace\RequestTester;
use App\Livewire\Workspace\Workspace;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_workspace_page_renders_with_its_panels(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Shop API']);
        Endpoint::factory()->create(['project_id' => $project->id, 'name' => 'Ping Endpoint', 'method' => 'GET']);

        $this->actingAs($user)
            ->get(route('projects.workspace', $project))
            ->assertOk()
            ->assertSee('Shop API')
            ->assertSee('Ping Endpoint')
            ->assertSee('Tester');
    }

    public function test_a_user_can_create_an_endpoint_in_the_workspace(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['project' => $project])
            ->call('newEndpoint')
            ->set('form.name', 'List Users')
            ->set('form.method', 'GET')
            ->set('form.url', '{{BASE_URL}}/users')
            ->call('saveEndpoint')
            ->assertHasNoErrors()
            ->assertSet('mode', 'view');

        $this->assertDatabaseHas('endpoints', [
            'name' => 'List Users',
            'project_id' => $project->id,
            'method' => 'GET',
        ]);
    }

    public function test_the_tester_sends_a_request_and_records_history(): void
    {
        Http::fake(['*' => Http::response('{"ok":true}', 200)]);

        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->environments()->create([
            'name' => 'Dev',
            'variables' => ['BASE_URL' => 'https://api.test'],
            'is_active' => true,
        ]);
        $endpoint = Endpoint::factory()->create([
            'project_id' => $project->id,
            'method' => 'GET',
            'url' => '{{BASE_URL}}/ping',
        ]);

        Livewire::actingAs($user)
            ->test(RequestTester::class, ['project' => $project, 'endpointId' => $endpoint->id])
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('request_histories', [
            'url' => 'https://api.test/ping',
            'response_status' => 200,
            'endpoint_id' => $endpoint->id,
        ]);
    }
}
