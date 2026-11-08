<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\SubmissionBox;
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
    ];

    /* =====================================================================
     |  1. NEW APPLICANT  ->  HR, Admin
     * ===================================================================*/
    public static function newApplicant(Model $application): void
    {
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
            'applicants',
            'info',
            "applicant:{$application->id}:new"
        );
    }

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
            "Reference: TC-{$tc->id}. {$name} requested a {$tc->correction_type} correction for {$tc->correction_date} on ".($tc->created_at?->format('M j, Y g:i A') ?? 'an unknown date').". "
                ."Incorrect time: {$tc->incorrect_time}; requested time: {$tc->correct_time}. "
                .($tc->note ? "Note: {$tc->note}. " : '')
                .'Please review and approve.',
            'time',
            'warning',
            "time_correction:{$tc->id}:submitted"
        );
    }

    /**
     * Call after HR or Admin changes their status. It stays silent until the request
     * is final: both approved, or either one rejected. Employee gets exactly one message.
     */
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
                ? "Time correction TC-{$tc->id} for {$tc->correction_date} was approved by HR and Admin."
                : "Time correction TC-{$tc->id} for {$tc->correction_date} was rejected.".($reason ? " Reason: {$reason}" : ''),
            'time_employee',
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
            "Reference: LEAVE-{$leave->id}. {$leave->employee_name} requested {$leave->type} from {$leave->start_date} to {$leave->end_date}. "
                ."Reason: {$leave->reason}. Please review and approve.",
            'leave',
            'warning',
            "leave:{$leave->id}:submitted"
        );
    }

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

    /** Call once the leave reaches its final status (approved or rejected). Employee gets one message. */
    public static function leaveDecided(Model $leave, ?string $reason = null): void
    {
        $status = strtolower((string) $leave->status);
        if (! in_array($status, ['approved', 'rejected'], true) || ! $leave->user_id) {
            return;
        }

        $approved = $status === 'approved';
        $reason ??= $leave->rejection_reason ?? null;
        if ($approved && (
            strtolower((string) $leave->hr_status) !== 'approved'
            || strtolower((string) $leave->admin_status) !== 'approved'
        )) {
            return;
        }
        if (! $approved && blank($reason)) {
            return;
        }

        static::send(
            User::find($leave->user_id),
            $approved ? 'Leave request approved' : 'Leave request rejected',
            $approved
                ? "Your leave request LEAVE-{$leave->id} ({$leave->type}, {$leave->start_date} to {$leave->end_date}) was approved."
                : "Your leave request LEAVE-{$leave->id} ({$leave->type}, {$leave->start_date} to {$leave->end_date}) was rejected.".($reason ? " Reason: {$reason}" : ''),
            'leave_employee',
            $approved ? 'success' : 'danger',
            "leave:{$leave->id}:final"
        );
    }

    /* =====================================================================
     |  4. FINANCIAL LOAN   (HR -> Accounting -> Admin)
     * ===================================================================*/

    /** Employee submitted -> HR, Accounting, Admin */
    public static function loanSubmitted(Model $loan): void
    {
        $name = static::employeeName($loan->employee_id);
        $amount = number_format((float) $loan->amount, 2);

        $message = "Reference: LOAN-{$loan->id}. {$name} requested a {$loan->loan_type} of ₱{$amount} on ".($loan->created_at?->format('M j, Y g:i A') ?? 'an unknown date').". Purpose: {$loan->reason}";
        $dedupe = "loan:{$loan->id}:submitted";

        static::send(static::usersWithRole(['HR', 'Admin']), 'New loan request', $message, 'loan', 'info', $dedupe);
        static::send(static::usersWithRole(['Accounting']), 'New loan request', $message, 'loan_accounting', 'info', $dedupe);
    }

    /** HR's decision opens Accounting or ends the process if rejected. */
    public static function loanHrDecided(Model $loan): void
    {
        $status = strtolower((string) $loan->hr_status);
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return;
        }

        $approved = $status === 'approved';
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
    public static function loanAccountingDecided(Model $loan): void
    {
        $status = strtolower((string) $loan->accounting_status);
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return;
        }

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
    }

    protected static function loanFinal(Model $loan, bool $approved, ?string $reason): void
    {
        $amount = number_format((float) $loan->amount, 2);

        static::send(
            static::employeeUser($loan->employee_id),
            $approved ? 'Loan request approved' : 'Loan request rejected',
            $approved
                ? "Your {$loan->loan_type} request LOAN-{$loan->id} of ₱{$amount} was approved."
                : "Your {$loan->loan_type} request LOAN-{$loan->id} of ₱{$amount} was rejected.".($reason ? " Reason: {$reason}" : ''),
            'loan_employee',
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
        $payPeriod = static::payslipPayPeriod($payslip);

        static::send(
            static::usersWithRole(['HR', 'Admin']),
            'Payslip needs review',
            "Reference: PAYSLIP-{$payslip->id}. Accounting submitted {$name}'s {$payPeriod} payslip for approval on ".now()->format('M j, Y g:i A').". Transfer date: {$payslip->transfer_date}.",
            'payslip',
            'warning',
            "payslip:{$payslip->id}:".static::payslipCycle($payslip).':submitted'
        );
    }

    /** HR approved/rejected -> Accounting, Admin (then checks if ready for release) */
    public static function payslipHrDecided(Model $payslip): void
    {
        static::payslipDecision($payslip, 'HR', $payslip->hr_status, $payslip->hr_rejection_reason, ['Accounting', 'Admin']);
        static::payslipReadyCheck($payslip);
    }

    /** Admin approved/rejected -> Accounting (then checks if ready for release) */
    public static function payslipAdminDecided(Model $payslip): void
    {
        static::payslipDecision($payslip, 'Admin', $payslip->admin_status, $payslip->admin_rejection_reason, ['Accounting']);
        static::payslipReadyCheck($payslip);
    }

    protected static function payslipDecision(Model $payslip, string $by, ?string $status, ?string $reason, array $notify): void
    {
        $status = strtolower((string) $status);
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return;
        }

        $name = static::employeeName($payslip->employee_id);
        $payPeriod = static::payslipPayPeriod($payslip);

        static::send(
            static::usersWithRole($notify),
            "{$by} {$status} a payslip",
            "{$by} {$status} payslip PAYSLIP-{$payslip->id} for {$name} ({$payPeriod}) on ".now()->format('M j, Y g:i A').". Transfer date: {$payslip->transfer_date}.".($status === 'rejected' && $reason ? " Reason: {$reason}" : ''),
            'payslip',
            $status === 'approved' ? 'success' : 'danger',
            "payslip:{$payslip->id}:".static::payslipCycle($payslip).':'.strtolower($by).'_decision'
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
        $payPeriod = static::payslipPayPeriod($payslip);

        static::send(
            static::usersWithRole(['Accounting']),
            'Payslip ready for distribution',
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
            'info',
            "payslip:{$payslip->id}:released"
        );
    }

    /** Employee approved/rejected their payslip -> Accounting. $response = PayslipResponse row */
    public static function payslipEmployeeResponded(Model $response): void
    {
        $status = strtolower((string) $response->status);
        $name = static::employeeName($response->employee_id);

        static::send(
            static::usersWithRole(['Accounting']),
            "Employee {$status} a payslip",
            "{$name} {$status} payslip PAYSLIP-{$response->payslip_id} on ".now()->format('M j, Y g:i A').'.'.($status === 'rejected' && $response->reason ? " Reason: {$response->reason}" : ''),
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
            "Submission box DOCBOX-{$box->id}: HR requested documents for \"{$box->name}\".{$deadline}",
            'documents_employee',
            'warning',
            "box:{$box->id}:employee:{$employeeId}:requested"
        );
    }

    /**
     * Employee submitted documents -> HR.
     * $documentNames: array of file/document names just submitted.
     */
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

        static::send(
            static::usersWithRole(['HR']),
            'Documents submitted',
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
            "Reference: OFFBOARD-{$case->id}. {$name} submitted an offboarding request (last working day: {$case->last_working_day}). Reason: {$case->reason}.",
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
        $reference = $case ? "OFFBOARD-{$case->id}" : "OFFBOARD-DOC-{$document->id}";

        static::send(
            static::usersWithRole(['HR']),
            'Offboarding requirement submitted',
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
        );
    }

    /** HR forwarded the case for final approval -> Admin */
    public static function offboardingSentToAdmin(Model $case): void
    {
        $name = static::employeeName($case->employee_id);

        static::send(
            static::usersWithRole(['Admin']),
            'Offboarding needs final approval',
            "Reference: OFFBOARD-{$case->id}. HR forwarded {$name}'s offboarding request for final review.",
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
            'Admin '.($approved ? 'approved' : 'rejected').' an offboarding request',
            "Admin ".($approved ? 'approved' : 'rejected')." offboarding request OFFBOARD-{$case->id} for {$name}.".(! $approved && $reason ? " Reason: {$reason}" : ''),
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
            "Offboarding request OFFBOARD-{$case->id} was rejected by {$by}.".($reason ? " Reason: {$reason}" : ''),
            'offboarding_employee',
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

        $total = (clone $docs)->count();
        $notApproved = (clone $docs)->whereRaw("LOWER(review_status) <> 'approved'")->count();

        if ($total === 0 || $notApproved > 0) {
            return;
        }

        static::send(
            static::employeeUser($case->employee_id),
            'Offboarding completed',
            "Offboarding request OFFBOARD-{$case->id} has final approval and all required documents are approved. The process is complete.",
            'offboarding_employee',
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
        $from = Auth::user()?->name ?? 'System';
        $url = static::link($routeKey);

        collect($recipients)
            ->filter()
            ->unique('id')
            ->reject(fn ($user) => $actorId && $user->id === $actorId)
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
