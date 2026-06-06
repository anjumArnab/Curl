<?php

namespace Tests\Feature;

use App\Livewire\Projects\ProjectList;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_projects_index_renders(): void
    {
        $user = User::factory()->admin()->create();
        Project::factory()->create(['name' => 'Inventory API']);

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Inventory API');
    }

    public function test_admin_can_create_a_project(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ProjectList::class)
            ->call('create')
            ->set('form.name', 'My API')
            ->set('form.visibility', 'private')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', ['name' => 'My API', 'created_by' => $admin->id]);
    }

    public function test_team_member_cannot_create_a_project(): void
    {
        $member = User::factory()->create();

        Livewire::actingAs($member)
            ->test(ProjectList::class)
            ->call('create')
            ->assertForbidden();
    }
}
