<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceType extends Model
{
    protected $fillable = [
        'name', 'description', 'duration_minutes',
        'price_range_min_cents', 'price_range_max_cents',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'price_range_min_cents' => 'integer',
        'price_range_max_cents' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeMenuOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('name');
    }

    public function formattedPriceRange(): string
    {
        return '$'.number_format($this->price_range_min_cents / 100, 0)
             .' – $'.number_format($this->price_range_max_cents / 100, 0);
    }
}
