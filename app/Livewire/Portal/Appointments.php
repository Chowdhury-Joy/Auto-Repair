<?php

namespace App\Livewire\Portal;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Appointments extends Component
{
    public function cancel(int $id): void
    {
        $customer = Auth::user()->customer;
        if ($customer) {
            $appt = Appointment::where('id', $id)
                ->where('customer_id', $customer->id)
                ->where('status', AppointmentStatus::Scheduled)
                ->first();

            if ($appt) {
                $appt->update(['status' => AppointmentStatus::Cancelled]);
                session()->flash('success', 'Appointment cancelled.');
            }
        }
    }

    public function render()
    {
        $customer = Auth::user()->customer;
        $appointments = Appointment::query()
            ->where('customer_id', $customer?->id)
            ->with(['vehicle', 'serviceTypes', 'serviceBay', 'mechanic'])
            ->orderBy('starts_at', 'desc')
            ->get();

        return view('livewire.portal.appointments', [
            'appointments' => $appointments,
        ])->layout('layouts.portal', ['title' => 'My Appointments · TrueWrench']);
    }
}
