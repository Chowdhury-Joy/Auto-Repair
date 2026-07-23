<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'customer_id', 'make', 'model', 'year', 'vin',
        'license_plate', 'state_plate', 'current_mileage', 'color',
    ];

    protected $casts = ['current_mileage' => 'integer'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->year} {$this->make} {$this->model}");
    }
}
