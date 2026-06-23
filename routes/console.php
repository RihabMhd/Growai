<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('orders:mark-abandoned')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('app:process-recovery-rules')
    ->everyTenMinutes()
    ->withoutOverlapping();
