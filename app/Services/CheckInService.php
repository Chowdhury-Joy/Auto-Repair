<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class CheckInService
{
    public function checkIn(Appointment $appointment): WorkOrder
    {
        return DB::transaction(function () use ($appointment) {
            $appointment->update(['status' => AppointmentStatus::CheckedIn]);

            return WorkOrder::create([
                'appointment_id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'vehicle_id' => $appointment->vehicle_id,
                'service_bay_id' => $appointment->service_bay_id,
                'mechanic_id' => $appointment->mechanic_id,
                'status' => WorkOrderStatus::Open,
                'opened_at' => now(),
                'notes' => $appointment->customer_notes,
            ]);
        });
    }
}
