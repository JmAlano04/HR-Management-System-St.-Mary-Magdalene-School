<?php

namespace App\Services;

use App\Models\Employee;
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
        'applicants'  => 'applicants.index',
        'time'        => 'time-corrections.index',
        'leave'       => 'leave-requests.index',
        'loan'        => 'loan-requests.index',
        'payslip'     => 'payslips.index',
        'documents'   => 'documents.index',
        'offboarding' => 'offboarding.index',
    ];

    /* =====================================================================
     |  1. NEW APPLICANT  ->  HR, Admin
     * ===================================================================*/
    public static function newApplicant(Model $application): void
    {
        static::send(
            static::usersWithRole(['HR', 'Admin']),
            'New applicant',
            "{$application->fullname} applied for {$application->position} ({$application->job_type}). "
            . "Email: {$application->email}, phone: {$application->phone}.",
            'applicants',
            'info',
            "applicant:{$application->id}:new"
        );
    }

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
            "{$name} requested a time correction for {$tc->correction_date} ({$tc->correction_type}). Please review and approve.",
            'time',
            'warning',
            "time_correction:{$tc->id}:submitted"
        );
    }

    /**
     * Call after HR or Admin changes their status. It stays silent until the request
     * is final: both approved, or either one rejected. Employee gets exactly one message.
     */
    public static function timeCorrectionReviewed(Model $tc, ?string $reason = null): void
    {
        $admin = strtolower((string) $tc->admin_status);
        $hr    = strtolower((string) $tc->hr_status);

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
                ? "Your time correction request for {$tc->correction_date} was approved."
                : "Your time correction request for {$tc->correction_date} was rejected." . ($reason ? " Reason: {$reason}" : ''),
            'time',
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
            "{$leave->employee_name} requested {$leave->type} from {$leave->start_date} to {$leave->end_date}. Please review and approve.",
            'leave',
            'warning',
            "leave:{$leave->id}:submitted"
        );
    }

    /** Call once the leave reaches its final status (approved or rejected). Employee gets one message. */
    public static function leaveDecided(Model $leave, ?string $reason = null): void
    {
        $status = strtolower((string) $leave->status);
        if (! in_array($status, ['approved', 'rejected'], true) || ! $leave->user_id) {
            return;
        }

        $approved = $status === 'approved';
        $reason ??= $leave->rejection_reason ?? null;

        static::send(
            User::find($leave->user_id),
            $approved ? 'Leave request approved' : 'Leave request rejected',
            $approved
                ? "Your {$leave->type} from {$leave->start_date} to {$leave->end_date} was approved."
                : "Your {$leave->type} from {$leave->start_date} to {$leave->end_date} was rejected." . ($reason ? " Reason: {$reason}" : ''),
            'leave',
            $approved ? 'success' : 'danger',
            "leave:{$leave->id}:final"
        );
    }

    /* =====================================================================
     |  4. FINANCIAL LOAN   (HR is the only approver)
     * ===================================================================*/

    /** Employee submitted -> HR, Accounting, Admin */
    public static function loanSubmitted(Model $loan): void
    {
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
    public static function loanHrDecided(Model $loan): void
    {
        $status = strtolower((string) $loan->hr_status);
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return;
        }

        $approved = $status === 'approved';
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
    public static function loanAccountingDecided(Model $loan): void
    {
        $status = strtolower((string) $loan->accounting_status);
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return;
        }

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
    }

    protected static function loanFinal(Model $loan, bool $approved, ?string $reason): void
    {
        $amount = number_format((float) $loan->amount, 2);

        static::send(
            static::employeeUser($loan->employee_id),
            $approved ? 'Loan request approved' : 'Loan request rejected',
            $approved
                ? "Your {$loan->loan_type} request of ₱{$amount} was approved."
                : "Your {$loan->loan_type} request of ₱{$amount} was rejected." . ($reason ? " Reason: {$reason}" : ''),
            'loan',
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

        static::send(
            static::usersWithRole(['HR', 'Admin']),
            'Payslip needs review',
            "Accounting submitted {$name}'s {$payslip->cutoff} cutoff payslip for approval.",
            'payslip',
            'warning',
            "payslip:{$payslip->id}:submitted"
        );
    }

    /** HR approved/rejected -> Accounting, Admin (then checks if ready for release) */
    public static function payslipHrDecided(Model $payslip): void
    {
        static::payslipDecision($payslip, 'HR', $payslip->hr_status, $payslip->hr_rejection_reason, ['Accounting', 'Admin']);
        static::payslipReadyCheck($payslip);
    }

    /** Admin approved/rejected -> Accounting, HR (then checks if ready for release) */
    public static function payslipAdminDecided(Model $payslip): void
    {
        static::payslipDecision($payslip, 'Admin', $payslip->admin_status, $payslip->admin_rejection_reason, ['Accounting', 'HR']);
        static::payslipReadyCheck($payslip);
    }

    protected static function payslipDecision(Model $payslip, string $by, ?string $status, ?string $reason, array $notify): void
    {
        $status = strtolower((string) $status);
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return;
        }

        $name = static::employeeName($payslip->employee_id);

        static::send(
            static::usersWithRole($notify),
            "{$by} {$status} a payslip",
            "{$by} {$status} {$name}'s {$payslip->cutoff} cutoff payslip." . ($status === 'rejected' && $reason ? " Reason: {$reason}" : ''),
            'payslip',
            $status === 'approved' ? 'success' : 'danger',
            "payslip:{$payslip->id}:" . strtolower($by) . '_decision'
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

        static::send(
            static::usersWithRole(['Accounting']),
            'Payslip ready for distribution',
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
            'info',
            "payslip:{$payslip->id}:released"
        );
    }

    /** Employee approved/rejected their payslip -> Accounting. $response = PayslipResponse row */
    public static function payslipEmployeeResponded(Model $response): void
    {
        $status = strtolower((string) $response->status);
        $name   = static::employeeName($response->employee_id);

        static::send(
            static::usersWithRole(['Accounting']),
            "Employee {$status} a payslip",
            "{$name} {$status} their payslip." . ($status === 'rejected' && $response->reason ? " Reason: {$response->reason}" : ''),
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
            "HR requested documents for \"{$box->name}\".{$deadline}",
            'documents',
            'warning',
            "box:{$box->id}:employee:{$employeeId}:requested"
        );
    }

    /**
     * Employee submitted documents -> HR.
     * $documentNames: array of file/document names just submitted.
     */
    public static function documentsSubmitted(int $employeeId, array $documentNames, string $status = 'Submitted', ?int $boxId = null): void
    {
        $name = static::employeeName($employeeId);
        $date = now()->format('M d, Y h:i A');
        $list = implode(', ', $documentNames) ?: 'documents';

        static::send(
            static::usersWithRole(['HR']),
            'Documents submitted',
            "{$name} submitted: {$list}. Submitted on {$date}. Status: {$status}.",
            'documents',
            'success'
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
            "{$name} submitted an offboarding request (last working day: {$case->last_working_day}). Reason: {$case->reason}.",
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

        static::send(
            static::usersWithRole(['HR']),
            'Offboarding requirement submitted',
            "{$name} submitted \"{$document->document_label}\" and it is ready for review.",
            'offboarding',
            'info'
        );
    }

    /** HR forwarded the case for final approval -> Admin */
    public static function offboardingSentToAdmin(Model $case): void
    {
        $name = static::employeeName($case->employee_id);

        static::send(
            static::usersWithRole(['Admin']),
            'Offboarding needs final approval',
            "HR forwarded {$name}'s offboarding request for your final review.",
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
            'Admin ' . ($approved ? 'approved' : 'rejected') . ' an offboarding request',
            "Admin " . ($approved ? 'approved' : 'rejected') . " {$name}'s offboarding request." . (! $approved && $reason ? " Reason: {$reason}" : ''),
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
            "Your offboarding request was rejected by {$by}." . ($reason ? " Reason: {$reason}" : ''),
            'offboarding',
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

        $total       = (clone $docs)->count();
        $notApproved = (clone $docs)->whereRaw("LOWER(review_status) <> 'approved'")->count();

        if ($total === 0 || $notApproved > 0) {
            return;
        }

        static::send(
            static::employeeUser($case->employee_id),
            'Offboarding completed',
            'Your offboarding documents are approved and your offboarding request is fully approved. The process is complete.',
            'offboarding',
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
        $url     = static::link($routeKey);

        collect($recipients)
            ->filter()
            ->unique('id')
            ->reject(fn ($user) => $actorId && $user->id === $actorId)
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