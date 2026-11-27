<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run', ['--type' => 'database', '--notes' => 'Daily automated database backup'])
    ->dailyAt('02:00')
    ->withoutOverlapping();

Schedule::command('backup:run', ['--type' => 'full', '--notes' => 'Weekly automated full backup'])
    ->weeklyOn(0, '03:00')
    ->withoutOverlapping();

Schedule::command('edupulse:ai-manager-tick')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('commerce:expire-reservations')
    ->everyFiveMinutes()
    ->withoutOverlapping();
