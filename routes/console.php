<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


use Illuminate\Support\Facades\Schedule;

Schedule::command("guests:cleanup")->dailyAt("03:00");

Schedule::command("extraction:cleanup")->dailyAt("03:30");
