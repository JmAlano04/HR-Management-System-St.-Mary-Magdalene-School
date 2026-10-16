<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\OffboardingController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TimeCorrectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Role-protected pages (match the sidebar in layouts/navigation.blade.php)
|--------------------------------------------------------------------------
| Role names must match your Spatie roles exactly:
| Admin, HR, Accounting, Employee, Applicant
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // Admin + HR
    Route::middleware('role:Admin|HR')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])
        ->name('employees.index');
        Route::get('onboarding', [OnboardingController::class, 'index'])
        ->name('onboarding.index');
        Route::get('/offboarding', [OffboardingController::class, 'index'])
        ->name('offboarding.index');
    });

    // Admin + HR + Employee
    Route::middleware('role:Admin|HR|Employee')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/time-corrections', [TimeCorrectionController::class, 'index'])->name('time-corrections.index');
        Route::get('leaves', [LeaveController::class, 'index'])->name('leaves.index');
    });

    // Admin + Accounting + Employee
    Route::middleware('role:Admin|Accounting|Employee')->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    });

    // Admin + HR + Accounting
    Route::middleware('role:Admin|HR|Accounting')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    // // Applicant only
    // Route::middleware('role:Applicant')->group(function () {
    //     Route::resource('applications', ApplicationController::class);
    // });
});

require __DIR__.'/auth.php';