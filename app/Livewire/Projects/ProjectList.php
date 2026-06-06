<?php

namespace App\Livewire\Projects;

use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Projects')]
class ProjectList extends Component
{
    public ProjectForm $form;

    public bool $showModal = false;

    public bool $editing = false;

    public function create(): void
    {
        $this->authorize('create', Project::class);
        $this->form->reset();
        $this->editing = false;
        $this->showModal = true;
    }

    public function edit(Project $project): void
    {
        $this->authorize('update', $project);
        $this->form->setProject($project);
        $this->editing = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        if ($this->editing) {
            $this->authorize('update', Project::findOrFail($this->form->projectId));
            $this->form->update();
        } else {
            $this->authorize('create', Project::class);
            $this->form->store();
        }

        $this->showModal = false;
    }

    public function delete(Project $project): void
    {
        $this->authorize('delete', $project);
        $project->delete();
    }

    public function render()
    {
        return view('livewire.projects.project-list', [
            'projects' => Project::withCount(['endpoints', 'categories'])
                ->latest()
                ->get(),
            'canManage' => auth()->user()->isAdmin(),
        ]);
    }
}
