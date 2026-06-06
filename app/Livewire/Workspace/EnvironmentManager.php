<?php

namespace App\Livewire\Workspace;

use App\Models\Environment;
use App\Models\Project;
use Livewire\Component;

class EnvironmentManager extends Component
{
    public Project $project;

    public bool $show = false;

    public ?int $editingId = null;

    public string $name = '';

    /** @var array<int,array{key:string,value:string}> */
    public array $variableRows = [];

    public bool $isActive = false;

    public function open(): void
    {
        $this->show = true;
        $this->resetForm();
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function newEnvironment(): void
    {
        $this->resetForm();
    }

    public function addVariable(): void
    {
        $this->variableRows[] = ['key' => '', 'value' => ''];
    }

    public function removeVariable(int $index): void
    {
        unset($this->variableRows[$index]);
        $this->variableRows = array_values($this->variableRows);
    }

    public function edit(int $id): void
    {
        $environment = Environment::where('project_id', $this->project->id)->findOrFail($id);
        $this->authorize('update', $environment);

        $this->editingId = $environment->id;
        $this->name = $environment->name;
        $this->isActive = $environment->is_active;
        $this->variableRows = [];
        foreach (($environment->variables ?? []) as $key => $value) {
            $this->variableRows[] = ['key' => (string) $key, 'value' => (string) $value];
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'variableRows.*.key' => 'nullable|string|max:255',
        ]);

        $variables = [];
        foreach ($this->variableRows as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            if ($key !== '') {
                $variables[$key] = (string) ($row['value'] ?? '');
            }
        }

        $payload = [
            'name' => $this->name,
            'variables' => $variables,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            $environment = Environment::where('project_id', $this->project->id)->findOrFail($this->editingId);
            $this->authorize('update', $environment);
            $environment->update($payload);
        } else {
            $this->authorize('create', Environment::class);
            $environment = $this->project->environments()->create($payload);
        }

        // Only one active environment per project.
        if ($environment->is_active) {
            Environment::where('project_id', $this->project->id)
                ->where('id', '!=', $environment->id)
                ->update(['is_active' => false]);
        }

        $this->resetForm();
        $this->dispatch('environments-changed');
    }

    public function makeActive(int $id): void
    {
        $environment = Environment::where('project_id', $this->project->id)->findOrFail($id);
        $this->authorize('update', $environment);

        Environment::where('project_id', $this->project->id)->update(['is_active' => false]);
        $environment->update(['is_active' => true]);

        $this->dispatch('environments-changed');
    }

    public function delete(int $id): void
    {
        $environment = Environment::where('project_id', $this->project->id)->findOrFail($id);
        $this->authorize('delete', $environment);
        $environment->delete();

        $this->resetForm();
        $this->dispatch('environments-changed');
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->isActive = false;
        $this->variableRows = [['key' => 'BASE_URL', 'value' => '']];
    }

    public function render()
    {
        return view('livewire.workspace.environment-manager', [
            'environments' => $this->project->environments()->get(),
        ]);
    }
}
