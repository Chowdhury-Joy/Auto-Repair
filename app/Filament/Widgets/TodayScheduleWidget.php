<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ServiceBay;
use Filament\Widgets\Widget;

class TodayScheduleWidget extends Widget
{
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.today-schedule';

    public function getViewData(): array
    {
        $bays = ServiceBay::active()->ordered()->get();
        $today = today();

        $appts = Appointment::query()
            ->with(['customer.user', 'vehicle', 'mechanic', 'serviceTypes'])
            ->whereDate('starts_at', $today)
            ->orderBy('starts_at')
            ->get()
            ->groupBy('service_bay_id');

        return [
            'bays'  => $bays,
            'appts' => $appts,
            'today' => $today,
        ];
    }
}
