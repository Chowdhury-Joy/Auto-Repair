<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class ShopHour extends Model
{
    protected $fillable = ['day_of_week', 'opens_at', 'closes_at', 'is_closed'];

    protected $casts = ['day_of_week' => 'integer', 'is_closed' => 'boolean'];

    /** Get the ShopHour row for a given date. */
    public static function forDate(CarbonInterface $date): ?self
    {
        return self::where('day_of_week', $date->dayOfWeekIso)->first();
    }
}
