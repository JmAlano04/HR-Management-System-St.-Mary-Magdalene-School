<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveManagementController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\OffboardingController;
use App\Http\Controllers\OnboardingManagementController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollProcessingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryRecordController;
use App\Http\Controllers\SalaryMonitoringController;
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
 
        Route::patch('/offboarding/{offboardingCase}/approve', [OffboardingController::class, 'approve'])
            ->name('offboarding.approve');
 
        Route::get('/offboarding/{offboardingCase}/documents/{document}', [OffboardingController::class, 'document'])
            ->name('offboarding.document')
            ->scopeBindings();

        // ---------- Document Management ----------
        Route::get('/document', [DocumentController::class, 'index'])
            ->name('documents.index');

        Route::post('/documents/boxes', [DocumentController::class, 'storeBox'])
            ->name('boxes.store');

        Route::patch('/documents/decision', [DocumentController::class, 'decide'])
            ->name('documents.decide');

        Route::delete('/documents', [DocumentController::class, 'destroy'])
            ->name('destroy');
                
    });

    // Admin + HR + Employee
    Route::middleware('role:Admin|HR|Employee')->group(function () {

       // Employee
       Route::resource('employees', EmployeeController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    
        
        Route::get('/attendance-monitoring', [AttendanceController::class, 'index'])
            ->name('attendance.index');
 
        Route::get('/attendance-monitoring/export', [AttendanceController::class, 'export'])
            ->name('attendance.export');
 
        Route::patch('/attendance-monitoring/archive', [AttendanceController::class, 'archive'])
            ->name('attendance.archive');
 
        Route::delete('/attendance-monitoring/archive', [AttendanceController::class, 'destroy'])
            ->name('attendance.destroy');

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
      
    
    });

    // Admin + HR + Accounting
    Route::middleware('role:Admin|HR|Accounting')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');


          Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::patch('approve', [PayrollController::class, 'approve'])->name('payroll.approve');
        Route::patch('approve-all', [PayrollController::class, 'approveAll'])->name('payroll.approve-all');
        Route::patch('reject', [PayrollController::class, 'reject'])->name('payroll.reject');


             Route::get('/payroll-processing', [PayrollProcessingController::class, 'index'])
            ->name('payroll-accounting.index');

            Route::get('/salary-monitoring', [SalaryMonitoringController::class, 'index'])
            ->name('salary-monitoring.index');
            
            Route::get('/salary-records', [SalaryRecordController::class, 'index'])
            ->name('salary-records.index');
        
        
        
        Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');

        Route::get('/loans', [LoanController::class, 'index'])
            ->name('loans.index');
 
        Route::patch('/loans/{loanRequest}/decision', [LoanController::class, 'decide'])
            ->name('loans.decide');
    });




    // Applicant only
    Route::middleware('role:Applicant')->group(function () {
        Route::get('/applications', [ApplicationController::class, 'index'])
        ->name('applications.index');
        Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');

        
    });
});

require __DIR__.'/auth.php';