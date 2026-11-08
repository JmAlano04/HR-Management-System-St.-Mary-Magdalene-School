<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
<<<<<<< HEAD
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

=======
use Illuminate\Support\Facades\Schedule;


>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

<<<<<<< HEAD
=======


>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
Schedule::command('hrms:zkteco-sync')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->onFailure(function () {
<<<<<<< HEAD
        Log::error('hrms:zkteco-sync scheduled run failed');
    });

Schedule::command('hrms:mark-attendance-absences')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->onFailure(function () {
        Log::error('hrms:mark-attendance-absences scheduled run failed');
    });
=======
        \Illuminate\Support\Facades\Log::error('hrms:zkteco-sync scheduled run failed');
    });
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
