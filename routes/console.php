<?php
use Illuminate\Support\Facades\Schedule;

if (! config('zkteco.push.enabled')) {
    Schedule::command('zkteco:sync')
        ->everyMinute()
        ->withoutOverlapping();
}