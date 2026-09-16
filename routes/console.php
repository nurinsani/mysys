<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('activitylog:clean akses --days=60 --force')->quarterly();

Schedule::command('pembiayaan:update-kolektibilitas')->dailyAt('01:00');
