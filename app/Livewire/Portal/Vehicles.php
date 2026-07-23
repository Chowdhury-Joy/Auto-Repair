<?php

namespace App\Livewire\Portal;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Vehicles extends Component
{
    public bool $showAddForm = false;

    public string $make = '';

    public string $model = '';

    public string $year = '';

    public string $plate = '';

    public string $mileage = '';

    protected $rules = [
        'make' => 'required|string|max:50',
        'model' => 'required|string|max:50',
        'year' => 'required|string|size:4',
        'plate' => 'nullable|string|max:20',
        'mileage' => 'nullable|integer|min:0',
    ];

    public function addVehicle(): void
    {
        $this->validate();

        $customer = Auth::user()->customer ?: Auth::user()->customer()->create([]);
        Vehicle::create([
            'customer_id' => $customer->id,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'license_plate' => $this->plate ?: null,
            'current_mileage' => $this->mileage ? (int) $this->mileage : null,
        ]);

        $this->reset(['make', 'model', 'year', 'plate', 'mileage', 'showAddForm']);
        session()->flash('success', 'Vehicle added.');
    }

    public function deleteVehicle(int $id): void
    {
        $customer = Auth::user()->customer;
        if ($customer) {
            Vehicle::where('id', $id)->where('customer_id', $customer->id)->delete();
            session()->flash('success', 'Vehicle removed.');
        }
    }

    public function render()
    {
        $customer = Auth::user()->customer;
        $vehicles = $customer?->vehicles ?? collect();

        return view('livewire.portal.vehicles', [
            'vehicles' => $vehicles,
        ])->layout('components.layouts.portal', ['title' => 'My Vehicles · TrueWrench']);
    }
}
