<?php

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Livewire\BookAppointment;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\ServiceBay;
use App\Models\ServiceType;
use App\Models\ShopHour;
use App\Models\User;
use App\Models\Vehicle;
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

it('refuses to attach a guest booking to an existing customer\'s account', function () {
    // Regression test for a real account-takeover bug: a guest could previously
    // "book" using any existing customer's email address and the appointment
    // (plus a brand-new vehicle) would silently attach to that stranger's real
    // account — no password or verification required. See
    // App\Actions\BookAppointmentAction::resolveCustomer().
    $svc = ServiceType::create([
        'name' => 'Oil Change',
        'duration_minutes' => 30,
        'price_range_min_cents' => 5000,
        'price_range_max_cents' => 8000,
        'is_active' => true,
    ]);

    $victim = User::factory()->create(['email' => 'jane@example.com', 'role' => UserRole::Customer]);
    $victimCustomer = Customer::create(['user_id' => $victim->id]);
    Vehicle::create(['customer_id' => $victimCustomer->id, 'make' => 'Toyota', 'model' => 'Camry', 'year' => '2019']);

    $date = CarbonImmutable::now()->addDay()->format('Y-m-d');

    Livewire::test(BookAppointment::class)
        ->set('selectedServiceIds', [$svc->id])
        ->call('nextStep')
        ->assertSet('step', 2)
        ->call('selectDate', $date)
        ->call('selectTime', '09:00')
        ->call('nextStep')
        ->assertSet('step', 3)
        ->set('newVehicleMake', 'Sneaky')
        ->set('newVehicleModel', 'Intruder')
        ->set('newVehicleYear', '2024')
        ->set('contactName', 'Not Jane')
        ->set('contactEmail', 'jane@example.com')
        ->set('contactPhone', '555-9999')
        ->call('nextStep')
        // Blocked at step 3 — never reaches the review screen.
        ->assertSet('step', 3)
        ->assertSet('emailConflict', true);

    expect($victimCustomer->fresh()->vehicles)->toHaveCount(1)
        ->and($victimCustomer->fresh()->appointments)->toHaveCount(0)
        ->and(Appointment::count())->toBe(0);
});

it('blocks the same account-conflict case even if confirm() is called directly', function () {
    // The nextStep() check above is only a UX nicety — this test proves the real
    // enforcement lives in BookAppointmentAction itself, by skipping straight to
    // confirm() the way a scripted/adversarial client could.
    $svc = ServiceType::create([
        'name' => 'Oil Change',
        'duration_minutes' => 30,
        'price_range_min_cents' => 5000,
        'price_range_max_cents' => 8000,
        'is_active' => true,
    ]);

    $victim = User::factory()->create(['email' => 'jane@example.com', 'role' => UserRole::Customer]);
    $victimCustomer = Customer::create(['user_id' => $victim->id]);

    $date = CarbonImmutable::now()->addDay()->format('Y-m-d');

    Livewire::test(BookAppointment::class)
        ->set('selectedServiceIds', [$svc->id])
        ->set('selectedDate', $date)
        ->set('selectedTime', '09:00')
        ->set('newVehicleMake', 'Sneaky')
        ->set('newVehicleModel', 'Intruder')
        ->set('newVehicleYear', '2024')
        ->set('contactName', 'Not Jane')
        ->set('contactEmail', 'jane@example.com')
        ->set('contactPhone', '555-9999')
        ->call('confirm')
        ->assertSet('step', 3)
        ->assertSet('emailConflict', true);

    expect($victimCustomer->fresh()->vehicles)->toHaveCount(0)
        ->and(Appointment::count())->toBe(0);
});
