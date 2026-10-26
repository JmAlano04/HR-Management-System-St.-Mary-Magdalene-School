
<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SchoolEmployeeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDashboardController;
use App\Http\Controllers\LeaveManagementController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\OffboardingController;
use App\Http\Controllers\OnboardingManagementController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\PayrollProcessingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryRecordController;
use App\Http\Controllers\SalaryMonitoringController;
use App\Http\Controllers\TimeCorrectionController;
use App\Http\Controllers\EmployeeAttendanceController;
use App\Http\Controllers\AccountingLoanController;
use App\Http\Controllers\EmployeeLeaveRequestController;
use App\Http\Controllers\EmployeeLoanController;
use App\Http\Controllers\NonWorkingDayController;
use App\Http\Controllers\EmployeeOffboardingController;
use App\Http\Controllers\EmployeePayslipController;
use App\Http\Controllers\JobOpeningController;
use App\Http\Controllers\TimeCorrectionReviewController;
use App\Http\Controllers\PayslipController;

use App\Http\Controllers\LeaveRequestController;


use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

        

    Route::patch('/profile', [ProfileController::class, 'updateAccount'])
        ->name('profile.updateAccount');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Authenticated and Verified Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Admin + HR
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Admin|HR')->group(function () {

        // Onboarding Management
        Route::get('/onboarding', [OnboardingManagementController::class, 'index'])
            ->name('onboarding.index');

        Route::patch('/onboarding/{application}/decision', [
            OnboardingManagementController::class,
            'decide',
        ])->name('onboarding.decide');

        Route::delete('/onboarding/{application}', [
            OnboardingManagementController::class,
            'destroy',
        ])->name('onboarding.destroy');

        Route::get('/onboarding/{application}/documents/{type}', [
            OnboardingManagementController::class,
            'document',
        ])->name('onboarding.document');

        Route::post('/onboarding/{application}/schedule', [OnboardingManagementController::class, 'schedule'])->name('onboarding.schedule');


        Route::post('/job-openings', [JobOpeningController::class, 'store'])->name('job-openings.store');
        Route::delete('/job-openings.store/{jobOpening}', [JobOpeningController::class, 'destroy'])->name('job-openings.destroy');
 

        // Offboarding Management
        Route::get('/offboarding', [OffboardingController::class, 'index'])
            ->name('offboarding.index');

        Route::patch('/offboarding/{offboardingCase}/approve', [
            OffboardingController::class,
            'approve',
        ])->name('offboarding.approve');

        Route::get('/offboarding/{offboardingCase}/documents/{document}', [
            OffboardingController::class,
            'document',
        ])->name('offboarding.document')
            ->scopeBindings();

        // Document Management
        Route::get('/document', [DocumentController::class, 'index'])
            ->name('documents.index');

        Route::post('/documents/boxes', [DocumentController::class, 'storeBox'])
            ->name('boxes.store');

        Route::patch('/documents/decision', [DocumentController::class, 'decide'])
            ->name('hr.documents.decide');

        Route::delete('/documents', [DocumentController::class, 'destroy'])
            ->name('destroy');

        Route::get('document-employee/{document}/file', [EmployeeDocumentController::class, 'file'])->name('employee.documents.file');
        
    });

    /*
    |--------------------------------------------------------------------------
    | Employee Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Employee|HR')->group(function () {

        // Employee Attendance
        Route::get('/attendance-employee-monitoring', [
            EmployeeAttendanceController::class,
            'index',
        ])->name('attendance.attendance-employee.index');

        
        // School Employees
        Route::get('/school-employees', [SchoolEmployeeController::class, 'index'])
            ->name('employee.school-employees');

        // Employee Dashboard
        Route::get('/employee-dashboard', [
            EmployeeDashboardController::class,
            'index',
        ])->name('employee-dashboard');

        // Employee Time Corrections
        Route::get('/time-corrections-employee', [
            TimeCorrectionController::class,
            'index',
        ])->name('time-corrections.employee.index');

        Route::post('/time-corrections', [
            TimeCorrectionController::class,
            'store',
        ])->name('time-corrections.store');

        // Employee Leave Requests
        Route::get('/leave-request-employee', [
            LeaveRequestController::class,
            'index',
        ])->name('leaves.employee.index');

        Route::post('/leave-requests', [
            LeaveRequestController::class,
            'store',
        ])->name('leave-requests.store');

        Route::patch('/leave-requests/{leaveRequest}/archive', [
            LeaveRequestController::class,
            'archive',
        ])->name('leave-requests.archive');

        // Employee Financial Loan Requests
        Route::get('/loan-employee', [
            EmployeeLoanController::class,
            'index',
    ])->name('loans.employee.index');

        Route::post('/loan-employee', [
            EmployeeLoanController::class,
            'store',
        ])->name('loan.loan-employee.store');

        Route::post('/loan-employee/archive', [
            EmployeeLoanController::class,
            'archive',
        ])->name('loan.loan-employee.archive');

        Route::post('/loan-employee/{loanRequest}/restore', [
            EmployeeLoanController::class,
            'restore',
        ])->name('loan.loan-employee.restore');

        // Employee Offboarding
        Route::get('/offboarding-employee', [
            EmployeeOffboardingController::class,
            'index',
        ])->name('offboarding.employee.index');

        Route::post('/offboarding-employee', [
            EmployeeOffboardingController::class,
            'store',
        ])->name('employee.offboarding.store');


        // Employee Payslips
        Route::get('/payslip-employee', [
            EmployeePayslipController::class,
            'index',
        ])->name('salary-records.employee.index');

        Route::post('/payslips/{payslip}/respond', [EmployeePayslipController::class, 'respond'])->name('employee.payslips.respond');


        Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
        Route::get('/payslips/{payslip}/download', [PayslipController::class, 'download'])->name('payslips.download');

        // Employee Documents
        Route::get('/document-employee', [
            EmployeeDocumentController::class,
            'index',
        ])->name('documents.employee.index');
    
        Route::get('document-employee/boxes/{box}', [EmployeeDocumentController::class, 'boxDocuments'])->name('documents.box');
        Route::post('document-employee', [EmployeeDocumentController::class, 'store'])->name('employee.documents.store');
        Route::get('document-employee/{document}/file', [EmployeeDocumentController::class, 'file'])->name('employee.documents.file');
        
        // Employee Tasks
        Route::get('/tasks', [
            EmployeeDashboardController::class,
            'tasks',
        ])->name('tasks.index');

        Route::post('/tasks', [
            EmployeeDashboardController::class,
            'storeTask',
        ])->name('tasks.store');

        Route::delete('/tasks/{task}', [
            EmployeeDashboardController::class,
            'destroyTask',
        ])->name('tasks.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin + HR + Employee
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Admin|HR')->group(function () {

        // Employee Management
        Route::resource('employees', EmployeeController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Attendance Monitoring
        Route::get('/attendance-monitoring', [AttendanceController::class, 'index'])
            ->name('attendance.index');


        Route::post('/attendance-monitoring', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::post('/attendance-monitoring/archive', [AttendanceController::class, 'archive'])->name('attendance.archive');
        Route::delete('/attendance-monitoring', [AttendanceController::class, 'destroyMany'])->name('attendance.destroyMany');
    
        // Non-working days management
        Route::get('/non-working-days', [NonWorkingDayController::class, 'index'])->name('non-working-days.index');
        Route::post('/non-working-days', [NonWorkingDayController::class, 'store'])->name('hr.non-working-days.store');
        Route::delete('/non-working-days/{nonWorkingDay}', [NonWorkingDayController::class, 'destroy'])->name('non-working-days.destroy');


        Route::get('/attendance-monitoring/export', [
            AttendanceController::class,
            'export',
        ])->name('attendance.export');

        Route::patch('/attendance-monitoring/archive', [
            AttendanceController::class,
            'archive',
        ])->name('attendance.archive');

        Route::delete('/attendance-monitoring/archive', [
            AttendanceController::class,
            'destroy',
        ])->name('attendance.destroy');

        // Time Correction Management
    

        Route::get('/time-corrections', [TimeCorrectionReviewController::class, 'index'])->name('time-corrections.index');
        Route::patch('/time-corrections/decide', [TimeCorrectionReviewController::class, 'decide'])->name('decide');
        Route::get('/time-corrections/{timeCorrection}/proof', [TimeCorrectionController::class, 'proof'])->name('proof');
  



        // Leave Management
        Route::get('/leaves', [LeaveManagementController::class, 'index'])
            ->name('leaves.index');

        Route::patch('/leaves/{leaveRequest}/status', [
            LeaveManagementController::class,
            'updateStatus',
        ])->name('leave.status');

        Route::patch('/leaves/{leaveRequest}/archive', [
            LeaveManagementController::class,
            'archive',
        ])->name('leave.archive');

        Route::delete('/leaves/archive/clear', [
            LeaveManagementController::class,
            'clearArchive',
        ])->name('leave.archive.clear');



    });



    /*
    |--------------------------------------------------------------------------
    | Admin + Accounting + Employee
    |--------------------------------------------------------------------------
    |
    | Add routes here only if they should be accessible to all three roles.
    |
    *

    /*
    |--------------------------------------------------------------------------
    | Admin + HR + Accounting
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Admin|HR|Accounting')->group(function () {

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');

        // Payroll Approval
        Route::get('/payroll', [PayrollController::class, 'index'])
            ->name('payroll.index');

        Route::patch('/approve', [PayrollController::class, 'approve'])
            ->name('payroll.approve');

        Route::patch('/approve-all', [PayrollController::class, 'approveAll'])
            ->name('payroll.approve-all');

        Route::patch('/reject', [PayrollController::class, 'reject'])
            ->name('payroll.reject');

        // Payroll Processing
        Route::get('/payroll-processing', [
            PayrollProcessingController::class,
            'index',
        ])->name('payroll-accounting.index');

        Route::post('/payroll-processing/save', [
            PayrollProcessingController::class,
            'save',
        ])->name('payroll-accounting.save');

        Route::post('/payroll-processing/steps', [
            PayrollProcessingController::class,
            'saveSteps',
        ])->name('payroll-accounting.steps');

        Route::post('/payroll-processing/transfer', [
            PayrollProcessingController::class,
            'transfer',
        ])->name('payroll-accounting.transfer');

        // Salary Monitoring
        Route::get('/salary-monitoring', [
            SalaryMonitoringController::class,
            'index',
        ])->name('salary-monitoring.index');

        Route::post('/salary-monitoring/send', [
            SalaryMonitoringController::class,
            'send',
        ])->name('salary-monitoring.send');

        // Accounting Loan Management
        Route::get('/financial-loans', [
            AccountingLoanController::class,
            'index',
        ])->name('loans-accounting.index');

        Route::patch('/financial-loans/{loanRequest}/decision', [
            AccountingLoanController::class,
            'decide',
        ])->name('accounting-loans.decide');

        // Salary Records
        Route::get('/salary-records', [
            SalaryRecordController::class,
            'index',
        ])->name('salary-records.index');

        // Loan Management
        Route::get('/loans', [LoanController::class, 'index'])
            ->name('loans.index');

        Route::patch('/loans/{loanRequest}/decision', [
            LoanController::class,
            'decide',
        ])->name('loans.decide');

    Route::patch('/loans/{loanRequest}/archive', [LoanController::class, 'archive'])->name('loans.archive');
    Route::patch('/loans/{loanRequest}/restore', [LoanController::class, 'restore'])->name('loans.restore');

    });

    /*
    |--------------------------------------------------------------------------
    | Applicant Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Applicant')->group(function () {

        Route::get('/applications', [ApplicationController::class, 'index'])
            ->name('applications.index');

        Route::post('/applications', [ApplicationController::class, 'store'])
            ->name('applications.store');
    });
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
