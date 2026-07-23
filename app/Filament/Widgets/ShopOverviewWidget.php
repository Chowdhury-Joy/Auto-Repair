<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = today();

        $todayAppts = Appointment::query()
            ->whereDate('starts_at', $today)
            ->count();

        $todayCheckedIn = Appointment::query()
            ->whereDate('starts_at', $today)
            ->whereIn('status', [AppointmentStatus::CheckedIn, AppointmentStatus::InProgress])
            ->count();

        $weekRevenue = $this->computeWeekRevenue();

        $last30NoShowRate = $this->computeNoShowRate();

        return [
            Stat::make('Appointments today', $todayAppts)
                ->description("{$todayCheckedIn} currently in shop")
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),

            Stat::make('Revenue this week', '$'.number_format($weekRevenue / 100, 0))
                ->description('From completed work orders')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('No-show rate (30d)', number_format($last30NoShowRate, 1).'%')
                ->description('Lower is better')
                ->icon('heroicon-o-x-mark')
                ->color($last30NoShowRate > 10 ? 'danger' : 'gray'),
        ];
    }

    private function computeWeekRevenue(): int
    {
        // WorkOrder model lands Day 3; degrade gracefully until then.
        if (! class_exists(WorkOrder::class)) {
            return 0;
        }

        return WorkOrder::query()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total_cents');
    }

    private function computeNoShowRate(): float
    {
        $window = Appointment::query()
            ->where('starts_at', '>=', now()->subDays(30))
            ->where('starts_at', '<=', now());

        $total = (clone $window)->count();
        if ($total === 0) {
            return 0.0;
        }

        $noShows = (clone $window)->where('status', AppointmentStatus::NoShow)->count();

        return ($noShows / $total) * 100;
    }
}
