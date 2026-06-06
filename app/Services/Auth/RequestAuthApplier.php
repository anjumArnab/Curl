<?php

namespace App\Services\Auth;

use App\Enums\AuthType;

class RequestAuthApplier
{
    /**
     * Resolve auth configuration into outgoing headers and query parameters.
     *
     * @param  array<string, mixed>  $config
     * @return array{headers: array<string, string>, query: array<string, string>}
     */
    public function resolve(AuthType $type, array $config): array
    {
        $headers = [];
        $query = [];

        switch ($type) {
            case AuthType::Bearer:
                $token = (string) ($config['token'] ?? '');
                if ($token !== '') {
                    $headers['Authorization'] = 'Bearer '.$token;
                }
                break;

            case AuthType::Basic:
                $headers['Authorization'] = 'Basic '.base64_encode(
                    ($config['username'] ?? '').':'.($config['password'] ?? '')
                );
                break;

            case AuthType::ApiKey:
                $key = (string) ($config['key'] ?? 'X-API-Key');
                $value = (string) ($config['value'] ?? '');
                if ($key !== '') {
                    if (($config['in'] ?? 'header') === 'query') {
                        $query[$key] = $value;
                    } else {
                        $headers[$key] = $value;
                    }
                }
                break;

            case AuthType::Custom:
                foreach (($config['headers'] ?? []) as $pair) {
                    $name = trim((string) ($pair['name'] ?? ''));
                    if ($name !== '') {
                        $headers[$name] = (string) ($pair['value'] ?? '');
                    }
                }
                break;

            case AuthType::None:
            default:
                break;
        }

        return ['headers' => $headers, 'query' => $query];
    }
}
