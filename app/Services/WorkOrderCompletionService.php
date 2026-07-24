<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Invoice;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Cache;

class WorkOrderCompletionService
{
    /**
     * Completes a work order, closes the associated appointment, and optionally generates an invoice.
     * 
     * CONCURRENCY CONTROL:
     * We use a scoped Cache lock keyed by the work order ID (`invoice_generation_{id}`).
     * This prevents two mechanics from double-clicking the "Complete" button simultaneously 
     * on the same work order, which would otherwise result in duplicate invoices being generated.
     * By scoping to the ID, we allow different work orders to be completed concurrently.
     * 
     * @param WorkOrder $workOrder
     * @param bool $generateInvoice
     * @return Invoice|null
     */
    public function complete(WorkOrder $workOrder, bool $generateInvoice = true): ?Invoice
    {
        return Cache::lock('invoice_generation_' . $workOrder->id, 5)->block(5, function () use ($workOrder, $generateInvoice) {
            return DB::transaction(function () use ($workOrder, $generateInvoice) {
                $workOrder->update([
                    'status' => WorkOrderStatus::Completed,
                    'completed_at' => now(),
                ]);

                // Update the appointment status if linked.
                if ($workOrder->appointment) {
                    $workOrder->appointment->update(['status' => AppointmentStatus::Completed]);
                }

                if (! $generateInvoice) {
                    return null;
                }

                $total = $workOrder->computeTotal();

                return Invoice::create([
                    'work_order_id' => $workOrder->id,
                    'customer_id' => $workOrder->customer_id,
                    'number' => Invoice::generateNumber(),
                    'status' => InvoiceStatus::Draft,
                    'total_cents' => $total,
                    'public_token' => Invoice::generateToken(),
                ]);
            });
        });
    }
}
