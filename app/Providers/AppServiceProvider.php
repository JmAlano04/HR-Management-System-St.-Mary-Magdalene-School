<?php

namespace App\Providers;

use App\Listeners\SyncZKTecoAttendanceRecords;
use HafizAsifAli\ZkTeco\Laravel\Events\AttendanceSynced;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(AttendanceSynced::class, SyncZKTecoAttendanceRecords::class);
    }
    
}
