<?php

namespace App\Livewire\Workspace;

use App\Enums\AuthType;
use App\Enums\HttpMethod;
use App\Models\Category;
use App\Models\Endpoint;
use App\Models\Project;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Workspace extends Component
{
    public Project $project;

    public ?int $selectedEndpointId = null;

    /** empty | view | edit | create */
    public string $mode = 'empty';

    public string $search = '';

    public string $newCategoryName = '';

    /** @var array<string,mixed> */
    public array $form = [];

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->resetForm();

        if ($first = $project->endpoints()->first()) {
            $this->selectedEndpointId = $first->id;
            $this->mode = 'view';
        }
    }

    /* ------------------------------------------------------------------ */
    /* Computed data                                                       */
    /* ------------------------------------------------------------------ */

    #[Computed]
    public function categories()
    {
        return $this->project->categories()->get();
    }

    #[Computed]
    public function endpoints()
    {
        $query = $this->project->endpoints();

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('url', 'like', $term)
                    ->orWhere('method', 'like', $term);
            });
        }

        return $query->get();
    }

    #[Computed]
    public function selectedEndpoint(): ?Endpoint
    {
        return $this->selectedEndpointId ? Endpoint::find($this->selectedEndpointId) : null;
    }

    /* ------------------------------------------------------------------ */
    /* Tree / selection                                                    */
    /* ------------------------------------------------------------------ */

    public function selectEndpoint(int $id): void
    {
        $this->selectedEndpointId = $id;
        $this->mode = 'view';
    }

    public function addCategory(): void
    {
        $this->authorize('create', Category::class);

        $name = trim($this->newCategoryName);
        if ($name === '') {
            return;
        }

        Category::create([
            'project_id' => $this->project->id,
            'name' => $name,
            'sort_order' => (int) $this->project->categories()->max('sort_order') + 1,
        ]);

        $this->newCategoryName = '';
        unset($this->categories, $this->endpoints);
    }

    public function deleteCategory(int $id): void
    {
        $category = Category::where('project_id', $this->project->id)->findOrFail($id);
        $this->authorize('delete', $category);
        $category->delete();
        unset($this->categories, $this->endpoints);
    }

    /* ------------------------------------------------------------------ */
    /* Endpoint form-builder                                               */
    /* ------------------------------------------------------------------ */

    public function newEndpoint(?int $categoryId = null): void
    {
        $this->authorize('create', Endpoint::class);
        $this->resetForm();
        $this->form['category_id'] = $categoryId;
        $this->mode = 'create';
    }

    public function editEndpoint(int $id): void
    {
        $endpoint = Endpoint::findOrFail($id);
        $this->authorize('update', $endpoint);

        $this->form = [
            'id' => $endpoint->id,
            'name' => $endpoint->name,
            'category_id' => $endpoint->category_id,
            'method' => $endpoint->method->value,
            'url' => $endpoint->url,
            'description' => $endpoint->description ?? '',
            'auth_type' => $endpoint->auth_type->value,
            'auth_config' => array_merge($this->defaultAuthConfig(), $endpoint->auth_config ?? []),
            'parameters' => $endpoint->parameters ?? [],
            'headers' => $endpoint->headers ?? [],
            'query_params' => $endpoint->query_params ?? [],
            'path_params' => $endpoint->path_params ?? [],
            'request_body' => $endpoint->request_body ?? ['type' => 'none', 'content' => ''],
            'responses' => $endpoint->responses ?? [],
        ];

        $this->selectedEndpointId = $endpoint->id;
        $this->mode = 'edit';
    }

    public function saveEndpoint(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.method' => 'required|in:GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD',
            'form.url' => 'required|string|max:2000',
            'form.category_id' => 'nullable|exists:categories,id',
            'form.auth_type' => 'required|in:none,bearer,basic,api_key,custom',
            'form.description' => 'nullable|string',
        ], attributes: [
            'form.name' => 'name',
            'form.url' => 'URL',
        ]);

        $data = [
            'project_id' => $this->project->id,
            'category_id' => $this->form['category_id'] ?: null,
            'name' => $this->form['name'],
            'method' => $this->form['method'],
            'url' => $this->form['url'],
            'description' => $this->form['description'] ?: null,
            'auth_type' => $this->form['auth_type'],
            'auth_config' => $this->form['auth_config'],
            'parameters' => array_values($this->form['parameters']),
            'headers' => array_values($this->form['headers']),
            'query_params' => array_values($this->form['query_params']),
            'path_params' => array_values($this->form['path_params']),
            'request_body' => $this->form['request_body'],
            'responses' => array_values($this->form['responses']),
        ];

        if ($this->form['id']) {
            $endpoint = Endpoint::findOrFail($this->form['id']);
            $this->authorize('update', $endpoint);
            $endpoint->update($data);
        } else {
            $this->authorize('create', Endpoint::class);
            $data['slug'] = Str::slug($this->form['name']) ?: 'endpoint';
            $data['sort_order'] = (int) $this->project->endpoints()->max('sort_order') + 1;
            $endpoint = Endpoint::create($data);
        }

        $this->selectedEndpointId = $endpoint->id;
        $this->mode = 'view';
        unset($this->categories, $this->endpoints, $this->selectedEndpoint);
        $this->dispatch('endpoint-saved', id: $endpoint->id);
    }

    public function deleteEndpoint(int $id): void
    {
        $endpoint = Endpoint::findOrFail($id);
        $this->authorize('delete', $endpoint);
        $endpoint->delete();

        if ($this->selectedEndpointId === $id) {
            $this->selectedEndpointId = null;
            $this->mode = 'empty';
        }
        unset($this->endpoints);
    }

    public function cancelEdit(): void
    {
        $this->mode = $this->selectedEndpointId ? 'view' : 'empty';
    }

    /* ------------------------------------------------------------------ */
    /* Repeatable rows                                                     */
    /* ------------------------------------------------------------------ */

    public function addRow(string $field): void
    {
        $row = match ($field) {
            'parameters', 'query_params', 'path_params' => ['name' => '', 'type' => 'string', 'required' => false, 'description' => ''],
            'headers' => ['name' => '', 'required' => false, 'description' => ''],
            'responses' => ['status' => '200', 'description' => '', 'example' => ''],
            'authHeaders' => ['name' => '', 'value' => ''],
            default => ['name' => '', 'value' => ''],
        };

        if ($field === 'authHeaders') {
            $this->form['auth_config']['headers'][] = $row;

            return;
        }

        $this->form[$field][] = $row;
    }

    public function removeRow(string $field, int $index): void
    {
        if ($field === 'authHeaders') {
            unset($this->form['auth_config']['headers'][$index]);
            $this->form['auth_config']['headers'] = array_values($this->form['auth_config']['headers']);

            return;
        }

        unset($this->form[$field][$index]);
        $this->form[$field] = array_values($this->form[$field]);
    }

    /* ------------------------------------------------------------------ */

    protected function resetForm(): void
    {
        $this->form = [
            'id' => null,
            'name' => '',
            'category_id' => null,
            'method' => 'GET',
            'url' => '',
            'description' => '',
            'auth_type' => 'none',
            'auth_config' => $this->defaultAuthConfig(),
            'parameters' => [],
            'headers' => [],
            'query_params' => [],
            'path_params' => [],
            'request_body' => ['type' => 'none', 'content' => ''],
            'responses' => [],
        ];
    }

    /** @return array<string,mixed> */
    protected function defaultAuthConfig(): array
    {
        return [
            'token' => '',
            'username' => '',
            'password' => '',
            'key' => 'X-API-Key',
            'value' => '',
            'in' => 'header',
            'headers' => [],
        ];
    }

    public function render()
    {
        return view('livewire.workspace.workspace', [
            'methods' => HttpMethod::cases(),
            'authTypes' => AuthType::cases(),
        ]);
    }
}
