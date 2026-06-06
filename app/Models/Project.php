<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'visibility',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order')->orderBy('name');
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class)->orderBy('sort_order')->orderBy('name');
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class)->orderBy('name');
    }

    public function activeEnvironment(): ?Environment
    {
        return $this->environments->firstWhere('is_active', true)
            ?? $this->environments->first();
    }
}
