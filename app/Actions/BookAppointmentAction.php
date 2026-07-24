<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Exceptions\AccountConflictException;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AppointmentAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BookAppointmentAction
{
    /**
     * Executes the booking process within a database transaction.
     * Resolves the customer and vehicle, then attempts to book via the availability service.
     *
     * @param array $data Expected keys: contactName, contactEmail, contactPhone, 
     *                    useExistingVehicle, existingVehicleId, newVehicleMake, 
     *                    newVehicleModel, newVehicleYear, newVehiclePlate, 
     *                    newVehicleMileage, selectedDate, selectedTime, 
     *                    selectedServiceIds, customerNotes.
     * @return Appointment
     * @throws RuntimeException
     */
    public function execute(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $customer = $this->resolveCustomer($data);
            $vehicle = $this->resolveVehicle($customer, $data);

            $startsAt = CarbonImmutable::parse($data['selectedDate'].' '.$data['selectedTime']);
            
            $service = app(AppointmentAvailabilityService::class);

            return $service->book(
                $startsAt,
                $data['selectedServiceIds'],
                $vehicle,
                $customer,
                $data['customerNotes'] ?? null,
            );
        });
    }

    private function resolveCustomer(array $data): Customer
    {
        if (Auth::check()) {
            $user = Auth::user();
            return $user->customer ?: $user->customer()->create([]);
        }

        // Normalize before matching/storing: emails are case-insensitive in practice
        // (RFC 5321 technically leaves the local part case-sensitive, but no real mail
        // provider treats it that way), and without this a guest could dodge the
        // account-conflict check below just by typing "Jane@Example.com" instead of
        // "jane@example.com".
        $email = Str::lower(trim($data['contactEmail']));

        // SECURITY: this is the fix for a real account-takeover bug — a guest could
        // previously "book" using any existing customer's email and the appointment
        // (and a brand-new vehicle) would silently attach to that stranger's real
        // account, with no password or verification required at all. Do not remove
        // this check to "simplify" guest checkout; it's the actual auth boundary.
        $user = User::where('email', $email)->first();
        if ($user) {
            throw new AccountConflictException(
                'An account already exists for this email address. Please log in to book an appointment.'
            );
        }

        // Guest accounts get a random password (not a shared default) — the customer
        // sets their own via the "Forgot Password" flow (App\Livewire\Auth\ForgotPassword)
        // the first time they want to log in, so this value is never used directly.
        $password = Str::password(12);

        $user = User::create([
            'name' => $data['contactName'],
            'email' => $email,
            'password' => bcrypt($password),
            'phone' => $data['contactPhone'],
            'role' => UserRole::Customer,
        ]);

        session()->flash('success', "An account has been created for you. You can set a password anytime using the 'Forgot Password' link on the login page.");

        return $user->customer ?: $user->customer()->create([]);
    }

    private function resolveVehicle(Customer $customer, array $data): Vehicle
    {
        if (!empty($data['useExistingVehicle']) && !empty($data['existingVehicleId'])) {
            $vehicle = Vehicle::where('id', $data['existingVehicleId'])
                ->where('customer_id', $customer->id)
                ->first();
            if ($vehicle) {
                return $vehicle;
            }
        }

        return Vehicle::create([
            'customer_id' => $customer->id,
            'make' => $data['newVehicleMake'] ?? '',
            'model' => $data['newVehicleModel'] ?? '',
            'year' => $data['newVehicleYear'] ?? '',
            'license_plate' => !empty($data['newVehiclePlate']) ? $data['newVehiclePlate'] : null,
            'current_mileage' => !empty($data['newVehicleMileage']) ? (int) $data['newVehicleMileage'] : null,
        ]);
    }
}
