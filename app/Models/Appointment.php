<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Appointment extends Model
{
    protected $fillable = [
        'customer_id', 'vehicle_id', 'service_bay_id', 'mechanic_id',
        'starts_at', 'ends_at', 'status', 'customer_notes', 'staff_notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'status'    => AppointmentStatus::class,
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
            ->where('ends_at',   '>', $start);
    }

    public function scopeForBay(Builder $q, int $bayId): Builder
    {
        return $q->where('service_bay_id', $bayId);
    }

    public function scopeForMechanic(Builder $q, int $mechanicId): Builder
    {
        return $q->where('mechanic_id', $mechanicId);
    }
}
