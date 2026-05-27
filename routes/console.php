<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:send-monthly-reports --send')
    ->monthlyOn(1, '06:00')
    ->timezone(config('app.timezone', 'Asia/Manila'))
    ->description('Generate and email previous month attendance reports to advisers');
