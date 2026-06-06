<?php

namespace App\Livewire\Workspace;

use App\Enums\AuthType;
use App\Enums\HttpMethod;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\RequestHistory;
use App\Services\Http\ApiRequestExecutor;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class RequestTester extends Component
{
    use WithFileUploads;

    public Project $project;

    public ?int $endpointId = null;

    public string $method = 'GET';

    public string $url = '';

    /** @var array<int,array{name:string,value:string}> */
    public array $headerRows = [];

    /** @var array<int,array{name:string,value:string}> */
    public array $queryRows = [];

    /** @var array<int,array{name:string,value:string}> */
    public array $pathRows = [];

    public string $bodyType = 'none';

    public string $body = '';

    /** @var array<int,array{name:string,value:string}> */
    public array $formRows = [];

    /** @var array<int,array{name:string,upload:mixed}> */
    public array $files = [];

    public string $authType = 'none';

    /** @var array<string,mixed> */
    public array $authConfig = [];

    public ?int $environmentId = null;

    /** @var array<string,mixed>|null */
    public ?array $response = null;

    public bool $sending = false;

    public function mount(Project $project, ?int $endpointId = null): void
    {
        $this->project = $project;
        $this->endpointId = $endpointId;
        $this->authConfig = $this->defaultAuthConfig();

        $active = $project->activeEnvironment();
        $this->environmentId = $active?->id;

        if ($endpointId && ($endpoint = Endpoint::find($endpointId))) {
            $this->loadFromEndpoint($endpoint);
        }
    }

    protected function loadFromEndpoint(Endpoint $endpoint): void
    {
        $this->method = $endpoint->method->value;
        $this->url = $endpoint->url;
        $this->authType = $endpoint->auth_type->value;
        $this->authConfig = array_merge($this->defaultAuthConfig(), $endpoint->auth_config ?? []);

        $this->headerRows = $this->seedRows($endpoint->headers ?? []);
        $this->queryRows = $this->seedRows($endpoint->query_params ?? []);
        $this->pathRows = $this->seedRows($endpoint->path_params ?? []);

        $body = $endpoint->request_body ?? ['type' => 'none', 'content' => ''];
        $this->bodyType = in_array($body['type'] ?? 'none', ['none', 'json', 'raw', 'urlencoded', 'multipart'], true)
            ? $body['type']
            : 'none';
        $this->body = (string) ($body['content'] ?? '');
    }

    /**
     * Seed key/value tester rows from documented parameter definitions.
     *
     * @param  array<int,array<string,mixed>>  $definitions
     * @return array<int,array{name:string,value:string}>
     */
    protected function seedRows(array $definitions): array
    {
        $rows = [];
        foreach ($definitions as $definition) {
            $name = trim((string) ($definition['name'] ?? ''));
            if ($name !== '') {
                $rows[] = ['name' => $name, 'value' => ''];
            }
        }

        return $rows;
    }

    public function addRow(string $field): void
    {
        $this->{$field}[] = $field === 'files'
            ? ['name' => '', 'upload' => null]
            : ['name' => '', 'value' => ''];
    }

    public function removeRow(string $field, int $index): void
    {
        unset($this->{$field}[$index]);
        $this->{$field} = array_values($this->{$field});
    }

    #[On('environments-changed')]
    public function refreshEnvironments(): void
    {
        $this->project->load('environments');

        if ($this->environmentId && ! $this->project->environments->contains('id', $this->environmentId)) {
            $this->environmentId = $this->project->activeEnvironment()?->id;
        }
    }

    #[On('load-request')]
    public function loadRequest(int $historyId): void
    {
        $history = RequestHistory::find($historyId);
        if (! $history) {
            return;
        }

        $this->method = $history->method;
        $this->url = $history->url;
        $this->headerRows = $this->assocToRows($history->request_headers ?? []);
        $this->body = (string) ($history->request_body ?? '');
        $this->bodyType = $this->body !== '' ? 'raw' : 'none';
        $this->response = null;
    }

    /**
     * @param  array<string,mixed>  $assoc
     * @return array<int,array{name:string,value:string}>
     */
    protected function assocToRows(array $assoc): array
    {
        $rows = [];
        foreach ($assoc as $name => $value) {
            $rows[] = ['name' => (string) $name, 'value' => (string) $value];
        }

        return $rows;
    }

    public function send(ApiRequestExecutor $executor): void
    {
        $this->validate([
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD',
            'url' => 'required|string|max:2000',
        ]);

        $this->sending = true;

        // Resolve path parameters ({id}) before environment substitution.
        $url = $this->url;
        foreach ($this->pathRows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                $url = str_replace('{'.$name.'}', (string) ($row['value'] ?? ''), $url);
            }
        }

        $files = [];
        foreach ($this->files as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $upload = $row['upload'] ?? null;
            if ($name !== '' && $upload) {
                $files[] = [
                    'name' => $name,
                    'path' => $upload->getRealPath(),
                    'filename' => $upload->getClientOriginalName(),
                ];
            }
        }

        $history = $executor->execute([
            'method' => $this->method,
            'url' => $url,
            'headers' => $this->headerRows,
            'query' => $this->queryRows,
            'auth_type' => AuthType::tryFrom($this->authType) ?? AuthType::None,
            'auth_config' => $this->authConfig,
            'body_type' => $this->bodyType,
            'body' => $this->body,
            'form_fields' => $this->formRows,
            'files' => $files,
        ], $this->environmentVariables(), auth()->id(), $this->endpointId);

        $this->response = [
            'status' => $history->response_status,
            'time_ms' => $history->response_time_ms,
            'headers' => $history->response_headers ?? [],
            'body' => $history->response_body,
            'error' => $history->error,
            'size' => $history->response_body ? strlen($history->response_body) : 0,
            'pretty' => $this->prettyPrint($history->response_body),
        ];

        $this->sending = false;
        $this->dispatch('request-executed');
    }

    /** @return array<string,mixed> */
    protected function environmentVariables(): array
    {
        if (! $this->environmentId) {
            return [];
        }

        $environment = $this->project->environments->firstWhere('id', $this->environmentId);

        return is_array($environment?->variables) ? $environment->variables : [];
    }

    protected function prettyPrint(?string $body): ?string
    {
        if ($body === null || $body === '') {
            return null;
        }

        $decoded = json_decode($body, true);

        return json_last_error() === JSON_ERROR_NONE
            ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : null;
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
        return view('livewire.workspace.request-tester', [
            'environments' => $this->project->environments,
            'methods' => HttpMethod::cases(),
            'authTypes' => AuthType::cases(),
        ]);
    }
}
