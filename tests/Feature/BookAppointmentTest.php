<?php

use App\Enums\AppointmentStatus;
use App\Livewire\BookAppointment;
use App\Models\Appointment;
use App\Models\Mechanic;
use App\Models\ServiceBay;
use App\Models\ServiceType;
use App\Models\ShopHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (range(1, 7) as $dow) {
        ShopHour::create([
            'day_of_week' => $dow,
            'opens_at' => '08:00',
            'closes_at' => '18:00',
            'is_closed' => false,
        ]);
    }

    ServiceBay::create(['name' => 'Bay 1', 'is_active' => true]);
    Mechanic::create(['name' => 'Marcus Reed', 'is_active' => true]);
});

it('can complete multi-step booking flow', function () {
    $svc = ServiceType::create([
        'name' => 'Oil Change',
        'duration_minutes' => 30,
        'price_range_min_cents' => 5000,
        'price_range_max_cents' => 8000,
        'is_active' => true,
    ]);

    $date = CarbonImmutable::now()->addDay()->format('Y-m-d');

    Livewire::test(BookAppointment::class)
        ->set('selectedServiceIds', [$svc->id])
        ->call('nextStep')
        ->assertSet('step', 2)
        ->call('selectDate', $date)
        ->call('selectTime', '09:00')
        ->call('nextStep')
        ->assertSet('step', 3)
        ->set('newVehicleMake', 'Toyota')
        ->set('newVehicleModel', 'Camry')
        ->set('newVehicleYear', '2020')
        ->set('contactName', 'John Doe')
        ->set('contactEmail', 'john@example.com')
        ->set('contactPhone', '555-0199')
        ->call('nextStep')
        ->assertSet('step', 4)
        ->call('confirm')
        ->assertSet('step', 5);

    expect(Appointment::count())->toBe(1);

    $appt = Appointment::first();
    expect($appt->customer->user->email)->toBe('john@example.com')
        ->and($appt->vehicle->make)->toBe('Toyota')
        ->and($appt->status)->toBe(AppointmentStatus::Scheduled);
});
