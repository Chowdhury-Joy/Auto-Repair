<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $fillable = [
        'customer_id', 'vehicle_id', 'service_bay_id', 'mechanic_id',
        'starts_at', 'ends_at', 'status', 'confirmed_at', 'customer_notes', 'staff_notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'status' => AppointmentStatus::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function serviceBay(): BelongsTo
    {
        return $this->belongsTo(ServiceBay::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function serviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(ServiceType::class);
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    /** Appointments that still occupy their bay + mechanic. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNotIn('status', [
            AppointmentStatus::Completed,
            AppointmentStatus::NoShow,
            AppointmentStatus::Cancelled,
        ]);
    }

    /** Appointments overlapping the interval [$start, $end] on a given resource. */
    public function scopeOverlapping(
        Builder $q,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
    ): Builder {
        return $q->active()
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start);
    }

    public function scopeForBay(Builder $q, int $bayId): Builder
    {
        return $q->where('service_bay_id', $bayId);
    }

    public function scopeForMechanic(Builder $q, int $mechanicId): Builder
    {
        return $q->where('mechanic_id', $mechanicId);
    }

    public function scopeUnconfirmed(Builder $q): Builder
    {
        return $q->whereNull('confirmed_at');
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('starts_at', '>=', now());
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}
