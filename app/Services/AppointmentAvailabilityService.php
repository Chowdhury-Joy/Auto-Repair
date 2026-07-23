<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\ServiceBay;
use App\Models\ServiceType;
use App\Models\ShopHour;
use App\Models\Vehicle;
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
     * @return array<string, array<string>>  ['2026-07-27' => ['09:00','09:30',...], ...]
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
        $to   = CarbonImmutable::parse($toDate)->endOfDay();

        // Pull ALL active appointments in the window once — overlap checks run in memory.
        $appointments = Appointment::with(['serviceBay', 'mechanic'])
            ->active()
            ->whereBetween('starts_at', [$from, $to])
            ->get();

        $bays      = ServiceBay::active()->ordered()->get();
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

            $openTime  = $day->copy()->setTimeFromTimeString($hours->opens_at);
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

                if ($this->hasFreePair($bays, $mechanics, $appointments, $candidate, $candidateEnd)) {
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
        $endsAt       = CarbonImmutable::parse($startsAt)->addMinutes($totalMinutes);

        // Serialize concurrent bookings for the same day. Keyed by date so different
        // days don't contend. 10-second TTL is a safety net; we release explicitly.
        $lockKey = 'truewrench:booking:' . CarbonImmutable::parse($startsAt)->format('Y-m-d');

        return Cache::lock($lockKey, 10)->block(5, function () use (
            $startsAt, $endsAt, $serviceTypeIds, $vehicle, $customer, $customerNotes,
        ) {
            return DB::transaction(function () use (
                $startsAt, $endsAt, $serviceTypeIds, $vehicle, $customer, $customerNotes,
            ) {
                $bays      = ServiceBay::active()->ordered()->get();
                $mechanics = Mechanic::active()->ordered()->get();

                // Re-check under the lock, with a fresh query against the DB
                // (not the cached collection) so we see any just-committed rows.
                $pair = $this->findFreePairInTransaction($bays, $mechanics, $startsAt, $endsAt);

                if ($pair === null) {
                    throw new RuntimeException('The selected time slot is no longer available.');
                }

                [$bayId, $mechanicId] = $pair;

                $appointment = Appointment::create([
                    'customer_id'    => $customer->id,
                    'vehicle_id'     => $vehicle->id,
                    'service_bay_id' => $bayId,
                    'mechanic_id'    => $mechanicId,
                    'starts_at'      => $startsAt,
                    'ends_at'        => $endsAt,
                    'status'         => AppointmentStatus::Scheduled,
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
     * Given pre-fetched bays, mechanics, and appointments, determine whether at
     * least one (bay, mechanic) pair is free for the entire [start, end) window.
     */
    private function hasFreePair(
        Collection $bays,
        Collection $mechanics,
        Collection $appointments,
        CarbonInterface $start,
        CarbonInterface $end,
    ): bool {
        // Pre-compute which bays and mechanics are busy during the window.
        $busyBayIds      = [];
        $busyMechanicIds = [];

        foreach ($appointments as $appt) {
            $overlaps = $appt->starts_at < $end && $appt->ends_at > $start;
            if (! $overlaps) continue;

            if ($appt->service_bay_id !== null) {
                $busyBayIds[$appt->service_bay_id] = true;
            }
            if ($appt->mechanic_id !== null) {
                $busyMechanicIds[$appt->mechanic_id] = true;
            }
        }

        // Any free bay + any free mechanic = a valid pair exists.
        $freeBay      = $bays->first(fn ($b) => ! isset($busyBayIds[$b->id]));
        $freeMechanic = $mechanics->first(fn ($m) => ! isset($busyMechanicIds[$m->id]));

        return $freeBay !== null && $freeMechanic !== null;
    }

    /**
     * Same idea as hasFreePair, but queries the DB under the lock so we see
     * the absolute latest state. Returns [bayId, mechanicId] or null.
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

            if ($bayBusy) continue;

            foreach ($mechanics as $mechanic) {
                $mechanicBusy = Appointment::query()
                    ->forMechanic($mechanic->id)
                    ->overlapping($start, $end)
                    ->exists();

                if (! $mechanicBusy) {
                    return [$bay->id, $mechanic->id];
                }
            }
        }

        return null;
    }
}
