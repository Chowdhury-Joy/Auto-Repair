<?php

namespace App\Enums;

enum AlertType: string
{
    case AppointmentUnconfirmed = 'appointment_unconfirmed';
    case WorkOrderStuck         = 'work_order_stuck';

    public function label(): string
    {
        return match ($this) {
            self::AppointmentUnconfirmed => 'Appointment Unconfirmed',
            self::WorkOrderStuck         => 'Work Order Stuck',
        };
    }
}
