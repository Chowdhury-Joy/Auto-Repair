<?php

namespace App\Livewire;

use App\Actions\BookAppointmentAction;
use App\Enums\UserRole;
use App\Exceptions\AccountConflictException;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AppointmentAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class BookAppointment extends Component
{
    // Step 1: service selection
    public array $selectedServiceIds = [];

    // Step 2: date + time
    public ?string $selectedDate = null;

    public ?string $selectedTime = null;

    // Step 3: vehicle + contact
    public bool $useExistingVehicle = false;

    public ?int $existingVehicleId = null;

    public string $newVehicleMake = '';

    public string $newVehicleModel = '';

    public string $newVehicleYear = '';

    public string $newVehiclePlate = '';

    public string $newVehicleMileage = '';

    public string $contactName = '';

    public string $contactEmail = '';

    public string $contactPhone = '';

    public string $customerNotes = '';

    // Flow state
    #[Locked]
    public int $step = 1;

    public ?int $confirmedAppointmentId = null;

    // Set when confirm() is rejected because the typed contact email already belongs
    // to an existing account (see AccountConflictException) — the view uses this to
    // show a "log in instead" link rather than a generic error banner.
    public bool $emailConflict = false;

    // Cached availability (recomputed when services or date change)
    public array $availableSlots = [];

    public function mount(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            $this->contactName = $user->name;
            $this->contactEmail = $user->email;
            $this->contactPhone = $user->phone ?? '';
        }
        $this->recomputeSlots();
    }

    // -------------------------------------------------------------------------
    // Step navigation
    // -------------------------------------------------------------------------

    public function updatedSelectedServiceIds(): void
    {
        // Service selection changed — slots may differ (different total duration).
        $this->selectedDate = null;
        $this->selectedTime = null;
        $this->recomputeSlots();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedTime = null;
    }

    public function selectTime(string $time): void
    {
        $this->selectedTime = $time;
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            if (empty($this->selectedServiceIds)) {
                session()->flash('error', 'Please select at least one service.');

                return;
            }
            $this->step = 2;
            $this->recomputeSlots();
        } elseif ($this->step === 2) {
            if ($this->selectedDate === null || $this->selectedTime === null) {
                session()->flash('error', 'Please pick a date and time.');

                return;
            }
            // Re-verify the slot is still available before letting them proceed.
            if (! $this->slotStillAvailable()) {
                session()->flash('error', 'Sorry, that slot was just taken. Please pick another.');
                $this->recomputeSlots();

                return;
            }
            $this->step = 3;
        } elseif ($this->step === 3) {
            $this->validate([
                'contactName' => 'required|string|max:255',
                'contactEmail' => 'required|email|max:255',
                'contactPhone' => 'required|string|max:20',
                'newVehicleMake' => 'required_if:useExistingVehicle,false|string|max:255',
                'newVehicleModel' => 'required_if:useExistingVehicle,false|string|max:255',
                'newVehicleYear' => 'required_if:useExistingVehicle,false|string|size:4',
                'newVehiclePlate' => 'nullable|string|max:20',
                'newVehicleMileage' => 'nullable|integer|min:0',
            ]);

            // Surface the account-conflict rule here too (not just in confirm()) so a
            // guest finds out before reaching the review screen. This is a UX nicety
            // only — BookAppointmentAction::resolveCustomer() is the actual boundary
            // that enforces it, since this step can be skipped by calling confirm()
            // directly.
            if ($this->emailBelongsToExistingAccount()) {
                $this->emailConflict = true;
                session()->flash('error', 'An account already exists for this email address. Please log in to book an appointment.');

                return;
            }

            $this->emailConflict = false;
            $this->step = 4;
        }
    }

    private function emailBelongsToExistingAccount(): bool
    {
        if (Auth::check()) {
            return false;
        }

        return User::where('email', Str::lower(trim($this->contactEmail)))->exists();
    }

    public function backStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    // -------------------------------------------------------------------------
    // Submission
    // -------------------------------------------------------------------------

    public function confirm(): void
    {
        // Final re-check under the same lock the service uses.
        if (! $this->slotStillAvailable()) {
            session()->flash('error', 'That slot is no longer available. Please choose another.');
            $this->step = 2;
            $this->recomputeSlots();

            return;
        }

        try {
            $appointment = $this->createAppointment();
            $this->confirmedAppointmentId = $appointment->id;
            $this->step = 5;
        } catch (AccountConflictException $e) {
            // Same rule as the nextStep() check above, but this is the one that
            // actually matters — it fires even if step 3's check was bypassed
            // (e.g. by calling confirm() directly), because the enforcement lives
            // in BookAppointmentAction, not in this component's UI flow.
            $this->emailConflict = true;
            session()->flash('error', $e->getMessage());
            $this->step = 3;
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            $this->step = 2;
            $this->recomputeSlots();
        }
    }

    public function startOver(): void
    {
        $this->reset();
        $this->mount();
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function recomputeSlots(): void
    {
        if (empty($this->selectedServiceIds)) {
            $this->availableSlots = [];

            return;
        }

        $service = app(AppointmentAvailabilityService::class);
        $from = CarbonImmutable::now()->startOfDay();
        $to = $from->addDays(13); // 14-day window

        $this->availableSlots = $service->getAvailableSlots($from, $to, $this->selectedServiceIds);

        // Default to the earliest date that has slots.
        if ($this->selectedDate === null && ! empty($this->availableSlots)) {
            $this->selectedDate = array_key_first($this->availableSlots);
        }
    }

    private function slotStillAvailable(): bool
    {
        if ($this->selectedDate === null || $this->selectedTime === null) {
            return false;
        }

        $service = app(AppointmentAvailabilityService::class);
        $slots = $service->getAvailableSlots(
            CarbonImmutable::parse($this->selectedDate),
            CarbonImmutable::parse($this->selectedDate),
            $this->selectedServiceIds,
        );

        return isset($slots[$this->selectedDate])
            && in_array($this->selectedTime, $slots[$this->selectedDate], true);
    }

    private function createAppointment(): Appointment
    {
        $action = app(BookAppointmentAction::class);
        
        return $action->execute([
            'contactName' => $this->contactName,
            'contactEmail' => $this->contactEmail,
            'contactPhone' => $this->contactPhone,
            'useExistingVehicle' => $this->useExistingVehicle,
            'existingVehicleId' => $this->existingVehicleId,
            'newVehicleMake' => $this->newVehicleMake,
            'newVehicleModel' => $this->newVehicleModel,
            'newVehicleYear' => $this->newVehicleYear,
            'newVehiclePlate' => $this->newVehiclePlate,
            'newVehicleMileage' => $this->newVehicleMileage,
            'selectedDate' => $this->selectedDate,
            'selectedTime' => $this->selectedTime,
            'selectedServiceIds' => $this->selectedServiceIds,
            'customerNotes' => $this->customerNotes,
        ]);
    }

    // -------------------------------------------------------------------------
    // View helpers
    // -------------------------------------------------------------------------

    public function getServiceMenuProperty()
    {
        return ServiceType::active()->menuOrdered()->get();
    }

    public function getTotalDurationProperty(): int
    {
        return ServiceType::whereIn('id', $this->selectedServiceIds)->sum('duration_minutes');
    }

    public function getPriceRangeProperty(): string
    {
        $services = ServiceType::whereIn('id', $this->selectedServiceIds)->get();
        if ($services->isEmpty()) {
            return '';
        }
        $min = $services->sum('price_range_min_cents');
        $max = $services->sum('price_range_max_cents');

        return '$'.number_format($min / 100, 0).' – $'.number_format($max / 100, 0);
    }

    public function getMyVehiclesProperty()
    {
        if (! Auth::check()) {
            return collect();
        }
        $customer = Auth::user()->customer;

        return $customer ? $customer->vehicles : collect();
    }

    public function getConfirmedAppointmentProperty()
    {
        if (! $this->confirmedAppointmentId) {
            return null;
        }

        return Appointment::with(['serviceBay', 'mechanic', 'vehicle', 'serviceTypes', 'customer.user'])
            ->find($this->confirmedAppointmentId);
    }

    public function render()
    {
        return view('livewire.book-appointment')
            ->layout('components.layouts.public', ['title' => 'Book an Appointment · TrueWrench']);
    }
}
