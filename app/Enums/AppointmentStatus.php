<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case CheckedIn = 'checked_in';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case NoShow = 'no_show';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::CheckedIn => 'Checked In',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::NoShow => 'No Show',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::CheckedIn => 'warning',
            self::InProgress => 'primary',
            self::Completed => 'success',
            self::NoShow => 'danger',
            self::Cancelled => 'gray',
        };
    }

    /** Terminal / non-occupying statuses — these don't block the bay or mechanic. */
    public function isNonOccupying(): bool
    {
        return in_array($this, [
            self::Completed, self::NoShow, self::Cancelled,
        ]);
    }
}
