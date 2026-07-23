<?php

use App\Livewire\BookAppointment;
use App\Livewire\Portal\Appointments as PortalAppointments;
use App\Livewire\Portal\Dashboard as PortalDashboard;
use App\Livewire\Portal\Invoices as PortalInvoices;
use App\Livewire\Portal\VehicleDetail as PortalVehicleDetail;
use App\Livewire\Portal\Vehicles as PortalVehicles;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/book', BookAppointment::class)->name('book');

// --- Customer Portal (Auth required) ---
Route::middleware('auth')->prefix('portal')->group(function () {
    Route::get('/', PortalDashboard::class)->name('portal.dashboard');
    Route::get('/vehicles', PortalVehicles::class)->name('portal.vehicles');
    Route::get('/vehicles/{vehicleId}', PortalVehicleDetail::class)->name('portal.vehicle.detail');
    Route::get('/appointments', PortalAppointments::class)->name('portal.appointments');
    Route::get('/invoices', PortalInvoices::class)->name('portal.invoices');
});

// --- Public Invoice View (No auth, token-based) ---
Route::get('/invoices/{token}', function (string $token) {
    $invoice = \App\Models\Invoice::where('public_token', $token)
        ->with(['workOrder.items', 'workOrder.vehicle', 'customer.user'])
        ->firstOrFail();

    return view('public.invoice', compact('invoice'));
})->name('public.invoice');
