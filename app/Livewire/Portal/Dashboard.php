<?php

namespace App\Livewire\Portal;

use App\Enums\AppointmentStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $customer = Auth::user()->customer;

        $vehicles = $customer?->vehicles ?? collect();
        $upcomingAppointments = Appointment::query()
            ->with(['vehicle', 'serviceTypes'])
            ->where('customer_id', $customer?->id)
            ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::CheckedIn])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        $inProgressWorkOrders = WorkOrder::query()
            ->where('customer_id', $customer?->id)
            ->whereIn('status', [
                WorkOrderStatus::Open,
                WorkOrderStatus::AwaitingParts,
                WorkOrderStatus::InProgress,
                WorkOrderStatus::ReadyForPickup,
            ])
            ->with(['vehicle', 'mechanic'])
            ->get();

        return view('livewire.portal.dashboard', [
            'vehicles' => $vehicles,
            'upcomingAppointments' => $upcomingAppointments,
            'inProgressWorkOrders' => $inProgressWorkOrders,
        ])->layout('layouts.portal', ['title' => 'My Garage · TrueWrench']);
    }
}
