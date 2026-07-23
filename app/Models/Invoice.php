<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'work_order_id', 'customer_id', 'number',
        'status', 'total_cents', 'issued_at', 'paid_at', 'public_token',
    ];

    protected $casts = [
        'total_cents' => 'integer',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'status' => InvoiceStatus::class,
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeDraft(Builder $q): Builder
    {
        return $q->where('status', InvoiceStatus::Draft);
    }

    public function scopeSent(Builder $q): Builder
    {
        return $q->where('status', InvoiceStatus::Sent);
    }

    public function scopePaid(Builder $q): Builder
    {
        return $q->where('status', InvoiceStatus::Paid);
    }

    public function publicUrl(): string
    {
        return url("/invoices/{$this->public_token}");
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return "TW-{$year}-".str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
