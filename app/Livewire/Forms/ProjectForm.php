<?php

namespace App\Livewire\Forms;

use App\Models\Project;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProjectForm extends Form
{
    public ?int $projectId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:5000')]
    public string $description = '';

    #[Validate('required|in:private,public')]
    public string $visibility = 'private';

    public function setProject(Project $project): void
    {
        $this->projectId = $project->id;
        $this->name = $project->name;
        $this->description = $project->description ?? '';
        $this->visibility = $project->visibility;
    }

    public function store(): Project
    {
        $this->validate();

        return Project::create([
            'name' => $this->name,
            'slug' => $this->uniqueSlug($this->name),
            'description' => $this->description ?: null,
            'visibility' => $this->visibility,
            'created_by' => auth()->id(),
        ]);
    }

    public function update(): Project
    {
        $this->validate();

        $project = Project::findOrFail($this->projectId);
        $project->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'visibility' => $this->visibility,
        ]);

        return $project;
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $suffix = 1;

        while (Project::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
