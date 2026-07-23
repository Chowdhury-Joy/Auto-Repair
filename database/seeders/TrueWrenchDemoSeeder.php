<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\ServiceBay;
use App\Models\ServiceType;
use App\Models\ShopHour;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TrueWrenchDemoSeeder extends Seeder
{
    public function run(): void
    {
        // --- Shop hours: Mon-Fri 8-6, Sat 9-2, Sun closed ---
        $hours = [
            1 => ['08:00','18:00',false],
            2 => ['08:00','18:00',false],
            3 => ['08:00','18:00',false],
            4 => ['08:00','18:00',false],
            5 => ['08:00','18:00',false],
            6 => ['09:00','14:00',false],
            7 => [null,   null,   true],
        ];
        foreach ($hours as $dow => [$open, $close, $closed]) {
            ShopHour::create([
                'day_of_week' => $dow,
                'opens_at'    => $open,
                'closes_at'   => $close,
                'is_closed'   => $closed,
            ]);
        }

        // --- Bays ---
        ServiceBay::create(['name' => 'Bay 1', 'sort_order' => 1]);
        ServiceBay::create(['name' => 'Bay 2', 'sort_order' => 2]);
        ServiceBay::create(['name' => 'Bay 3 (Diagnostics)', 'sort_order' => 3]);

        // --- Mechanics ---
        Mechanic::create(['name' => 'Marcus Reed',   'specialty' => 'Diagnostics', 'sort_order' => 1]);
        Mechanic::create(['name' => 'Elena Vasquez', 'specialty' => 'Brakes/Suspension', 'sort_order' => 2]);
        Mechanic::create(['name' => 'Darnell King',  'specialty' => 'General', 'sort_order' => 3]);
        Mechanic::create(['name' => 'Priya Shah',    'specialty' => 'Transmissions', 'sort_order' => 4]);

        // --- Service menu ---
        $menu = [
            ['Oil Change',          'Standard or synthetic oil change with filter.', 30,  4500,  8500],
            ['Brake Service',       'Pad replacement, rotor inspection, fluid top-off.', 90, 15000, 35000],
            ['Diagnostic',          'Check-engine / electrical diagnosis, 1-hour max.', 60, 9500, 15000],
            ['Tire Rotation',       'Rotate and inspect all four tires.', 30, 3000, 5000],
            ['State Inspection',    'Annual safety and emissions inspection.', 45, 6000, 9000],
            ['Transmission Service','Fluid drain-and-fill, pan inspection.', 90, 18000, 30000],
        ];
        foreach ($menu as $i => [$name, $desc, $mins, $lo, $hi]) {
            ServiceType::create([
                'name' => $name, 'description' => $desc,
                'duration_minutes' => $mins,
                'price_range_min_cents' => $lo,
                'price_range_max_cents' => $hi,
                'sort_order' => $i + 1,
            ]);
        }

        // --- Users + Customers ---
        $admin = User::create([
            'name' => 'Shop Admin', 'email' => 'admin@truewrench.demo',
            'password' => Hash::make('password'), 'role' => UserRole::Admin,
        ]);
        Customer::create(['user_id' => $admin->id]);

        $staff = User::create([
            'name' => 'Marcus Reed', 'email' => 'marcus@truewrench.demo',
            'password' => Hash::make('password'), 'role' => UserRole::Staff, 'phone' => '555-0100',
        ]);
        Customer::create(['user_id' => $staff->id]);

        $jane = User::create([
            'name' => 'Jane Ortiz', 'email' => 'jane@example.com',
            'password' => Hash::make('password'), 'role' => UserRole::Customer, 'phone' => '555-0142',
        ]);
        $janeCust = Customer::create([
            'user_id' => $jane->id, 'city' => 'Portland', 'state' => 'OR', 'postal_code' => '97201',
        ]);
        Vehicle::create([
            'customer_id' => $janeCust->id, 'make' => 'Toyota', 'model' => 'Camry',
            'year' => '2019', 'vin' => '1HGCM82633A004352', 'license_plate' => 'OR-8821',
            'current_mileage' => 64200, 'color' => 'Silver',
        ]);
        Vehicle::create([
            'customer_id' => $janeCust->id, 'make' => 'Honda', 'model' => 'CR-V',
            'year' => '2022', 'license_plate' => 'OR-1144', 'current_mileage' => 18500, 'color' => 'Blue',
        ]);

        $rob = User::create([
            'name' => 'Rob Chen', 'email' => 'rob@example.com',
            'password' => Hash::make('password'), 'role' => UserRole::Customer, 'phone' => '555-0199',
        ]);
        $robCust = Customer::create(['user_id' => $rob->id]);
        Vehicle::create([
            'customer_id' => $robCust->id, 'make' => 'Ford', 'model' => 'F-150',
            'year' => '2021', 'license_plate' => 'OR-7712', 'current_mileage' => 41000,
        ]);
    }
}
