<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Staff    = 'staff';
    case Admin    = 'admin';

    public function canAccessFilament(): bool
    {
        return $this !== self::Customer;
    }
}
