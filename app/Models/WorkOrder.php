<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    protected $fillable = [
        'appointment_id', 'customer_id', 'vehicle_id',
        'service_bay_id', 'mechanic_id', 'status',
        'opened_at', 'completed_at', 'total_cents', 'notes',
    ];

    protected $casts = [
        'opened_at'    => 'datetime',
        'completed_at' => 'datetime',
        'total_cents'  => 'integer',
        'status'       => WorkOrderStatus::class,
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

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

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    public function invoice(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', '!=', WorkOrderStatus::Completed);
    }

    public function scopeInProgress(Builder $q): Builder
    {
        return $q->whereIn('status', [WorkOrderStatus::InProgress, WorkOrderStatus::AwaitingParts]);
    }

    public function computeTotal(): int
    {
        $total = (int) $this->items()->sum('amount_cents');
        $this->update(['total_cents' => $total]);
        return $total;
    }
}
