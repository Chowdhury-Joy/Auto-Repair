<?php

namespace App\Models;

use App\Enums\AlertType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OperationsAlert extends Model
{
    protected $fillable = [
        'type', 'reference_type', 'reference_id',
        'payload', 'status', 'delivered_at', 'error_message',
    ];

    protected $casts = [
        'payload'      => 'array',
        'delivered_at' => 'datetime',
        'type'         => AlertType::class,
    ];

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function scopeDelivered(Builder $q): Builder
    {
        return $q->where('status', 'delivered');
    }

    public function scopeFailed(Builder $q): Builder
    {
        return $q->where('status', 'failed');
    }
}
