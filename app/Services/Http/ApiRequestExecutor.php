<?php

namespace App\Services\Http;

use App\Enums\AuthType;
use App\Models\RequestHistory;
use App\Services\Auth\RequestAuthApplier;
use App\Services\VariableSubstitutor;
use Illuminate\Support\Facades\Http;

class ApiRequestExecutor
{
    public function __construct(
        protected VariableSubstitutor $substitutor,
        protected RequestAuthApplier $authApplier,
    ) {}

    /**
     * Execute a user-built request server-side, persist it to history and
     * return the resulting RequestHistory record.
     *
     * Expected $request shape:
     *   method:      string  (GET, POST, ...)
     *   url:         string  (may contain {{VARS}})
     *   headers:     array<string,string>
     *   query:       array<string,string>
     *   auth_type:   AuthType
     *   auth_config: array<string,mixed>
     *   body_type:   none|json|raw|urlencoded|multipart
     *   body:        string                 (json / raw)
     *   form_fields: array<string,string>   (urlencoded / multipart text fields)
     *   files:       array<int,array{name:string,path:string,filename?:string}>
     *
     * @param  array<string,mixed>  $request
     * @param  array<string,string|int|float|bool|null>  $variables
     */
    public function execute(array $request, array $variables = [], ?int $userId = null, ?int $endpointId = null): RequestHistory
    {
        $method = strtoupper((string) ($request['method'] ?? 'GET'));
        $url = (string) $this->substitutor->substitute($request['url'] ?? '', $variables);
        $url = $this->applyBaseUrl($url, $variables);

        $headers = $this->substitutor->substitute($this->cleanPairs($request['headers'] ?? []), $variables);
        $query = $this->substitutor->substitute($this->cleanPairs($request['query'] ?? []), $variables);
        $formFields = $this->substitutor->substitute($this->cleanPairs($request['form_fields'] ?? []), $variables);

        // Apply authentication on top of any explicit headers/query.
        $authType = $request['auth_type'] ?? AuthType::None;
        if (! $authType instanceof AuthType) {
            $authType = AuthType::tryFrom((string) $authType) ?? AuthType::None;
        }
        $authConfig = $this->substitutor->substitute($request['auth_config'] ?? [], $variables);
        $auth = $this->authApplier->resolve($authType, is_array($authConfig) ? $authConfig : []);
        $headers = array_merge($headers, $auth['headers']);
        $query = array_merge($query, $auth['query']);

        $bodyType = (string) ($request['body_type'] ?? 'none');
        $body = (string) $this->substitutor->substitute($request['body'] ?? '', $variables);

        // SSRF guard for self-hosted multi-tenant safety (default: permissive).
        $host = parse_url($url, PHP_URL_HOST);
        if ($host && in_array($host, config('curl.blocked_hosts', []), true)) {
            return $this->persist($userId, $endpointId, $method, $url, $headers, $body, $formFields, [
                'error' => "Requests to host [{$host}] are blocked by configuration.",
            ]);
        }

        $options = $this->buildOptions($bodyType, $body, $formFields, $query, $request['files'] ?? [], $headers);

        $start = microtime(true);
        $result = ['error' => null, 'status' => null, 'headers' => null, 'body' => null];

        try {
            $response = Http::timeout((int) config('curl.request_timeout', 30))
                ->withHeaders($headers)
                ->withOptions(['http_errors' => false])
                ->send($method, $url, $options);

            $result['status'] = $response->status();
            $result['headers'] = $this->flattenHeaders($response->headers());
            $result['body'] = $this->truncate($response->body());
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        $result['time_ms'] = (int) round((microtime(true) - $start) * 1000);

        return $this->persist($userId, $endpointId, $method, $url, $headers, $body, $formFields, $result);
    }

    /**
     * Build Guzzle-style options for the outgoing request based on body type.
     *
     * @param  array<string,string>  $formFields
     * @param  array<string,string>  $query
     * @param  array<int,array<string,mixed>>  $files
     * @param  array<string,string>  $headers  (passed by reference to set Content-Type)
     * @return array<string,mixed>
     */
    protected function buildOptions(string $bodyType, string $body, array $formFields, array $query, array $files, array &$headers): array
    {
        $options = [];

        if ($query !== []) {
            $options['query'] = $query;
        }

        switch ($bodyType) {
            case 'json':
                $options['body'] = $body;
                if (! $this->hasHeader($headers, 'Content-Type')) {
                    $headers['Content-Type'] = 'application/json';
                }
                break;

            case 'raw':
                $options['body'] = $body;
                break;

            case 'urlencoded':
                $options['form_params'] = $formFields;
                break;

            case 'multipart':
                $multipart = [];
                foreach ($formFields as $name => $value) {
                    $multipart[] = ['name' => $name, 'contents' => $value];
                }
                foreach ($files as $file) {
                    if (empty($file['name']) || empty($file['path']) || ! is_file($file['path'])) {
                        continue;
                    }
                    $multipart[] = [
                        'name' => $file['name'],
                        'contents' => file_get_contents($file['path']),
                        'filename' => $file['filename'] ?? basename($file['path']),
                    ];
                }
                $options['multipart'] = $multipart;
                break;

            case 'none':
            default:
                break;
        }

        return $options;
    }

    /**
     * Prepend the active environment's BASE_URL to a relative path. Absolute URLs
     * (any scheme) — including endpoints that already resolved {{BASE_URL}} — are
     * left untouched, as are paths when no BASE_URL is configured.
     *
     * @param  array<string,mixed>  $variables
     */
    protected function applyBaseUrl(string $url, array $variables): string
    {
        $url = trim($url);

        if ($url === '' || preg_match('#^[a-z][a-z0-9+.\-]*://#i', $url)) {
            return $url;
        }

        $base = trim((string) ($variables['BASE_URL'] ?? ''));
        if ($base === '') {
            return $url;
        }

        return rtrim($base, '/').'/'.ltrim($url, '/');
    }

    /**
     * Normalise key/value pair rows (or an assoc array) into a clean assoc array,
     * dropping rows without a name.
     *
     * @param  mixed  $pairs
     * @return array<string,string>
     */
    protected function cleanPairs(mixed $pairs): array
    {
        if (! is_array($pairs)) {
            return [];
        }

        $result = [];
        foreach ($pairs as $key => $value) {
            // Row form: ['name' => ..., 'value' => ...]
            if (is_array($value) && array_key_exists('name', $value)) {
                $name = trim((string) ($value['name'] ?? ''));
                if ($name !== '' && ($value['enabled'] ?? true)) {
                    $result[$name] = (string) ($value['value'] ?? '');
                }

                continue;
            }

            // Assoc form: ['Header' => 'value']
            if (is_string($key) && trim($key) !== '') {
                $result[$key] = (string) $value;
            }
        }

        return $result;
    }

    /**
     * @param  array<string,mixed>  $result
     */
    protected function persist(?int $userId, ?int $endpointId, string $method, string $url, array $headers, string $body, array $formFields, array $result): RequestHistory
    {
        return RequestHistory::create([
            'user_id' => $userId,
            'endpoint_id' => $endpointId,
            'method' => $method,
            'url' => $url,
            'request_headers' => $headers,
            'request_body' => $body !== '' ? $body : ($formFields !== [] ? http_build_query($formFields) : null),
            'response_status' => $result['status'] ?? null,
            'response_headers' => $result['headers'] ?? null,
            'response_body' => $result['body'] ?? null,
            'response_time_ms' => $result['time_ms'] ?? null,
            'error' => $result['error'] ?? null,
        ]);
    }

    /**
     * @param  array<string,array<int,string>>  $headers
     * @return array<string,string>
     */
    protected function flattenHeaders(array $headers): array
    {
        return array_map(fn ($values) => implode(', ', (array) $values), $headers);
    }

    /**
     * @param  array<string,string>  $headers
     */
    protected function hasHeader(array $headers, string $name): bool
    {
        foreach (array_keys($headers) as $key) {
            if (strcasecmp($key, $name) === 0) {
                return true;
            }
        }

        return false;
    }

    protected function truncate(string $body): string
    {
        $max = (int) config('curl.max_stored_response_bytes', 262144);

        return strlen($body) > $max ? substr($body, 0, $max)."\n… [truncated]" : $body;
    }
}
