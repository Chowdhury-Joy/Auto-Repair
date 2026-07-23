<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Invoice;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class WorkOrderCompletionService
{
    public function complete(WorkOrder $workOrder, bool $generateInvoice = true): ?Invoice
    {
        return DB::transaction(function () use ($workOrder, $generateInvoice) {
            $workOrder->update([
                'status'       => WorkOrderStatus::Completed,
                'completed_at' => now(),
            ]);

            // Update the appointment status if linked.
            if ($workOrder->appointment) {
                $workOrder->appointment->update(['status' => \App\Enums\AppointmentStatus::Completed]);
            }

            if (! $generateInvoice) return null;

            $total = $workOrder->computeTotal();

            return Invoice::create([
                'work_order_id' => $workOrder->id,
                'customer_id'   => $workOrder->customer_id,
                'number'        => Invoice::generateNumber(),
                'status'        => InvoiceStatus::Draft,
                'total_cents'   => $total,
                'public_token'  => Invoice::generateToken(),
            ]);
        });
    }
}
