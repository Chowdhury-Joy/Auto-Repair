<?php

namespace App\Services;

use App\Enums\AlertType;
use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\OperationsAlert;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OperationsAlertService
{
    public function checkAndCreateAlerts(): void
    {
        $this->checkUnconfirmedAppointments();
        $this->checkStuckWorkOrders();
        $this->dispatchPendingAlerts();
    }

    private function checkUnconfirmedAppointments(): void
    {
        $threshold = now()->addHours(24);

        Appointment::query()
            ->unconfirmed()
            ->upcoming()
            ->where('starts_at', '<=', $threshold)
            ->where('status', 'scheduled')
            ->each(function (Appointment $appt) {
                // Don't create duplicate alerts.
                $exists = OperationsAlert::where('type', AlertType::AppointmentUnconfirmed)
                    ->where('reference_type', Appointment::class)
                    ->where('reference_id', $appt->id)
                    ->where('status', 'pending')
                    ->exists();

                if ($exists) {
                    return;
                }

                OperationsAlert::create([
                    'type' => AlertType::AppointmentUnconfirmed,
                    'reference_type' => Appointment::class,
                    'reference_id' => $appt->id,
                    'payload' => [
                        'customer_name' => $appt->customer?->user?->name,
                        'customer_email' => $appt->customer?->user?->email,
                        'starts_at' => $appt->starts_at->toIso8601String(),
                    ],
                    'status' => 'pending',
                ]);
            });
    }

    private function checkStuckWorkOrders(): void
    {
        $threshold = now()->subHours(8);

        WorkOrder::query()
            ->whereIn('status', [WorkOrderStatus::InProgress, WorkOrderStatus::AwaitingParts])
            ->where('opened_at', '<=', $threshold)
            ->each(function (WorkOrder $wo) {
                $exists = OperationsAlert::where('type', AlertType::WorkOrderStuck)
                    ->where('reference_type', WorkOrder::class)
                    ->where('reference_id', $wo->id)
                    ->where('status', 'pending')
                    ->exists();

                if ($exists) {
                    return;
                }

                OperationsAlert::create([
                    'type' => AlertType::WorkOrderStuck,
                    'reference_type' => WorkOrder::class,
                    'reference_id' => $wo->id,
                    'payload' => [
                        'vehicle' => "{$wo->vehicle?->year} {$wo->vehicle?->make} {$wo->vehicle?->model}",
                        'status' => $wo->status->label(),
                        'opened_at' => $wo->opened_at->toIso8601String(),
                        'mechanic' => $wo->mechanic?->name,
                    ],
                    'status' => 'pending',
                ]);
            });
    }

    private function dispatchPendingAlerts(): void
    {
        $webhookUrl = config('services.truewrench.webhook_url');

        OperationsAlert::pending()->each(function (OperationsAlert $alert) use ($webhookUrl) {
            if (! $webhookUrl) {
                // No webhook configured — just mark as delivered (logged only).
                $alert->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]);
                Log::info('Operations alert (logged only)', [
                    'type' => $alert->type->value,
                    'reference' => "{$alert->reference_type}#{$alert->reference_id}",
                    'payload' => $alert->payload,
                ]);

                return;
            }

            try {
                $response = Http::timeout(5)->post($webhookUrl, [
                    'type' => $alert->type->value,
                    'reference' => [
                        'type' => $alert->reference_type,
                        'id' => $alert->reference_id,
                    ],
                    'payload' => $alert->payload,
                    'timestamp' => now()->toIso8601String(),
                ]);

                if ($response->successful()) {
                    $alert->update([
                        'status' => 'delivered',
                        'delivered_at' => now(),
                    ]);
                } else {
                    $alert->update([
                        'status' => 'failed',
                        'error_message' => "HTTP {$response->status()}: {$response->body()}",
                    ]);
                }
            } catch (\Exception $e) {
                $alert->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        });
    }
}
