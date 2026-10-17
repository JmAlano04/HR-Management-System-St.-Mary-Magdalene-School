<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveManagementController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\OffboardingController;
use App\Http\Controllers\OnboardingManagementController;
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
        // Employee
       Route::resource('employees', EmployeeController::class)
        ->only(['index', 'store', 'update', 'destroy']);


            // ---------- Onboarding Management ----------
        Route::get('/onboarding', [OnboardingManagementController::class, 'index'])
            ->name('onboarding.index');
        Route::patch('/onboarding/{application}/decision', [OnboardingManagementController::class, 'decide'])
            ->name('onboarding.decide');
        Route::delete('/onboarding/{application}', [OnboardingManagementController::class, 'destroy'])
            ->name('onboarding.destroy');
        Route::get('/onboarding/{application}/documents/{type}', [OnboardingManagementController::class, 'document'])
            ->name('onboarding.document');


            
        Route::get('/offboarding', [OffboardingController::class, 'index'])
        ->name('offboarding.index');
    });

    // Admin + HR + Employee
    Route::middleware('role:Admin|HR|Employee')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])
            ->name('attendance.index');

        Route::get('/time-corrections', [TimeCorrectionController::class, 'index'])
            ->name('time-corrections.index');

        Route::patch('/time-corrections/decision', [
            TimeCorrectionController::class,
            'decide'
        ])->name('decide');

        Route::get('/time-corrections/{timeCorrection}/proof', [
            TimeCorrectionController::class,
            'proof'
        ])->name('proof');

        Route::get('/leaves', [LeaveManagementController::class, 'index'])
            ->name('leaves.index');
        
        Route::get('/leaves', [LeaveManagementController::class, 'index'])->name('leaves.index');
        Route::patch('/leaves/{leaveRequest}/status', [LeaveManagementController::class, 'updateStatus'])->name('leave.status');
        Route::patch('/leaves/{leaveRequest}/archive', [LeaveManagementController::class, 'archive'])->name('leave.archive');
        Route::delete('/leaves/archive/clear', [LeaveManagementController::class, 'clearArchive'])->name('leave.archive.clear');

        });

    // Time corrections: Admin at HR lang ang puwedeng mag-approve, mag-reject, at magbukas ng proof
Route::middleware('role:Admin|HR')
    ->prefix('time-corrections')
    ->name('time-corrections.')
    ->group(function () {
       
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

    // Applicant only
    Route::middleware('role:Applicant')->group(function () {
        Route::get('/applications', [ApplicationController::class, 'index'])
        ->name('applications.index');
        Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');

        
    });
});

require __DIR__.'/auth.php';