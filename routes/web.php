<?php

use App\Livewire\BookAppointment;
use App\Livewire\Portal\Appointments as PortalAppointments;
use App\Livewire\Portal\Dashboard as PortalDashboard;
use App\Livewire\Portal\Invoices as PortalInvoices;
use App\Livewire\Portal\VehicleDetail as PortalVehicleDetail;
use App\Livewire\Portal\Vehicles as PortalVehicles;
use App\Livewire\Auth\CustomerLogin;
use App\Models\Invoice;
use App\Models\ServiceType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// --- Public Marketing Pages ---
Route::get('/', function () {
    return view('marketing.home');
})->name('home');

Route::get('/about', function () {
    return view('marketing.about');
})->name('about');

Route::get('/services', function () {
    $services = ServiceType::active()->menuOrdered()->get();

    return view('marketing.services', compact('services'));
})->name('services');

Route::get('/contact', function () {
    return view('marketing.contact');
})->name('contact');

Route::post('/contact', function (Illuminate\Http\Request $request) {
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'message' => 'required|string|max:5000',
    ]);

    // Persisted so staff can actually see/action it in /admin (App\Filament\Resources\ContactMessageResource) —
    // logging alone meant every submission was effectively write-only.
    \App\Models\ContactMessage::create($data);

    return back()->with('success', 'Thanks for your message! We will get back to you soon.');
})->name('contact.submit');

Route::get('/book', BookAppointment::class)->name('book');

// --- Customer Auth ---
Route::get('/login', CustomerLogin::class)->name('login');
Route::get('/forgot-password', \App\Livewire\Auth\ForgotPassword::class)->name('password.request');
Route::get('/reset-password/{token}', \App\Livewire\Auth\ResetPassword::class)->name('password.reset');

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/');
})->name('logout');

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
    $invoice = Invoice::where('public_token', $token)
        ->with(['workOrder.items', 'workOrder.vehicle', 'customer.user'])
        ->firstOrFail();

    return view('public.invoice', compact('invoice'));
})->name('public.invoice');
