<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderItem extends Model
{
    protected $fillable = [
        'work_order_id', 'description', 'type',
        'quantity', 'rate_cents', 'amount_cents', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate_cents' => 'integer',
        'amount_cents' => 'integer',
        'sort_order' => 'integer',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    protected static function booted(): void
    {
        static::saving(function (WorkOrderItem $item) {
            $item->amount_cents = (int) round($item->quantity * $item->rate_cents);
        });

        static::saved(function (WorkOrderItem $item) {
            $item->workOrder?->computeTotal();
        });

        static::deleted(function (WorkOrderItem $item) {
            $item->workOrder?->computeTotal();
        });
    }
}
