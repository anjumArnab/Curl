<?php

namespace App\Services;

class VariableSubstitutor
{
    /**
     * Replace {{VAR}} tokens in a string (or recursively in an array) using the
     * supplied environment variables. Unknown tokens are left untouched.
     *
     * @param  array<string, string|int|float|bool|null>  $variables
     */
    public function substitute(mixed $value, array $variables): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->substitute($item, $variables), $value);
        }

        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_replace_callback('/\{\{\s*([A-Za-z0-9_.\-]+)\s*\}\}/', function (array $matches) use ($variables) {
            $key = $matches[1];

            return array_key_exists($key, $variables)
                ? (string) $variables[$key]
                : $matches[0];
        }, $value);
    }
}
