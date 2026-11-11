<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\BillingService;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// every hour: overdue invoices, expired bookings, featured start and end (nobody has to log in for this)
Schedule::call(fn () => BillingService::checkOverdue())->hourly()->name('check-overdue');