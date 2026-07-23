<?php

use App\Enums\AppointmentStatus;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\ServiceBay;
use App\Models\ServiceType;
use App\Models\ShopHour;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AppointmentAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Shop open 8am-6pm every day for the test window.
    foreach (range(1, 7) as $dow) {
        ShopHour::create([
            'day_of_week' => $dow,
            'opens_at'    => '08:00',
            'closes_at'   => '18:00',
            'is_closed'   => false,
        ]);
    }
});

it('lists only slots where both a bay and a mechanic are free', function () {
    $bay1 = ServiceBay::create(['name' => 'Bay 1']);
    $bay2 = ServiceBay::create(['name' => 'Bay 2']);
    $mech = Mechanic::create(['name' => 'Alex']);
    $svc  = ServiceType::create([
        'name' => 'Oil Change', 'duration_minutes' => 60,
        'price_range_min_cents' => 5000, 'price_range_max_cents' => 8000,
    ]);

    // Block Bay 1 from 10:00-11:00 (some other appointment)
    $customer = Customer::create(['user_id' => User::factory()->create()->id]);
    $vehicle  = Vehicle::create([
        'customer_id' => $customer->id, 'make' => 'Honda', 'model' => 'Civic', 'year' => '2020',
    ]);
    \App\Models\Appointment::create([
        'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
        'service_bay_id' => $bay1->id, 'mechanic_id' => $mech->id,
        'starts_at' => '2026-07-27 10:00', 'ends_at' => '2026-07-27 11:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $service = new AppointmentAvailabilityService();
    $slots   = $service->getAvailableSlots(
        CarbonImmutable::parse('2026-07-27'),
        CarbonImmutable::parse('2026-07-27'),
        [$svc->id],
    );

    // 10:00 should NOT appear (Bay 1 busy AND Alex busy — no free pair exists).
    // 09:00 should appear (everything free).
    // 11:00 should appear (everything free again).
    expect($slots['2026-07-27'])->toContain('09:00')
        ->and($slots['2026-07-27'])->toContain('11:00')
        ->and($slots['2026-07-27'])->not->toContain('10:00');
});

it('prevents double-booking the same bay at overlapping times', function () {
    $bay  = ServiceBay::create(['name' => 'Bay 1']);
    $mech = Mechanic::create(['name' => 'Alex']);
    $svc  = ServiceType::create([
        'name' => 'Oil Change', 'duration_minutes' => 60,
        'price_range_min_cents' => 5000, 'price_range_max_cents' => 8000,
    ]);

    $user1 = User::factory()->create();
    $c1    = Customer::create(['user_id' => $user1->id]);
    $v1    = Vehicle::create(['customer_id' => $c1->id, 'make' => 'A', 'model' => 'B', 'year' => '2020']);

    $user2 = User::factory()->create();
    $c2    = Customer::create(['user_id' => $user2->id]);
    $v2    = Vehicle::create(['customer_id' => $c2->id, 'make' => 'C', 'model' => 'D', 'year' => '2021']);

    $service = new AppointmentAvailabilityService();

    // First booking at 10:00 succeeds.
    $service->book(CarbonImmutable::parse('2026-07-27 10:00'), [$svc->id], $v1, $c1);

    // Second booking at 10:30 (overlapping) MUST throw.
    expect(fn () => $service->book(
        CarbonImmutable::parse('2026-07-27 10:30'), [$svc->id], $v2, $c2
    ))->toThrow(RuntimeException::class, 'no longer available');
});

it('respects both bay AND mechanic constraints independently', function () {
    $bay1  = ServiceBay::create(['name' => 'Bay 1']);
    $bay2  = ServiceBay::create(['name' => 'Bay 2']);
    $mechA = Mechanic::create(['name' => 'Alex']);
    $mechB = Mechanic::create(['name' => 'Blake']);
    $svc   = ServiceType::create([
        'name' => 'Oil Change', 'duration_minutes' => 60,
        'price_range_min_cents' => 5000, 'price_range_max_cents' => 8000,
    ]);

    $user = User::factory()->create();
    $c    = Customer::create(['user_id' => $user->id]);
    $v    = Vehicle::create(['customer_id' => $c->id, 'make' => 'A', 'model' => 'B', 'year' => '2020']);

    $service = new AppointmentAvailabilityService();

    // Book Bay 1 + Alex at 10:00.
    $service->book(CarbonImmutable::parse('2026-07-27 10:00'), [$svc->id], $v, $c);

    // Now try to book 10:00 again. Bay 2 is free AND Blake is free,
    // so this SHOULD succeed (different pair).
    $user2 = User::factory()->create();
    $c2    = Customer::create(['user_id' => $user2->id]);
    $v2    = Vehicle::create(['customer_id' => $c2->id, 'make' => 'X', 'model' => 'Y', 'year' => '2022']);

    $appt = $service->book(CarbonImmutable::parse('2026-07-27 10:00'), [$svc->id], $v2, $c2);
    expect($appt->service_bay_id)->toBe($bay2->id)
        ->and($appt->mechanic_id)->toBe($mechB->id);

    // Now try a THIRD booking at 10:00. Both bays are taken, so it MUST fail.
    $user3 = User::factory()->create();
    $c3    = Customer::create(['user_id' => $user3->id]);
    $v3    = Vehicle::create(['customer_id' => $c3->id, 'make' => 'M', 'model' => 'N', 'year' => '2019']);

    expect(fn () => $service->book(
        CarbonImmutable::parse('2026-07-27 10:00'), [$svc->id], $v3, $c3
    ))->toThrow(RuntimeException::class);
});

it('sums durations across multiple service types', function () {
    $bay  = ServiceBay::create(['name' => 'Bay 1']);
    $mech = Mechanic::create(['name' => 'Alex']);
    $oil  = ServiceType::create([
        'name' => 'Oil Change', 'duration_minutes' => 30,
        'price_range_min_cents' => 5000, 'price_range_max_cents' => 8000,
    ]);
    $tire = ServiceType::create([
        'name' => 'Tire Rotation', 'duration_minutes' => 30,
        'price_range_min_cents' => 3000, 'price_range_max_cents' => 5000,
    ]);

    $service = new AppointmentAvailabilityService();
    $slots   = $service->getAvailableSlots(
        CarbonImmutable::parse('2026-07-27'),
        CarbonImmutable::parse('2026-07-27'),
        [$oil->id, $tire->id],  // combined = 60 min
    );

    // 17:30 start would end at 18:30 — past close. Must not appear.
    expect($slots['2026-07-27'])->not->toContain('17:30')
        ->and($slots['2026-07-27'])->toContain('17:00');
});
