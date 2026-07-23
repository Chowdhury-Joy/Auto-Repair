<?php

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\OperationsAlert;
use App\Models\ServiceBay;
use App\Models\ShopHour;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\CheckInService;
use App\Services\OperationsAlertService;
use App\Services\WorkOrderCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
});

it('creates a work order when checking in an appointment', function () {
    $user = User::factory()->create();
    $cust = Customer::create(['user_id' => $user->id]);
    $veh = Vehicle::create(['customer_id' => $cust->id, 'make' => 'Honda', 'model' => 'Civic', 'year' => '2020']);
    $bay = ServiceBay::create(['name' => 'Bay 1']);
    $mech = Mechanic::create(['name' => 'Alex']);

    $appt = Appointment::create([
        'customer_id' => $cust->id,
        'vehicle_id' => $veh->id,
        'service_bay_id' => $bay->id,
        'mechanic_id' => $mech->id,
        'starts_at' => now(),
        'ends_at' => now()->addHour(),
        'status' => AppointmentStatus::Scheduled,
    ]);

    $service = new CheckInService;
    $wo = $service->checkIn($appt);

    expect($appt->fresh()->status)->toBe(AppointmentStatus::CheckedIn)
        ->and($wo->status)->toBe(WorkOrderStatus::Open)
        ->and($wo->customer_id)->toBe($cust->id)
        ->and($wo->vehicle_id)->toBe($veh->id);
});

it('completes work order and creates draft invoice with recalculated total', function () {
    $user = User::factory()->create();
    $cust = Customer::create(['user_id' => $user->id]);
    $veh = Vehicle::create(['customer_id' => $cust->id, 'make' => 'Toyota', 'model' => 'Camry', 'year' => '2021']);

    $wo = WorkOrder::create([
        'customer_id' => $cust->id,
        'vehicle_id' => $veh->id,
        'status' => WorkOrderStatus::InProgress,
        'opened_at' => now()->subHours(2),
    ]);

    $wo->items()->create(['description' => 'Oil Filter', 'type' => 'part', 'quantity' => 1, 'rate_cents' => 1500]);
    $wo->items()->create(['description' => 'Labor 1hr', 'type' => 'labor', 'quantity' => 1, 'rate_cents' => 8500]);

    $completionService = new WorkOrderCompletionService;
    $invoice = $completionService->complete($wo);

    expect($wo->fresh()->status)->toBe(WorkOrderStatus::Completed)
        ->and($wo->fresh()->total_cents)->toBe(10000)
        ->and($invoice)->not->toBeNull()
        ->and($invoice->total_cents)->toBe(10000)
        ->and($invoice->status)->toBe(InvoiceStatus::Draft);
});

it('generates operations alerts for unconfirmed appointments and stuck work orders', function () {
    $user = User::factory()->create();
    $cust = Customer::create(['user_id' => $user->id]);
    $veh = Vehicle::create(['customer_id' => $cust->id, 'make' => 'Ford', 'model' => 'F-150', 'year' => '2019']);

    // Unconfirmed appointment starting in 12 hours
    Appointment::create([
        'customer_id' => $cust->id,
        'vehicle_id' => $veh->id,
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(13),
        'status' => AppointmentStatus::Scheduled,
        'confirmed_at' => null,
    ]);

    // Work order open for > 8 hours
    WorkOrder::create([
        'customer_id' => $cust->id,
        'vehicle_id' => $veh->id,
        'status' => WorkOrderStatus::InProgress,
        'opened_at' => now()->subHours(10),
    ]);

    $alertService = new OperationsAlertService;
    $alertService->checkAndCreateAlerts();

    expect(OperationsAlert::count())->toBe(2);
});
