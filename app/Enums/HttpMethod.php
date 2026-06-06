<?php

namespace App\Enums;

enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
    case HEAD = 'HEAD';

    /**
     * Whether this method conventionally carries a request body.
     */
    public function allowsBody(): bool
    {
        return in_array($this, [self::POST, self::PUT, self::PATCH, self::DELETE], true);
    }

    /**
     * Tailwind colour classes for the method badge (light + dark).
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::GET => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            self::POST => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            self::PUT => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            self::PATCH => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
            self::DELETE => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
            self::OPTIONS, self::HEAD => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
    }
}
