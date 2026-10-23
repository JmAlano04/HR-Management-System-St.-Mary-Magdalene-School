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
use App\Http\Controllers\PayrollProcessingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryRecordController;
use App\Http\Controllers\SalaryMonitoringController;
use App\Http\Controllers\TimeCorrectionController;
use App\Http\Controllers\EmployeeAttendanceController;
use App\Http\Controllers\AccountingLoanController;
use App\Http\Controllers\EmployeeTimeCorrectionsController;
use App\Http\Controllers\EmployeeLeaveRequestController;
use App\Http\Controllers\EmployeeLoanController;
use App\Http\Controllers\EmployeeOffboardingController;
use App\Http\Controllers\EmployeePayslipController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\LeaveRequestController;

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






    // Employee: personal na tasks sa dashboard calendar
    Route::middleware(['auth', 'role:Employee'])->group(function () {


        Route::get('/attendance-employee-monitoring', [EmployeeAttendanceController::class, 'index'])
        ->name('attendance.attendance-employee.index');

        // School Employee
        Route::get('/school-employees', [SchoolEmployeeController::class, 'index'])
        ->name('employee.school-employees');
        
        Route::get ('employee-dashboard', [EmployeeDashboardController::class, 'index'])
        ->name('employee-dashboard');


         // Employee

         
        Route::get('time-corrections-employee', [TimeCorrectionController::class, 'index'])
        ->name('time-corrections.employee.index');

       Route::post('/time-corrections', [TimeCorrectionController::class, 'store'])->name('time-corrections.store');
            

        Route::get('leave-request-employee', [LeaveRequestController::class, 'index'])
        ->name('leaves.employee.index');

            Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
            Route::patch('/leave-requests/{leaveRequest}/archive', [LeaveRequestController::class, 'archive'])->name('leave-requests.archive');

        
        Route::get('loan-employee', [EmployeeLoanController::class, 'index'])
        ->name('loans.employee.index');
        
        Route::post('/loan-employee', [EmployeeLoanController::class, 'store'])
                ->name('loan.loan-employee.store');

            // Archive selected loan requests
        Route::post('/loan-employee/archive', [EmployeeLoanController::class, 'archive'])
                ->name('loan.loan-employee.archive');

            // Restore an archived loan request
        Route::post('/loan-employee/{loanRequest}/restore', [EmployeeLoanController::class, 'restore'])
                ->name('loan.loan-employee.restore');

                
        Route::get('offboarding-employee', [EmployeeOffboardingController::class, 'index'])
        ->name('offboarding.employee.index');
        
        Route::get('payslip-employee', [EmployeePayslipController::class, 'index'])
        ->name('salary-records.employee.index');
        
        Route::get('document-employee', [EmployeeDocumentController::class, 'index'])
        ->name('documents.employee.index');
        
        Route::get('tasks', [EmployeeDashboardController::class, 'tasks'])->name('tasks.index');
        Route::post('tasks', [EmployeeDashboardController::class, 'storeTask'])->name('tasks.store');
        Route::delete('tasks/{task}', [EmployeeDashboardController::class, 'destroyTask'])->name('tasks.destroy');
    });



    // Admin + HR + Employee
    Route::middleware('role:Admin|HR|Employee')->group(function () {

       // Employee module
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


    // Payroll Processing
        Route::get('/payroll-processing', [PayrollProcessingController::class, 'index'])
            ->name('payroll-accounting.index');
        Route::post('/payroll-processing/save', [PayrollProcessingController::class, 'save'])
            ->name('payroll-accounting.save');
        Route::post('/payroll-processing/steps', [PayrollProcessingController::class, 'saveSteps'])
            ->name('payroll-accounting.steps');
        Route::post('/payroll-processing/transfer', [PayrollProcessingController::class, 'transfer'])
            ->name('payroll-accounting.transfer');
    
        // Salary Monitoring
        Route::get('/salary-monitoring', [SalaryMonitoringController::class, 'index'])
            ->name('salary-monitoring.index');
        Route::post('/salary-monitoring/send', [SalaryMonitoringController::class, 'send'])
            ->name('salary-monitoring.send');
        // Loan Accounting
         Route::get('/financial-loans', [AccountingLoanController::class, 'index'])
        ->name('loans-accounting.index');
        Route::patch('/financial-loans/{loanRequest}/decision', [AccountingLoanController::class, 'decide'])
                ->name('accounting-loans.decide');
    
        // Salary Record Management
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