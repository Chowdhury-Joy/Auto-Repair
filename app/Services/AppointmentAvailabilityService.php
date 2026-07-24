<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\ServiceBay;
use App\Models\ServiceType;
use App\Models\ShopHour;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AppointmentAvailabilityService
{
    /** Slot granularity in minutes. */
    public const SLOT_STEP_MINUTES = 30;

    /**
     * Return every (date, start_time) that has at least one free (bay, mechanic) pair.
     *
     * @param  array<int>  $serviceTypeIds
     * @return array<string, array<string>> ['2026-07-27' => ['09:00','09:30',...], ...]
     */
    public function getAvailableSlots(
        CarbonInterface $fromDate,
        CarbonInterface $toDate,
        array $serviceTypeIds,
    ): array {
        $totalMinutes = ServiceType::whereIn('id', $serviceTypeIds)->sum('duration_minutes');
        if ($totalMinutes <= 0) {
            throw new RuntimeException('No valid service types selected.');
        }

        $from = CarbonImmutable::parse($fromDate)->startOfDay();
        $to = CarbonImmutable::parse($toDate)->endOfDay();

        // Pull ALL active appointments in the window once — overlap checks run in memory.
        $appointments = Appointment::with(['serviceBay', 'mechanic'])
            ->active()
            ->whereBetween('starts_at', [$from, $to])
            ->get();

        $activeWorkOrders = WorkOrder::query()
            ->whereIn('status', [
                WorkOrderStatus::InProgress,
                WorkOrderStatus::AwaitingParts,
            ])
            ->get();

        $bays = ServiceBay::active()->ordered()->get();
        $mechanics = Mechanic::active()->ordered()->get();

        if ($bays->isEmpty() || $mechanics->isEmpty()) {
            return [];
        }

        $result = [];
        $day = $from;

        while ($day->lte($to)) {
            $hours = ShopHour::forDate($day);
            if ($hours === null || $hours->is_closed || $hours->opens_at === null) {
                $day = $day->addDay();

                continue;
            }

            $openTime = $day->copy()->setTimeFromTimeString($hours->opens_at);
            $closeTime = $day->copy()->setTimeFromTimeString($hours->closes_at);

            // Last viable start = close - duration
            $lastStart = $closeTime->copy()->subMinutes($totalMinutes);
            if ($lastStart->lt($openTime)) {
                $day = $day->addDay();

                continue;
            }

            $slots = [];
            $candidate = $openTime->copy();

            while ($candidate->lte($lastStart)) {
                $candidateEnd = $candidate->copy()->addMinutes($totalMinutes);

                if ($this->hasFreePair($bays, $mechanics, $appointments, $activeWorkOrders, $candidate, $candidateEnd)) {
                    $slots[] = $candidate->format('H:i');
                }

                $candidate = $candidate->addMinutes(self::SLOT_STEP_MINUTES);
            }

            if (! empty($slots)) {
                $result[$day->format('Y-m-d')] = $slots;
            }

            $day = $day->addDay();
        }

        return $result;
    }

    /**
     * Book an appointment at a specific start time.
     *
     * Throws if the slot is no longer available (someone else grabbed it).
     */
    public function book(
        CarbonInterface $startsAt,
        array $serviceTypeIds,
        Vehicle $vehicle,
        Customer $customer,
        ?string $customerNotes = null,
    ): Appointment {
        $totalMinutes = ServiceType::whereIn('id', $serviceTypeIds)->sum('duration_minutes');
        $endsAt = CarbonImmutable::parse($startsAt)->addMinutes($totalMinutes);

        // CONCURRENCY CONTROL:
        // Serialize concurrent bookings for the same calendar date using Redis/Cache locks. 
        // This is necessary because multiple customers might try to book the last available 
        // 9:00 AM slot on the same day. By locking on the date (`Y-m-d`), we ensure that 
        // only one booking transaction is processed for that specific day at a time, 
        // preventing double-booking of a bay or mechanic. The 10-second TTL prevents deadlocks 
        // if the PHP process crashes mid-transaction.
        $lockKey = 'truewrench:booking:'.CarbonImmutable::parse($startsAt)->format('Y-m-d');

        return Cache::lock($lockKey, 10)->block(5, function () use (
            $startsAt, $endsAt, $serviceTypeIds, $vehicle, $customer, $customerNotes,
        ) {
            return DB::transaction(function () use (
                $startsAt, $endsAt, $serviceTypeIds, $vehicle, $customer, $customerNotes,
            ) {
                $bays = ServiceBay::active()->ordered()->get();
                $mechanics = Mechanic::active()->ordered()->get();

                // Prevent double booking the same vehicle
                $vehicleBusy = Appointment::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->overlapping($startsAt, $endsAt)
                    ->exists();

                if ($vehicleBusy) {
                    throw new RuntimeException('This vehicle is already scheduled for service at this time.');
                }

                // Re-check under the lock, with a fresh query against the DB
                // (not the cached collection) so we see any just-committed rows.
                $pair = $this->findFreePairInTransaction($bays, $mechanics, $startsAt, $endsAt);

                if ($pair === null) {
                    throw new RuntimeException('The selected time slot is no longer available.');
                }

                [$bayId, $mechanicId] = $pair;

                $appointment = Appointment::create([
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'service_bay_id' => $bayId,
                    'mechanic_id' => $mechanicId,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => AppointmentStatus::Scheduled,
                    'customer_notes' => $customerNotes,
                ]);

                $appointment->serviceTypes()->attach($serviceTypeIds);

                return $appointment->load(['serviceBay', 'mechanic', 'serviceTypes']);
            });
        });
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Checks if there's at least one available combination of a service bay and a mechanic 
     * for the proposed [start, end) time window. 
     * 
     * Note: This is an IN-MEMORY check used for rapidly generating the initial list of 
     * available slots to display on the calendar. It relies on the pre-fetched collections 
     * of bays, mechanics, appointments, and active work orders. It does NOT guarantee 
     * final availability due to race conditions (e.g. another customer booking simultaneously).
     * 
     * @param Collection $bays Pre-fetched active ServiceBays
     * @param Collection $mechanics Pre-fetched active Mechanics
     * @param Collection $appointments Pre-fetched active Appointments in the query window
     * @param Collection $activeWorkOrders Pre-fetched active WorkOrders
     * @param CarbonInterface $start Proposed start time
     * @param CarbonInterface $end Proposed end time
     * @return bool True if a valid (bay, mechanic) pairing is free
     */
    private function hasFreePair(
        Collection $bays,
        Collection $mechanics,
        Collection $appointments,
        Collection $activeWorkOrders,
        CarbonInterface $start,
        CarbonInterface $end,
    ): bool {
        // Pre-compute which bays and mechanics are busy during the window.
        $busyBayIds = [];
        $busyMechanicIds = [];

        foreach ($appointments as $appt) {
            $overlaps = $appt->starts_at < $end && $appt->ends_at > $start;
            if (! $overlaps) {
                continue;
            }

            if ($appt->service_bay_id !== null) {
                $busyBayIds[$appt->service_bay_id] = true;
            }
            if ($appt->mechanic_id !== null) {
                $busyMechanicIds[$appt->mechanic_id] = true;
            }
        }

        foreach ($activeWorkOrders as $wo) {
            $woStart = $wo->opened_at ? CarbonImmutable::parse($wo->opened_at) : now();
            // KNOWN SIMPLIFICATION: we don't track how long a work order is actually
            // expected to take, so this just assumes it occupies its bay/mechanic for
            // one more hour from right now, regardless of how long it's already been
            // open. That means a job open 5+ hours can look "free" sooner than it
            // really is, and a job that's about to wrap up can look busier than it
            // really is. A more accurate model would size this off the original
            // appointment's ServiceType durations. Flagging so a future change to this
            // heuristic is a deliberate decision, not an accidental regression.
            $woEnd = now()->addHours(1);

            $overlaps = $woStart < $end && $woEnd > $start;
            if (! $overlaps) {
                continue;
            }

            if ($wo->service_bay_id !== null) {
                $busyBayIds[$wo->service_bay_id] = true;
            }
            if ($wo->mechanic_id !== null) {
                $busyMechanicIds[$wo->mechanic_id] = true;
            }
        }

        // Any free bay + any free mechanic = a valid pair exists.
        $freeBay = $bays->first(fn ($b) => ! isset($busyBayIds[$b->id]));
        $freeMechanic = $mechanics->first(fn ($m) => ! isset($busyMechanicIds[$m->id]));

        return $freeBay !== null && $freeMechanic !== null;
    }

    /**
     * Final Database Check (The Source of Truth).
     * 
     * Unlike `hasFreePair()`, this method executes live COUNT/EXISTS queries against the 
     * database within an active Database Transaction and an exclusive Cache lock. This ensures 
     * that we see any newly committed appointments/work orders that might have sneaked in 
     * between the user viewing the calendar and clicking "Confirm".
     * 
     * @return array{0: int, 1: int}|null Returns [bayId, mechanicId] or null if no pairs remain.
     */
    private function findFreePairInTransaction(
        Collection $bays,
        Collection $mechanics,
        CarbonInterface $start,
        CarbonInterface $end,
    ): ?array {
        foreach ($bays as $bay) {
            $bayBusy = Appointment::query()
                ->forBay($bay->id)
                ->overlapping($start, $end)
                ->exists();

            $bayBusyByWO = false;
            if (now()->addHour()->gt($start)) {
                $bayBusyByWO = WorkOrder::query()
                    ->where('service_bay_id', $bay->id)
                    ->whereIn('status', [
                        WorkOrderStatus::InProgress,
                        WorkOrderStatus::AwaitingParts,
                    ])
                    ->where('opened_at', '<', $end)
                    ->exists();
            }

            if ($bayBusy || $bayBusyByWO) {
                continue;
            }

            foreach ($mechanics as $mechanic) {
                $mechanicBusy = Appointment::query()
                    ->forMechanic($mechanic->id)
                    ->overlapping($start, $end)
                    ->exists();

                $mechanicBusyByWO = false;
                if (now()->addHour()->gt($start)) {
                    $mechanicBusyByWO = WorkOrder::query()
                        ->where('mechanic_id', $mechanic->id)
                        ->whereIn('status', [
                            WorkOrderStatus::InProgress,
                            WorkOrderStatus::AwaitingParts,
                        ])
                        ->where('opened_at', '<', $end)
                        ->exists();
                }

                if (! $mechanicBusy && ! $mechanicBusyByWO) {
                    return [$bay->id, $mechanic->id];
                }
            }
        }

        return null;
    }
}
