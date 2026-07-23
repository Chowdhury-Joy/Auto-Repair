<?php

use App\Services\OperationsAlertService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Check for unconfirmed appointments and stuck work orders every hour.
Schedule::call(function () {
    app(OperationsAlertService::class)->checkAndCreateAlerts();
})->hourly();
