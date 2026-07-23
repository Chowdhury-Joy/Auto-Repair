<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case Open            = 'open';
    case AwaitingParts   = 'awaiting_parts';
    case InProgress      = 'in_progress';
    case ReadyForPickup  = 'ready_for_pickup';
    case Completed       = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Open           => 'Open',
            self::AwaitingParts  => 'Awaiting Parts',
            self::InProgress     => 'In Progress',
            self::ReadyForPickup => 'Ready for Pickup',
            self::Completed      => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open           => 'gray',
            self::AwaitingParts  => 'warning',
            self::InProgress     => 'primary',
            self::ReadyForPickup => 'success',
            self::Completed      => 'success',
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::Open           => 'Checked In',
            self::AwaitingParts  => 'Awaiting Parts',
            self::InProgress     => 'In Progress',
            self::ReadyForPickup => 'Ready for Pickup',
            self::Completed      => 'Completed',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Completed;
    }
}
