<?php

namespace App\Services;

<<<<<<< HEAD
use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\SubmissionBox;
=======
use App\Models\Employee;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use App\Models\User;
use App\Notifications\HrmsNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Every notification rule from the spec lives here.
 * Controllers only make a one-line call, e.g. HrmsNotifier::leaveSubmitted($leave);
 *
 * Rules:
 *  - The person who triggered the action is never notified about their own action.
 *  - "Single notification" rules (final approve/reject) are protected by a dedupe key,
 *    so calling the method twice can never send the employee two messages.
 */
class HrmsNotifier
{
    /** Where each notification should link to. EDIT these to match your route names. */
    protected const ROUTES = [
<<<<<<< HEAD
        'applicants' => 'onboarding.index',
        'applicant_employee' => 'applications.index',
        'time' => 'time-corrections.index',
        'time_employee' => 'time-corrections.employee.index',
        'leave' => 'leaves.index',
        'leave_employee' => 'leaves.employee.index',
        'loan' => 'loans.index',
        'loan_accounting' => 'loans-accounting.index',
        'loan_employee' => 'loans.employee.index',
        'payslip' => 'payslips.index',
        'payslip_accounting' => 'salary-monitoring.index',
        'payslip_employee' => 'salary-records.employee.index',
        'documents' => 'documents.index',
        'documents_employee' => 'documents.employee.index',
        'offboarding' => 'offboarding.index',
        'offboarding_employee' => 'offboarding.employee.index',
=======
        'applicants'  => 'applicants.index',
        'time'        => 'time-corrections.index',
        'leave'       => 'leave-requests.index',
        'loan'        => 'loan-requests.index',
        'payslip'     => 'payslips.index',
        'documents'   => 'documents.index',
        'offboarding' => 'offboarding.index',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    ];

    /* =====================================================================
     |  1. NEW APPLICANT  ->  HR, Admin
     * ===================================================================*/
    public static function newApplicant(Model $application): void
    {
<<<<<<< HEAD
        $documents = collect([
            $application->resume_original_name,
            $application->coverletter_original_name,
            $application->appletter_original_name,
        ])->filter()->implode(', ');

        static::send(
            static::usersWithRole(['HR', 'Admin']),
            'New applicant',
            implode("\n", [
                "Reference: APP-{$application->id}",
                "Applicant: {$application->fullname}",
                'Submitted on: '.($application->created_at?->format('M j, Y g:i A') ?? 'Date unavailable'),
                "Position: {$application->position} ({$application->job_type})",
                "Email: {$application->email}",
                "Phone: {$application->phone}",
                "Address: {$application->home_address}",
                'Birth date: '.($application->birth_date?->format('M j, Y') ?? 'Not provided'),
                "Gender: {$application->gender}",
                "Civil status: {$application->civil_status}",
                "Education: {$application->education}",
                'Submitted documents: '.$documents,
            ]),
=======
        static::send(
            static::usersWithRole(['HR', 'Admin']),
            'New applicant',
            "{$application->fullname} applied for {$application->position} ({$application->job_type}). "
            . "Email: {$application->email}, phone: {$application->phone}.",
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'applicants',
            'info',
            "applicant:{$application->id}:new"
        );
    }

<<<<<<< HEAD
    public static function applicantInterviewScheduled(Model $application, bool $rescheduled = false): void
    {
        $date = $application->interview_date?->format('M j, Y') ?? 'date unavailable';
        $schedule = implode('|', [
            (string) $application->interview_date,
            (string) $application->interview_time,
            (string) $application->interview_location,
        ]);

        static::send(
            $application->user,
            $rescheduled ? 'Applicant interview rescheduled' : 'Applicant interview scheduled',
            "Your interview for application {$application->code} is ".($rescheduled ? 'rescheduled' : 'scheduled')
                ." for {$date} at ".($application->interview_time ?: 'time unavailable')
                .($application->interview_location ? " at {$application->interview_location}" : '')
                .'.'.($application->interview_note ? " Note: {$application->interview_note}" : ''),
            'applicant_employee',
            'info',
            "applicant:{$application->id}:interview:".sha1($schedule)
        );
    }

    public static function applicantInterviewCompleted(Model $application): void
    {
        static::send(
            $application->user,
            'Applicant interview completed',
            "Your interview for application {$application->code} was marked complete by HR on ".now()->format('M j, Y g:i A').'.',
            'applicant_employee',
            'success',
            "applicant:{$application->id}:interview_completed"
        );
    }

    public static function applicantHrDecided(Model $application): void
    {
        $approved = $application->status === 'hr_approved';
        if (! $approved && $application->status !== 'hr_rejected') {
            return;
        }

        static::send(
            static::usersWithRole(['Admin']),
            $approved ? 'HR approved an applicant' : 'HR rejected an applicant',
            'HR '.($approved ? 'approved' : 'rejected')." application {$application->code} for {$application->fullname} ({$application->position}) on ".now()->format('M j, Y g:i A').'.'
                .(! $approved && $application->rejection_reason ? " Reason: {$application->rejection_reason}" : ''),
            'applicants',
            $approved ? 'success' : 'danger',
            "applicant:{$application->id}:hr_decision"
        );

        if (! $approved) {
            static::applicantFinal($application, false, 'HR');
        }
    }

    public static function applicantAdminDecided(Model $application): void
    {
        if (! in_array($application->status, Application::APPROVED_STATUSES, true)
            && ! in_array($application->status, Application::REJECTED_STATUSES, true)) {
            return;
        }

        $approved = in_array($application->status, Application::APPROVED_STATUSES, true);
        static::applicantFinal($application, $approved, 'Admin');
    }

    protected static function applicantFinal(Model $application, bool $approved, string $reviewer): void
    {
        static::send(
            $application->user,
            $approved ? 'Application approved' : 'Application rejected',
            "Application {$application->code} for {$application->fullname} was ".($approved ? 'approved' : "rejected by {$reviewer}").' on '.now()->format('M j, Y g:i A').'.'
                .(! $approved && $application->rejection_reason ? " Reason: {$application->rejection_reason}" : ''),
            'applicant_employee',
            $approved ? 'success' : 'danger',
            "applicant:{$application->id}:final"
        );
    }

=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    /* =====================================================================
     |  2. TIME CORRECTION
     * ===================================================================*/

    /** Employee submitted -> HR, Admin */
    public static function timeCorrectionSubmitted(Model $tc): void
    {
        $name = static::employeeName($tc->employee_id);

        static::send(
            static::usersWithRole(['HR', 'Admin']),
            'Time correction needs review',
<<<<<<< HEAD
            "Reference: TC-{$tc->id}. {$name} requested a {$tc->correction_type} correction for {$tc->correction_date} on ".($tc->created_at?->format('M j, Y g:i A') ?? 'an unknown date').". "
                ."Incorrect time: {$tc->incorrect_time}; requested time: {$tc->correct_time}. "
                .($tc->note ? "Note: {$tc->note}. " : '')
                .'Please review and approve.',
=======
            "{$name} requested a time correction for {$tc->correction_date} ({$tc->correction_type}). Please review and approve.",
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'time',
            'warning',
            "time_correction:{$tc->id}:submitted"
        );
    }

    /**
     * Call after HR or Admin changes their status. It stays silent until the request
     * is final: both approved, or either one rejected. Employee gets exactly one message.
     */
<<<<<<< HEAD
    public static function timeCorrectionReviewed(Model $tc, string $reviewer, ?string $reason = null): void
    {
        $admin = strtolower((string) $tc->admin_status);
        $hr = strtolower((string) $tc->hr_status);
        $reviewer = strtolower($reviewer);
        $reviewedStatus = $reviewer === 'hr' ? $hr : $admin;

        if (in_array($reviewer, ['hr', 'admin'], true) && in_array($reviewedStatus, ['approved', 'rejected'], true)) {
            $recipientRole = $reviewer === 'hr' ? 'Admin' : 'HR';
            $reviewedBy = ucfirst($reviewer);
            $reason ??= $tc->rejection_reason ?? null;

            static::send(
                static::usersWithRole([$recipientRole]),
                "{$reviewedBy} {$reviewedStatus} a time correction",
                "{$reviewedBy} {$reviewedStatus} time correction TC-{$tc->id} for {$tc->correction_date} on ".now()->format('M j, Y g:i A')."."
                    .($reviewedStatus === 'rejected' && $reason ? " Reason: {$reason}" : ''),
                'time',
                $reviewedStatus === 'approved' ? 'success' : 'danger',
                "time_correction:{$tc->id}:{$reviewer}_decision"
            );
        }
=======
    public static function timeCorrectionReviewed(Model $tc, ?string $reason = null): void
    {
        $admin = strtolower((string) $tc->admin_status);
        $hr    = strtolower((string) $tc->hr_status);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        if ($admin === 'approved' && $hr === 'approved') {
            $approved = true;
        } elseif ($admin === 'rejected' || $hr === 'rejected') {
            $approved = false;
        } else {
            return; // still waiting on the other reviewer
        }

        $reason ??= $tc->rejection_reason ?? null;

        static::send(
            static::employeeUser($tc->employee_id),
            $approved ? 'Time correction approved' : 'Time correction rejected',
            $approved
<<<<<<< HEAD
                ? "Time correction TC-{$tc->id} for {$tc->correction_date} was approved by HR and Admin."
                : "Time correction TC-{$tc->id} for {$tc->correction_date} was rejected.".($reason ? " Reason: {$reason}" : ''),
            'time_employee',
=======
                ? "Your time correction request for {$tc->correction_date} was approved."
                : "Your time correction request for {$tc->correction_date} was rejected." . ($reason ? " Reason: {$reason}" : ''),
            'time',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            $approved ? 'success' : 'danger',
            "time_correction:{$tc->id}:final"
        );
    }

    /* =====================================================================
     |  3. LEAVE REQUEST
     * ===================================================================*/

    /** Employee submitted -> HR, Admin */
    public static function leaveSubmitted(Model $leave): void
    {
        static::send(
            static::usersWithRole(['HR', 'Admin']),
            'Leave request needs review',
<<<<<<< HEAD
            "Reference: LEAVE-{$leave->id}. {$leave->employee_name} requested {$leave->type} from {$leave->start_date} to {$leave->end_date}. "
                ."Reason: {$leave->reason}. Please review and approve.",
=======
            "{$leave->employee_name} requested {$leave->type} from {$leave->start_date} to {$leave->end_date}. Please review and approve.",
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'leave',
            'warning',
            "leave:{$leave->id}:submitted"
        );
    }

<<<<<<< HEAD
    /** HR decision -> Admin */
    public static function leaveHrReviewed(Model $leave, string $decision, ?string $reason = null): void
    {
        $approved = strtolower($decision) === 'approved';

        static::send(
            static::usersWithRole(['Admin']),
            $approved ? 'HR approved a leave request' : 'HR rejected a leave request',
            $approved
                ? "HR approved leave request LEAVE-{$leave->id} for {$leave->employee_name}'s {$leave->type} from {$leave->start_date} to {$leave->end_date}."
                : "HR rejected leave request LEAVE-{$leave->id} for {$leave->employee_name}'s {$leave->type} from {$leave->start_date} to {$leave->end_date}. "
                    .'The request was automatically rejected for Admin.'
                    .($reason ? " Reason: {$reason}" : ''),
            'leave',
            $approved ? 'success' : 'danger',
            "leave:{$leave->id}:hr_decision"
        );
    }

=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    /** Call once the leave reaches its final status (approved or rejected). Employee gets one message. */
    public static function leaveDecided(Model $leave, ?string $reason = null): void
    {
        $status = strtolower((string) $leave->status);
        if (! in_array($status, ['approved', 'rejected'], true) || ! $leave->user_id) {
            return;
        }

        $approved = $status === 'approved';
        $reason ??= $leave->rejection_reason ?? null;
<<<<<<< HEAD
        if ($approved && (
            strtolower((string) $leave->hr_status) !== 'approved'
            || strtolower((string) $leave->admin_status) !== 'approved'
        )) {
            return;
        }
        if (! $approved && blank($reason)) {
            return;
        }
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        static::send(
            User::find($leave->user_id),
            $approved ? 'Leave request approved' : 'Leave request rejected',
            $approved
<<<<<<< HEAD
                ? "Your leave request LEAVE-{$leave->id} ({$leave->type}, {$leave->start_date} to {$leave->end_date}) was approved."
                : "Your leave request LEAVE-{$leave->id} ({$leave->type}, {$leave->start_date} to {$leave->end_date}) was rejected.".($reason ? " Reason: {$reason}" : ''),
            'leave_employee',
=======
                ? "Your {$leave->type} from {$leave->start_date} to {$leave->end_date} was approved."
                : "Your {$leave->type} from {$leave->start_date} to {$leave->end_date} was rejected." . ($reason ? " Reason: {$reason}" : ''),
            'leave',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            $approved ? 'success' : 'danger',
            "leave:{$leave->id}:final"
        );
    }

    /* =====================================================================
<<<<<<< HEAD
     |  4. FINANCIAL LOAN   (HR -> Accounting -> Admin)
=======
     |  4. FINANCIAL LOAN   (HR is the only approver)
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
     * ===================================================================*/

    /** Employee submitted -> HR, Accounting, Admin */
    public static function loanSubmitted(Model $loan): void
    {
<<<<<<< HEAD
        $name = static::employeeName($loan->employee_id);
        $amount = number_format((float) $loan->amount, 2);

        $message = "Reference: LOAN-{$loan->id}. {$name} requested a {$loan->loan_type} of ₱{$amount} on ".($loan->created_at?->format('M j, Y g:i A') ?? 'an unknown date').". Purpose: {$loan->reason}";
        $dedupe = "loan:{$loan->id}:submitted";

        static::send(static::usersWithRole(['HR', 'Admin']), 'New loan request', $message, 'loan', 'info', $dedupe);
        static::send(static::usersWithRole(['Accounting']), 'New loan request', $message, 'loan_accounting', 'info', $dedupe);
    }

    /** HR's decision opens Accounting or ends the process if rejected. */
=======
        $name   = static::employeeName($loan->employee_id);
        $amount = number_format((float) $loan->amount, 2);

        static::send(
            static::usersWithRole(['HR', 'Accounting', 'Admin']),
            'New loan request',
            "{$name} requested a {$loan->loan_type} of ₱{$amount}.",
            'loan',
            'info',
            "loan:{$loan->id}:submitted"
        );
    }

    /**
     * HR approved/rejected -> Accounting + Admin are informed,
     * and the employee gets the single final notification.
     */
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    public static function loanHrDecided(Model $loan): void
    {
        $status = strtolower((string) $loan->hr_status);
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return;
        }

        $approved = $status === 'approved';
<<<<<<< HEAD
        $name = static::employeeName($loan->employee_id);
        $reason = $loan->hr_rejection_reason;

        $title = 'HR '.($approved ? 'approved' : 'rejected').' a loan request';
        $message = "HR {$status} loan request LOAN-{$loan->id} for {$name} ({$loan->loan_type}) on ".now()->format('M j, Y g:i A').'.'.(! $approved && $reason ? " Reason: {$reason}" : '');
        $type = $approved ? 'success' : 'danger';
        $dedupe = "loan:{$loan->id}:hr_decision";

        static::send(static::usersWithRole(['Admin']), $title, $message, 'loan', $type, $dedupe);
        static::send(static::usersWithRole(['Accounting']), $title, $message, 'loan_accounting', $type, $dedupe);

        if (! $approved) {
            static::loanFinal($loan, false, $reason);
        }
    }

    /** Accounting's decision opens Admin or ends the process if rejected. */
=======
        $name     = static::employeeName($loan->employee_id);
        $reason   = $loan->hr_rejection_reason;

        static::send(
            static::usersWithRole(['Accounting', 'Admin']),
            'HR ' . ($approved ? 'approved' : 'rejected') . ' a loan request',
            "HR {$status} {$name}'s {$loan->loan_type} request." . (! $approved && $reason ? " Reason: {$reason}" : ''),
            'loan',
            $approved ? 'success' : 'danger',
            "loan:{$loan->id}:hr_decision"
        );

        static::loanFinal($loan, $approved, $reason);
    }

    /** Accounting decision (only if your workflow still records one) -> Admin */
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    public static function loanAccountingDecided(Model $loan): void
    {
        $status = strtolower((string) $loan->accounting_status);
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return;
        }

<<<<<<< HEAD
        $approved = $status === 'approved';
        $name = static::employeeName($loan->employee_id);
        $reason = $loan->accounting_rejection_reason;

        static::send(
            static::usersWithRole(['Admin']),
            'Accounting '.($approved ? 'approved' : 'rejected').' a loan request',
            "Accounting {$status} loan request LOAN-{$loan->id} for {$name} ({$loan->loan_type}) on ".now()->format('M j, Y g:i A').'.'.(! $approved && $reason ? " Reason: {$reason}" : ''),
            'loan',
            $approved ? 'success' : 'danger',
            "loan:{$loan->id}:accounting_decision"
        );

        if (! $approved) {
            static::loanFinal($loan, false, $reason);
        }
    }

    /** Admin's decision is final and notifies the employee once. */
    public static function loanAdminDecided(Model $loan): void
    {
        $status = strtolower((string) $loan->admin_status);
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return;
        }

        static::loanFinal($loan, $status === 'approved', $loan->admin_rejection_reason);
=======
        $name = static::employeeName($loan->employee_id);

        static::send(
            static::usersWithRole(['Admin']),
            'Accounting ' . $status . ' a loan request',
            "Accounting {$status} {$name}'s {$loan->loan_type} request."
            . ($status === 'rejected' && $loan->accounting_rejection_reason ? " Reason: {$loan->accounting_rejection_reason}" : ''),
            'loan',
            $status === 'approved' ? 'success' : 'danger',
            "loan:{$loan->id}:accounting_decision"
        );
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    protected static function loanFinal(Model $loan, bool $approved, ?string $reason): void
    {
        $amount = number_format((float) $loan->amount, 2);

        static::send(
            static::employeeUser($loan->employee_id),
            $approved ? 'Loan request approved' : 'Loan request rejected',
            $approved
<<<<<<< HEAD
                ? "Your {$loan->loan_type} request LOAN-{$loan->id} of ₱{$amount} was approved."
                : "Your {$loan->loan_type} request LOAN-{$loan->id} of ₱{$amount} was rejected.".($reason ? " Reason: {$reason}" : ''),
            'loan_employee',
=======
                ? "Your {$loan->loan_type} request of ₱{$amount} was approved."
                : "Your {$loan->loan_type} request of ₱{$amount} was rejected." . ($reason ? " Reason: {$reason}" : ''),
            'loan',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            $approved ? 'success' : 'danger',
            "loan:{$loan->id}:final"
        );
    }

    /* =====================================================================
     |  5. PAYROLL / PAYSLIP
     * ===================================================================*/

    /** Accounting submits for evaluation -> HR, Admin */
    public static function payslipSubmitted(Model $payslip): void
    {
        $name = static::employeeName($payslip->employee_id);
<<<<<<< HEAD
        $payPeriod = static::payslipPayPeriod($payslip);
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        static::send(
            static::usersWithRole(['HR', 'Admin']),
            'Payslip needs review',
<<<<<<< HEAD
            "Reference: PAYSLIP-{$payslip->id}. Accounting submitted {$name}'s {$payPeriod} payslip for approval on ".now()->format('M j, Y g:i A').". Transfer date: {$payslip->transfer_date}.",
            'payslip',
            'warning',
            "payslip:{$payslip->id}:".static::payslipCycle($payslip).':submitted'
=======
            "Accounting submitted {$name}'s {$payslip->cutoff} cutoff payslip for approval.",
            'payslip',
            'warning',
            "payslip:{$payslip->id}:submitted"
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        );
    }

    /** HR approved/rejected -> Accounting, Admin (then checks if ready for release) */
    public static function payslipHrDecided(Model $payslip): void
    {
        static::payslipDecision($payslip, 'HR', $payslip->hr_status, $payslip->hr_rejection_reason, ['Accounting', 'Admin']);
        static::payslipReadyCheck($payslip);
    }

<<<<<<< HEAD
    /** Admin approved/rejected -> Accounting (then checks if ready for release) */
    public static function payslipAdminDecided(Model $payslip): void
    {
        static::payslipDecision($payslip, 'Admin', $payslip->admin_status, $payslip->admin_rejection_reason, ['Accounting']);
=======
    /** Admin approved/rejected -> Accounting, HR (then checks if ready for release) */
    public static function payslipAdminDecided(Model $payslip): void
    {
        static::payslipDecision($payslip, 'Admin', $payslip->admin_status, $payslip->admin_rejection_reason, ['Accounting', 'HR']);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        static::payslipReadyCheck($payslip);
    }

    protected static function payslipDecision(Model $payslip, string $by, ?string $status, ?string $reason, array $notify): void
    {
        $status = strtolower((string) $status);
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return;
        }

        $name = static::employeeName($payslip->employee_id);
<<<<<<< HEAD
        $payPeriod = static::payslipPayPeriod($payslip);
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        static::send(
            static::usersWithRole($notify),
            "{$by} {$status} a payslip",
<<<<<<< HEAD
            "{$by} {$status} payslip PAYSLIP-{$payslip->id} for {$name} ({$payPeriod}) on ".now()->format('M j, Y g:i A').". Transfer date: {$payslip->transfer_date}.".($status === 'rejected' && $reason ? " Reason: {$reason}" : ''),
            'payslip',
            $status === 'approved' ? 'success' : 'danger',
            "payslip:{$payslip->id}:".static::payslipCycle($payslip).':'.strtolower($by).'_decision'
=======
            "{$by} {$status} {$name}'s {$payslip->cutoff} cutoff payslip." . ($status === 'rejected' && $reason ? " Reason: {$reason}" : ''),
            'payslip',
            $status === 'approved' ? 'success' : 'danger',
            "payslip:{$payslip->id}:" . strtolower($by) . '_decision'
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        );
    }

    /** Both HR and Admin approved -> Accounting can distribute */
    protected static function payslipReadyCheck(Model $payslip): void
    {
        if (strtolower((string) $payslip->hr_status) !== 'approved'
            || strtolower((string) $payslip->admin_status) !== 'approved') {
            return;
        }

        $name = static::employeeName($payslip->employee_id);
<<<<<<< HEAD
        $payPeriod = static::payslipPayPeriod($payslip);
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        static::send(
            static::usersWithRole(['Accounting']),
            'Payslip ready for distribution',
<<<<<<< HEAD
            "Payslip PAYSLIP-{$payslip->id} for {$name} ({$payPeriod}, transfer date {$payslip->transfer_date}) is approved by HR and Admin and ready to send to the employee.",
            'payslip_accounting',
            'success',
            "payslip:{$payslip->id}:".static::payslipCycle($payslip).':ready'
        );
    }

    protected static function payslipCycle(Model $payslip): string
    {
        return $payslip->sent_to_admin_at?->format('YmdHis.u') ?? 'unsubmitted';
    }

    protected static function payslipPayPeriod(Model $payslip): string
    {
        return match (strtolower(trim((string) $payslip->cutoff))) {
            '15th' => '15th Pay Period',
            '30th' => '30th Pay Period',
            default => ucfirst((string) $payslip->cutoff).' Pay Period',
        };
    }

    /** Accounting sent it to the employee -> Employee */
    public static function payslipReleased(Model $payslip): void
    {
        $payPeriod = static::payslipPayPeriod($payslip);

        static::send(
            static::employeeUser($payslip->employee_id),
            'Your payslip is available',
            "Your {$payPeriod} payslip PAYSLIP-{$payslip->id} (transfer date {$payslip->transfer_date}) is ready. Please review and approve or reject it.",
            'payslip_employee',
=======
            "{$name}'s {$payslip->cutoff} cutoff payslip is approved by HR and Admin and ready to send to the employee.",
            'payslip',
            'success',
            "payslip:{$payslip->id}:ready"
        );
    }

    /** Accounting sent it to the employee -> Employee */
    public static function payslipReleased(Model $payslip): void
    {
        static::send(
            static::employeeUser($payslip->employee_id),
            'Your payslip is available',
            "Your {$payslip->cutoff} cutoff payslip is ready. Please review and approve or reject it.",
            'payslip',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'info',
            "payslip:{$payslip->id}:released"
        );
    }

    /** Employee approved/rejected their payslip -> Accounting. $response = PayslipResponse row */
    public static function payslipEmployeeResponded(Model $response): void
    {
        $status = strtolower((string) $response->status);
<<<<<<< HEAD
        $name = static::employeeName($response->employee_id);
=======
        $name   = static::employeeName($response->employee_id);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        static::send(
            static::usersWithRole(['Accounting']),
            "Employee {$status} a payslip",
<<<<<<< HEAD
            "{$name} {$status} payslip PAYSLIP-{$response->payslip_id} on ".now()->format('M j, Y g:i A').'.'.($status === 'rejected' && $response->reason ? " Reason: {$response->reason}" : ''),
=======
            "{$name} {$status} their payslip." . ($status === 'rejected' && $response->reason ? " Reason: {$response->reason}" : ''),
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'payslip',
            $status === 'approved' ? 'success' : 'danger',
            "payslip_response:{$response->id}"
        );
    }

    /* =====================================================================
     |  6. DOCUMENT SUBMISSION
     * ===================================================================*/

    /** HR created a Submission Box and assigned it -> Employee */
    public static function documentsRequested(Model $box, int $employeeId): void
    {
        $deadline = $box->deadline ? " Deadline: {$box->deadline}." : '';

        static::send(
            static::employeeUser($employeeId),
            'Documents requested',
<<<<<<< HEAD
            "Submission box DOCBOX-{$box->id}: HR requested documents for \"{$box->name}\".{$deadline}",
            'documents_employee',
=======
            "HR requested documents for \"{$box->name}\".{$deadline}",
            'documents',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'warning',
            "box:{$box->id}:employee:{$employeeId}:requested"
        );
    }

    /**
     * Employee submitted documents -> HR.
     * $documentNames: array of file/document names just submitted.
     */
<<<<<<< HEAD
    public static function documentsSubmitted(
        int $employeeId,
        array $documentNames,
        string $status = 'Submitted',
        ?int $boxId = null,
        ?int $submissionId = null,
        ?string $submittedAt = null
    ): void {
        $name = static::employeeName($employeeId);
        $date = $submittedAt ?? now()->format('M d, Y h:i A');
        $list = implode(', ', $documentNames) ?: 'documents';
        $boxName = $boxId ? SubmissionBox::whereKey($boxId)->value('name') : null;
        $boxDescription = $boxName ? " for \"{$boxName}\"" : '';
        $reference = $submissionId ? "DOC-{$submissionId}" : ($boxId ? "DOCBOX-{$boxId}" : 'a submission box');
=======
    public static function documentsSubmitted(int $employeeId, array $documentNames, string $status = 'Submitted', ?int $boxId = null): void
    {
        $name = static::employeeName($employeeId);
        $date = now()->format('M d, Y h:i A');
        $list = implode(', ', $documentNames) ?: 'documents';
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        static::send(
            static::usersWithRole(['HR']),
            'Documents submitted',
<<<<<<< HEAD
            "Employee document {$reference}: {$name} submitted{$boxDescription}: {$list}. Submitted on {$date}. Status: {$status}.",
            'documents',
            'success',
            $submissionId ? "employee_document:{$submissionId}:submitted" : null
        );
    }

    public static function documentsReviewed(Model $document, string $decision, ?string $reviewer = null): void
    {
        $approved = $decision === EmployeeDocument::APPROVED;
        $reviewer ??= Auth::user()?->name ?? 'HR';

        static::send(
            static::employeeUser((int) $document->employee_id),
            $approved ? 'Submitted document approved' : 'Submitted document rejected',
            "{$reviewer} {$decision} document DOC-{$document->id} \"{$document->name}\""
                ." for submission box DOCBOX-{$document->submission_box_id} on ".now()->format('M j, Y g:i A').'.',
            'documents_employee',
            $approved ? 'success' : 'danger',
            "employee_document:{$document->id}:review:".strtolower($decision)
=======
            "{$name} submitted: {$list}. Submitted on {$date}. Status: {$status}.",
            'documents',
            'success'
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        );
    }

    /* =====================================================================
     |  7. OFFBOARDING
     * ===================================================================*/

    /** Employee submitted an offboarding request -> HR */
    public static function offboardingSubmitted(Model $case): void
    {
        $name = static::employeeName($case->employee_id);

        static::send(
            static::usersWithRole(['HR']),
            'Offboarding request submitted',
<<<<<<< HEAD
            "Reference: OFFBOARD-{$case->id}. {$name} submitted an offboarding request (last working day: {$case->last_working_day}). Reason: {$case->reason}.",
=======
            "{$name} submitted an offboarding request (last working day: {$case->last_working_day}). Reason: {$case->reason}.",
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'offboarding',
            'warning',
            "offboarding:{$case->id}:submitted"
        );
    }

    /** Employee uploaded a requirement -> HR (every time) */
    public static function offboardingRequirementSubmitted(Model $document): void
    {
        $case = DB::table('offboarding_cases')->find($document->offboarding_case_id);
        $name = $case ? static::employeeName($case->employee_id) : 'An employee';
<<<<<<< HEAD
        $reference = $case ? "OFFBOARD-{$case->id}" : "OFFBOARD-DOC-{$document->id}";
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        static::send(
            static::usersWithRole(['HR']),
            'Offboarding requirement submitted',
<<<<<<< HEAD
            "{$name} submitted \"{$document->document_label}\" for {$reference} on {$document->created_at}. It is ready for HR review.",
            'offboarding',
            'info',
            "offboarding_document:{$document->id}:upload:".sha1((string) $document->file_path)
        );
    }

    public static function offboardingRequirementsSent(Model $case): void
    {
        static::send(
            static::employeeUser($case->employee_id),
            'Offboarding requirements are ready',
            "HR sent your document requirements for OFFBOARD-{$case->id}. Open your offboarding tracker to review the list and upload your documents.",
            'offboarding_employee',
            'info',
            "offboarding:{$case->id}:requirements_sent"
        );
    }

    public static function offboardingInterviewCompleted(Model $case, string $label): void
    {
        static::send(
            static::employeeUser($case->employee_id),
            'Offboarding interview clearance completed',
            "HR marked \"{$label}\" as done for offboarding request OFFBOARD-{$case->id} on ".now()->format('M j, Y g:i A').'.',
            'offboarding_employee',
            'success',
            "offboarding:{$case->id}:interview:".sha1($label).':completed'
        );
    }

    public static function offboardingDocumentReviewed(Model $document, bool $approved): void
    {
        $case = $document->offboardingCase;

        static::send(
            static::employeeUser($case->employee_id),
            $approved ? 'Offboarding document approved' : 'Offboarding document needs replacement',
            $approved
                ? "HR approved \"{$document->document_label}\" for OFFBOARD-{$case->id}."
                : "HR rejected \"{$document->document_label}\" for OFFBOARD-{$case->id}.".($document->rejection_reason ? " Reason: {$document->rejection_reason}" : ''),
            'offboarding_employee',
            $approved ? 'success' : 'danger',
            "offboarding_document:{$document->id}:review:".sha1(implode('|', [
                (string) $document->review_status,
                (string) $document->reviewed_at,
                (string) $document->rejection_reason,
            ]))
=======
            "{$name} submitted \"{$document->document_label}\" and it is ready for review.",
            'offboarding',
            'info'
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        );
    }

    /** HR forwarded the case for final approval -> Admin */
    public static function offboardingSentToAdmin(Model $case): void
    {
        $name = static::employeeName($case->employee_id);

        static::send(
            static::usersWithRole(['Admin']),
            'Offboarding needs final approval',
<<<<<<< HEAD
            "Reference: OFFBOARD-{$case->id}. HR forwarded {$name}'s offboarding request for final review.",
=======
            "HR forwarded {$name}'s offboarding request for your final review.",
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'offboarding',
            'warning',
            "offboarding:{$case->id}:sent_to_admin"
        );
    }

    /** HR rejected the offboarding request (HR stage) -> Employee, once */
    public static function offboardingHrRejected(Model $case, ?string $reason = null): void
    {
        static::offboardingRejected($case, 'HR', $reason);
    }

    /**
     * Admin made the final decision -> HR.
     * If rejected, the employee also gets the single rejection notice.
     */
    public static function offboardingAdminDecided(Model $case, bool $approved, ?string $reason = null): void
    {
        $name = static::employeeName($case->employee_id);

        static::send(
            static::usersWithRole(['HR']),
<<<<<<< HEAD
            'Admin '.($approved ? 'approved' : 'rejected').' an offboarding request',
            "Admin ".($approved ? 'approved' : 'rejected')." offboarding request OFFBOARD-{$case->id} for {$name}.".(! $approved && $reason ? " Reason: {$reason}" : ''),
=======
            'Admin ' . ($approved ? 'approved' : 'rejected') . ' an offboarding request',
            "Admin " . ($approved ? 'approved' : 'rejected') . " {$name}'s offboarding request." . (! $approved && $reason ? " Reason: {$reason}" : ''),
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'offboarding',
            $approved ? 'success' : 'danger',
            "offboarding:{$case->id}:admin_decision"
        );

        if ($approved) {
            static::offboardingCompletedIfReady($case);
        } else {
            static::offboardingRejected($case, 'Admin', $reason);
        }
    }

    /** Rejected at ANY stage -> employee gets exactly one notice (dedupe key is stage-independent) */
    protected static function offboardingRejected(Model $case, string $by, ?string $reason): void
    {
        $reason ??= $case->rejection_reason ?? null;

        static::send(
            static::employeeUser($case->employee_id),
            'Offboarding request rejected',
<<<<<<< HEAD
            "Offboarding request OFFBOARD-{$case->id} was rejected by {$by}.".($reason ? " Reason: {$reason}" : ''),
            'offboarding_employee',
=======
            "Your offboarding request was rejected by {$by}." . ($reason ? " Reason: {$reason}" : ''),
            'offboarding',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'danger',
            "offboarding:{$case->id}:rejected"
        );
    }

    /**
     * Employee is told ONLY when the admin approved AND every document is approved.
     * Safe to call after any HR document review too.
     */
    public static function offboardingCompletedIfReady(Model $case): void
    {
        if (! $case->admin_final_approved) {
            return;
        }

        $docs = DB::table('offboarding_documents')->where('offboarding_case_id', $case->id);

<<<<<<< HEAD
        $total = (clone $docs)->count();
=======
        $total       = (clone $docs)->count();
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $notApproved = (clone $docs)->whereRaw("LOWER(review_status) <> 'approved'")->count();

        if ($total === 0 || $notApproved > 0) {
            return;
        }

        static::send(
            static::employeeUser($case->employee_id),
            'Offboarding completed',
<<<<<<< HEAD
            "Offboarding request OFFBOARD-{$case->id} has final approval and all required documents are approved. The process is complete.",
            'offboarding_employee',
=======
            'Your offboarding documents are approved and your offboarding request is fully approved. The process is complete.',
            'offboarding',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'success',
            "offboarding:{$case->id}:completed"
        );
    }

    /* =====================================================================
     |  Helpers
     * ===================================================================*/

    protected static function usersWithRole(array $roles)
    {
        return User::role($roles)->get();
    }

    protected static function employeeUser(?int $employeeId): ?User
    {
        $userId = Employee::whereKey($employeeId)->value('user_id');

        return $userId ? User::find($userId) : null;
    }

    protected static function employeeName(?int $employeeId): string
    {
        return Employee::whereKey($employeeId)->value('name') ?? 'An employee';
    }

    protected static function link(?string $key): ?string
    {
        $name = static::ROUTES[$key] ?? null;

        return $name && Route::has($name) ? route($name) : null;
    }

    /**
     * Send to one user or many. Skips the acting user and, when a dedupe key
     * is given, skips anyone who already received that exact notification.
     */
    protected static function send($recipients, string $title, string $message, ?string $routeKey = null, string $type = 'info', ?string $dedupe = null): void
    {
        if ($recipients instanceof Model) {
            $recipients = [$recipients];
        }

        $actorId = Auth::id();
<<<<<<< HEAD
        $from = Auth::user()?->name ?? 'System';
        $url = static::link($routeKey);
=======
        $url     = static::link($routeKey);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        collect($recipients)
            ->filter()
            ->unique('id')
            ->reject(fn ($user) => $actorId && $user->id === $actorId)
<<<<<<< HEAD
            ->each(function ($user) use ($title, $message, $url, $type, $dedupe, $from) {
                if ($dedupe && $user->notifications()
                    ->where('data->dedupe_key', $dedupe)
                    ->exists()) {
                    return;
                }

                $user->notify(new HrmsNotification($title, $message, $url, $type, $dedupe, $from));
            });
    }
}
=======
            ->each(function ($user) use ($title, $message, $url, $type, $dedupe) {
                if ($dedupe && $user->notifications()
                        ->where('data', 'like', '%"dedupe_key":"' . $dedupe . '"%')
                        ->exists()) {
                    return;
                }

                $user->notify(new HrmsNotification($title, $message, $url, $type, $dedupe));
            });
    }
}
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
