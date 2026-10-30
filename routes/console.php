<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



Schedule::command('hrms:zkteco-sync')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('hrms:zkteco-sync scheduled run failed');
    });