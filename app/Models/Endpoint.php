<?php

namespace App\Models;

use App\Enums\AuthType;
use App\Enums\HttpMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Endpoint extends Model
{
    /** @use HasFactory<\Database\Factories\EndpointFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'category_id',
        'name',
        'slug',
        'method',
        'url',
        'description',
        'auth_type',
        'auth_config',
        'parameters',
        'headers',
        'query_params',
        'path_params',
        'request_body',
        'responses',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'method' => HttpMethod::class,
            'auth_type' => AuthType::class,
            'auth_config' => 'array',
            'parameters' => 'array',
            'headers' => 'array',
            'query_params' => 'array',
            'path_params' => 'array',
            'request_body' => 'array',
            'responses' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
