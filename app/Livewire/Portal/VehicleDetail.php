<?php

namespace App\Livewire\Portal;

use App\Enums\WorkOrderStatus;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VehicleDetail extends Component
{
    public int $vehicleId;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount(int $vehicleId): void
    {
        $this->vehicleId = $vehicleId;
    }

    public function render()
    {
        $customer = Auth::user()->customer;
        $vehicle = Vehicle::where('id', $this->vehicleId)
            ->where('customer_id', $customer?->id)
            ->firstOrFail();

        $workOrders = WorkOrder::query()
            ->where('vehicle_id', $vehicle->id)
            ->with(['items', 'invoice', 'mechanic'])
            ->orderBy('opened_at', 'desc')
            ->get();

        $activeWorkOrder = $workOrders->first(function ($wo) {
            return ! $wo->status->isTerminal();
        });

        return view('livewire.portal.vehicle-detail', [
            'vehicle'         => $vehicle,
            'workOrders'      => $workOrders,
            'activeWorkOrder' => $activeWorkOrder,
        ])->layout('layouts.portal', ['title' => "{$vehicle->display_name} · TrueWrench"]);
    }
}
