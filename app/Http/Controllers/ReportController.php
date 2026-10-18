<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Reports — summary numbers from every module (read-only, no new tables).
     */
    public function index(): View
    {
        // Count rows of a table with optional conditions; 0 if the table isn't migrated yet
        $count = function (string $table, array $where = []): int {
            if (! Schema::hasTable($table)) {
                return 0;
            }

            return DB::table($table)->where($where)->count();
        };

        // ---------- Employees ----------
        $employees = [
            'Total employees' => $count('employees'),
            'Teaching'        => $count('employees', ['role_type' => 'teaching']),
            'Non-teaching'    => $count('employees', ['role_type' => 'nonteaching']),
        ];

        // ---------- Time corrections ----------
        $timeCorrections = [
            'Pending'  => $count('time_corrections', ['admin_status' => 'Pending']),
            'Approved' => $count('time_corrections', ['admin_status' => 'Approved']),
            'Rejected' => $count('time_corrections', ['admin_status' => 'Rejected']),
        ];

        // ---------- Leave ----------
        $leave = [
            'Pending'  => $count('leave_requests', ['status' => 'pending']),
            'Approved' => $count('leave_requests', ['status' => 'approved']),
            'Rejected' => $count('leave_requests', ['status' => 'rejected']),
        ];

        // ---------- Onboarding (applications) ----------
        $onboarding = [
            'Total applicants' => $count('applications'),
            'Under review'     => $count('applications', ['status' => 'pending']),
            'Approved'         => $count('applications', ['status' => 'qualified']),
            'Rejected'         => $count('applications', ['status' => 'rejected']),
        ];

        // ---------- Financial loans ----------
        $loans = [
            'Awaiting Admin' => $count('loan_requests', [
                'hr_status' => 'approved', 'accounting_status' => 'approved', 'admin_status' => 'pending',
            ]),
            'Approved'       => $count('loan_requests', ['admin_status' => 'approved']),
            'Rejected'       => $count('loan_requests', ['admin_status' => 'rejected']),
        ];

        $approvedLoanTotal = Schema::hasTable('loan_requests')
            ? (float) DB::table('loan_requests')->where('admin_status', 'approved')->sum('amount')
            : 0.0;

        // ---------- Offboarding ----------
        $offboarding = [
            'Awaiting final approval' => $count('offboarding_cases', ['sent_to_admin' => true, 'admin_final_approved' => false]),
            'Final approved'          => $count('offboarding_cases', ['sent_to_admin' => true, 'admin_final_approved' => true]),
        ];

        return view('report', compact(
            'employees',
            'timeCorrections',
            'leave',
            'onboarding',
            'loans',
            'approvedLoanTotal',
            'offboarding',
        ));
    }
}