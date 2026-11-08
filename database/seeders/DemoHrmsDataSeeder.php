<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\NonWorkingDay;
use App\Models\OffboardingCase;
use App\Models\TimeCorrection;
use App\Models\User;
use App\Support\AttendanceSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoHrmsDataSeeder extends Seeder
{
    private const REQUEST_COUNT = 10;

    private const APPLICANT_DEFAULT_PASSWORD = 'passwor123';

    public function run(): void
    {
        $today = today();
        $removedHolidayDate = \Illuminate\Support\Carbon::create(2026, 11, 4)->startOfDay();
        $start = $today->copy()->subMonthsNoOverflow(3);
        $employees = Employee::query()->orderBy('id')->get();

        if ($employees->isEmpty()) {
            throw new \RuntimeException('Cannot generate demo HR data because the employee database is empty.');
        }

        $eligibleEmployees = Employee::query()
            ->excludeInternalStaff()
            ->orderBy('id')
            ->limit(self::REQUEST_COUNT)
            ->get();
        $payrollEmployees = Employee::query()
            ->excludeInternalStaff()
            ->orderBy('id')
            ->get();

        if ($eligibleEmployees->count() < self::REQUEST_COUNT) {
            throw new \RuntimeException('At least 10 non-internal employees are required to seed demo requests.');
        }

        $hrId = User::role('HR')->value('id');
        $adminId = User::role('Admin')->value('id');

        DB::transaction(function () use (
            $today,
            $removedHolidayDate,
            $start,
            $employees,
            $eligibleEmployees,
            $payrollEmployees,
            $hrId,
            $adminId,
        ): void {
            $attendanceInserted = $this->seedAttendance($employees, $start, $today);
            $attendanceCorrected = $this->correctRemovedHolidayAttendance($removedHolidayDate);
            $leaveInserted = $this->seedLeaveRequests($eligibleEmployees, $start, $today, $hrId, $adminId);
            $correctionsInserted = $this->seedTimeCorrections(
                $eligibleEmployees,
                $start,
                $today,
                $hrId,
            );
            [$payrollInserted, $payslipsInserted] = $this->seedPayroll(
                $payrollEmployees,
                $start,
                $today,
                $hrId,
                $adminId,
            );
            $loansInserted = $this->seedLoanManagement($eligibleEmployees, $start, $hrId, $adminId);
            $onboardingInserted = $this->seedOnboarding($start, $today);
            [$offboardingInserted, $offboardingForwarded, $offboardingDeleted] = $this->seedOffboarding(
                $start,
                $today,
                $hrId,
            );

            if (
                $attendanceInserted + $attendanceCorrected + $leaveInserted + $correctionsInserted
                + $payrollInserted + $payslipsInserted + $loansInserted + $onboardingInserted
                + $offboardingInserted + $offboardingForwarded + $offboardingDeleted > 0
            ) {
                $versionUpdated = DB::table('system_data_versions')
                    ->where('id', 1)
                    ->increment('version', 1, ['updated_at' => now()]);

                if ($versionUpdated !== 1) {
                    throw new \RuntimeException('The system data update version could not be advanced.');
                }
            }

            $this->command?->info("Demo attendance rows added: {$attendanceInserted}");
            $this->command?->info("Nov 4 attendance rows corrected: {$attendanceCorrected}");
            $this->command?->info("Demo leave requests added: {$leaveInserted}");
            $this->command?->info("Demo time-correction requests added: {$correctionsInserted}");
            $this->command?->info("Demo payroll-processing rows added: {$payrollInserted}");
            $this->command?->info("Demo released payslips added: {$payslipsInserted}");
            $this->command?->info("Demo loan requests added: {$loansInserted}");
            $this->command?->info("Demo onboarding applications added: {$onboardingInserted}");
            $this->command?->info("Old demo offboarding cases removed: {$offboardingDeleted}");
            $this->command?->info("Demo offboarding cases added: {$offboardingInserted}");
            $this->command?->info("Demo offboarding cases forwarded to Admin: {$offboardingForwarded}");
        });
    }

    private function seedAttendance($employees, Carbon $start, Carbon $today): int
    {
        $existing = Attendance::query()
            ->whereBetween('date', [$start->toDateString(), $today->toDateString()])
            ->get(['employee_id', 'date'])
            ->mapWithKeys(fn (Attendance $record) => [
                $record->employee_id.'|'.$record->date->toDateString() => true,
            ]);

        $holidays = NonWorkingDay::query()
            ->whereBetween('date', [$start->toDateString(), $today->toDateString()])
            ->get()
            ->keyBy(fn (NonWorkingDay $day) => $day->date->toDateString());

        $rows = [];
        $now = now();

        foreach ($employees as $employee) {
            foreach (CarbonPeriod::create($start, $today) as $day) {
                if (! $day->isWeekday()) {
                    continue;
                }

                $date = $day->toDateString();
                $key = $employee->id.'|'.$date;

                if ($existing->has($key)) {
                    continue;
                }

                $holiday = $holidays->get($date);
                if ($holiday) {
                    $rows[] = $this->attendanceRow(
                        $employee->id,
                        $date,
                        null,
                        null,
                        null,
                        $holiday->isPaid() ? Attendance::PAID_HOLIDAY : Attendance::NON_WORKING_DAY,
                        $now,
                    );
                    continue;
                }

                $schedule = AttendanceSchedule::for($employee->position, $day);
                $pattern = crc32($employee->id.':'.$date) % 100;

                if ($day->isSameDay($today)) {
                    $timeIn = $schedule->start;
                    $timeOut = null;
                    $hours = null;
                    $status = 'Present';
                } elseif ($pattern < 4) {
                    $timeIn = null;
                    $timeOut = null;
                    $hours = null;
                    $status = Attendance::ABSENT;
                } else {
                    $timeIn = $schedule->start;
                    $timeOut = match (true) {
                        $pattern < 11 => '12:30',
                        $pattern < 19 => $schedule->start === '06:00' ? '14:30' : '15:00',
                        $pattern < 30 => Carbon::parse($schedule->start)->addMinutes(25)->format('H:i'),
                        default => $schedule->end,
                    };

                    $dateCarbon = Carbon::parse($date);
                    $in = AttendanceSchedule::toCarbon($dateCarbon, $timeIn);
                    $out = AttendanceSchedule::toCarbon($dateCarbon, $timeOut);
                    $hours = $schedule->hoursWorked($in, $out);
                    $status = $schedule->status($in, $out);
                }

                $rows[] = $this->attendanceRow(
                    $employee->id,
                    $date,
                    $timeIn,
                    $timeOut,
                    $hours,
                    $status,
                    $now,
                );
                $existing->put($key, true);
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('attendance_records')->insert($chunk);
        }

        return count($rows);
    }

    private function attendanceRow(
        int $employeeId,
        string $date,
        ?string $timeIn,
        ?string $timeOut,
        ?float $hours,
        string $status,
        Carbon $createdAt,
    ): array {
        return [
            'employee_id' => $employeeId,
            'date' => $date,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'hours_worked' => $hours,
            'status' => $status,
            'archived' => false,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    private function correctRemovedHolidayAttendance(Carbon $date): int
    {
        if (NonWorkingDay::query()->whereDate('date', $date->toDateString())->exists()) {
            return 0;
        }

        $records = Attendance::query()
            ->whereDate('date', $date->toDateString())
            ->where('status', Attendance::NON_WORKING_DAY)
            ->with('employee:id,position')
            ->get();

        foreach ($records as $record) {
            $schedule = AttendanceSchedule::for($record->employee?->position, $date);
            $record->update([
                'time_in' => $schedule->start,
                'time_out' => null,
                'hours_worked' => null,
                'status' => 'Present',
            ]);
        }

        return $records->count();
    }

    private function seedLoanManagement($employees, Carbon $start, ?int $hrId, ?int $adminId): int
    {
        $accountingId = User::role('Accounting')->value('id');
        $loanTypes = ['SSS', 'HMDF', 'PhilHealth', 'PERAA/CEAP', 'Cash Advance'];
        $now = now();
        $inserted = 0;

        foreach ($employees as $index => $employee) {
            $number = $index + 1;
            $reason = "DEMO DATA: Financial loan request #{$number}";

            if (DB::table('loan_requests')->where('reason', $reason)->exists()) {
                continue;
            }

            $requestedAt = $start->copy()->addDays(2 + $index * 7)->setTime(10, 30);
            $hrApproved = in_array($index, [1, 2, 4, 5, 6, 7, 8], true);
            $hrRejected = $index === 3;
            $accountingApproved = in_array($index, [2, 5, 6, 7], true);
            $accountingRejected = $index === 4;
            $adminApproved = in_array($index, [5, 6, 7], true);
            $amount = $loanTypes[$index % count($loanTypes)] === 'Cash Advance'
                ? 1500 + ($index * 50)
                : 5000 + ($index * 1750);
            $hrReviewedAt = $hrApproved || $hrRejected ? $requestedAt->copy()->addDay() : null;
            $accountingReviewedAt = $accountingApproved || $accountingRejected
                ? $requestedAt->copy()->addDays(2)
                : null;
            $adminReviewedAt = $adminApproved ? $requestedAt->copy()->addDays(3) : null;

            $loanId = DB::table('loan_requests')->insertGetId([
                'employee_id' => $employee->id,
                'loan_type' => $loanTypes[$index % count($loanTypes)],
                'amount' => $amount,
                'reason' => $reason,
                'requested_at' => $requestedAt,
                'hr_status' => $hrRejected ? 'rejected' : ($hrApproved ? 'approved' : 'pending'),
                'hr_rejection_reason' => $hrRejected ? 'DEMO DATA: Sample HR rejection.' : null,
                'hr_reviewed_at' => $hrReviewedAt,
                'hr_reviewed_by' => $hrReviewedAt ? $hrId : null,
                'accounting_status' => $accountingRejected
                    ? 'rejected'
                    : ($accountingApproved ? 'approved' : 'pending'),
                'accounting_rejection_reason' => $accountingRejected
                    ? 'DEMO DATA: Sample Accounting rejection.'
                    : null,
                'accounting_reviewed_at' => $accountingReviewedAt,
                'accounting_reviewed_by' => $accountingReviewedAt ? $accountingId : null,
                'admin_status' => $adminApproved ? 'approved' : 'pending',
                'admin_rejection_reason' => null,
                'admin_reviewed_at' => $adminReviewedAt,
                'admin_reviewed_by' => $adminReviewedAt ? $adminId : null,
                'archived_at' => null,
                'staff_archived_at' => null,
                'staff_archived_by' => null,
                'created_at' => $requestedAt,
                'updated_at' => $now,
            ]);

            if ($adminApproved) {
                $installments = $index === 5 ? 1 : 2;
                for ($installment = 1; $installment <= $installments; $installment++) {
                    DB::table('loan_repayments')->insert([
                        'loan_request_id' => $loanId,
                        'amount' => round($amount * 0.1, 2),
                        'paid_at' => $requestedAt->copy()->addDays(30 * $installment)->toDateString(),
                        'payslip_id' => null,
                        'created_at' => $requestedAt->copy()->addDays(30 * $installment),
                        'updated_at' => $now,
                    ]);
                }
            }

            $inserted++;
        }

        return $inserted;
    }

    private function seedOnboarding(Carbon $start, Carbon $today): int
    {
        $applicantRole = \Spatie\Permission\Models\Role::query()
            ->where('name', 'Applicant')
            ->where('guard_name', 'web')
            ->firstOrFail();
        $positions = DB::table('job_openings')->orderBy('id')->pluck('title')->all();
        if ($positions === []) {
            $positions = ['Elementary Teacher'];
        }

        $applicants = [
            ['Amelia', 'Rose', 'Santos'],
            ['Noah', 'James', 'Reyes'],
            ['Sophia', 'Mae', 'Cruz'],
            ['Liam', 'Gabriel', 'Garcia'],
            ['Isabella', 'Grace', 'Mendoza'],
            ['Ethan', 'Luis', 'Ramos'],
            ['Mia', 'Claire', 'Flores'],
            ['Lucas', 'Andre', 'Bautista'],
            ['Ava', 'Nicole', 'Torres'],
            ['Mateo', 'Jose', 'Rivera'],
        ];
        $statuses = [
            'pending', 'pending', 'pending', 'hr_approved', 'hr_approved',
            'qualified', 'hr_rejected', 'hr_rejected', 'rejected', 'pending',
        ];
        $now = now();
        $inserted = 0;

        foreach ($applicants as $index => [$firstName, $middleName, $lastName]) {
            $number = $index + 1;
            $email = sprintf('demo.applicant.%02d@example.test', $number);
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "{$firstName} {$middleName} {$lastName}",
                    'password' => Hash::make(self::APPLICANT_DEFAULT_PASSWORD),
                ],
            );

            if (! $user->hasRole($applicantRole)) {
                $user->assignRole($applicantRole);
            }

            if (DB::table('applications')->where('user_id', $user->id)->exists()) {
                continue;
            }

            $status = $statuses[$index];
            $createdAt = $start->copy()->addDays(4 + $index * 8)->setTime(9, 0);
            $hasInterview = in_array($index, [2, 3, 4, 5, 6, 7, 8], true);
            $interviewDate = $hasInterview
                ? $createdAt->copy()->addDays(3)->min($today->copy()->subDay())
                : null;
            if (in_array($index, [1, 2], true)) {
                $interviewDate = $today->copy()->addDays(2 + $index);
            }
            $interviewCompletedAt = in_array($status, ['hr_approved', 'qualified', 'hr_rejected', 'rejected'], true)
                ? $interviewDate?->copy()->setTime(12, 0)
                : null;
            $position = $positions[$index % count($positions)];

            DB::table('applications')->insert([
                'user_id' => $user->id,
                'fullname' => "{$firstName} {$middleName} {$lastName}",
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => sprintf('0917%07d', 1000000 + $number),
                'home_address' => "{$number} Demo Street, Sample City, Philippines",
                'birth_date' => $today->copy()->subYears(22 + ($index % 12))->subDays($index * 9)->toDateString(),
                'gender' => $index % 2 === 0 ? 'Female' : 'Male',
                'civil_status' => $index % 3 === 0 ? 'Married' : 'Single',
                'education' => $index % 2 === 0 ? "Bachelor's Degree" : 'Vocational',
                'job_type' => str_contains(strtolower($position), 'teacher') ? 'Teaching' : 'Staff',
                'position' => $position,
                'resume' => "demo-data/applicants/{$user->id}/resume.pdf",
                'coverletter' => "demo-data/applicants/{$user->id}/cover-letter.pdf",
                'appletter' => "demo-data/applicants/{$user->id}/application-letter.pdf",
                'interview_date' => $interviewDate?->toDateString(),
                'interview_time' => $interviewDate ? '10:00 AM' : null,
                'interview_location' => $interviewDate ? 'Main Office' : null,
                'interview_note' => $interviewDate ? 'DEMO DATA: Sample interview schedule.' : null,
                'interview_completed_at' => $interviewCompletedAt,
                'status' => $status,
                'rejection_reason' => in_array($status, ['hr_rejected', 'rejected'], true)
                    ? 'DEMO DATA: Sample decision for workflow testing.'
                    : null,
                'created_at' => $createdAt,
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        return $inserted;
    }

    private function seedOffboarding(Carbon $start, Carbon $today, ?int $hrId): array
    {
        $demoCases = OffboardingCase::query()
            ->where(function ($query) {
                $query->where('employee_message', 'like', 'DEMO DATA: Sample offboarding request #%')
                    ->orWhere('employee_message', 'like', 'DEMO DATA: Random employee offboarding request #%');
            })
            ->with('documents')
            ->get();

        if (
            $demoCases->count() === 5
            && $demoCases->every(fn (OffboardingCase $case) => $case->sent_to_admin
                && $case->documents->where('review_status', 'Approved')->count() === count(OffboardingCase::uploadableLabels()))
        ) {
            return [0, 0, 0];
        }

        $deleted = OffboardingCase::query()
            ->where(function ($query) {
                $query->where('employee_message', 'like', 'DEMO DATA: Sample offboarding request #%')
                    ->orWhere('employee_message', 'like', 'DEMO DATA: Random employee offboarding request #%');
            })
            ->delete();

        $existingEmployeeIds = OffboardingCase::query()->pluck('employee_id');
        $employees = Employee::query()
            ->whereDoesntHave('user', fn ($query) => $query->role(['HR', 'Admin', 'Accounting']))
            ->where('position', 'not like', '%HR%')
            ->where('position', 'not like', '%Admin%')
            ->where('position', 'not like', '%Accounting%')
            ->whereNotIn('id', $existingEmployeeIds)
            ->inRandomOrder()
            ->limit(5)
            ->get();

        if ($employees->count() < 5) {
            throw new \RuntimeException('At least five non-management employees without existing offboarding cases are required.');
        }

        $now = now();
        foreach ($employees as $index => $employee) {
            $number = $index + 1;
            $requestedAt = $start->copy()->addDays(18 + $index * 12)->setTime(9, 0);
            $requirementsSentAt = $requestedAt->copy()->addDay();
            $forwardedAt = $requirementsSentAt->copy()->addDay();

            $case = OffboardingCase::query()->create([
                'employee_id' => $employee->id,
                'reason' => 'Resignation',
                'last_working_day' => $today->copy()->addDays(20 + $index)->toDateString(),
                'employee_message' => "DEMO DATA: Random employee offboarding request #{$number}.",
                'status' => 'Open',
                'progress' => 100,
                'requirements_sent_at' => $requirementsSentAt,
                'required_documents' => OffboardingCase::REQUIRED_DOCUMENTS,
                'completed_interviews' => OffboardingCase::INTERVIEW_LABELS,
                'rejection_reason' => null,
                'sent_to_admin' => true,
                'sent_to_admin_at' => $forwardedAt,
                'admin_final_approved' => false,
                'admin_approved_at' => null,
                'admin_approved_by' => null,
                'created_at' => $requestedAt,
                'updated_at' => $now,
            ]);

            foreach (OffboardingCase::uploadableLabels() as $documentIndex => $label) {
                $case->documents()->create([
                    'document_label' => $label,
                    'file_name' => "DEMO DATA - {$label}.pdf",
                    'file_path' => "demo-data/offboarding/{$case->id}/document-{$documentIndex}.pdf",
                    'review_status' => 'Approved',
                    'reviewed_at' => $forwardedAt,
                    'reviewed_by' => $hrId,
                    'rejection_reason' => null,
                ]);
            }
        }

        return [$employees->count(), $employees->count(), $deleted];
    }

    private function seedLeaveRequests($employees, Carbon $start, Carbon $today, ?int $hrId, ?int $adminId): int
    {
        $types = array_keys(config('leave.types'));
        $now = now();
        $inserted = 0;

        foreach ($employees as $index => $employee) {
            $number = $index + 1;
            $reason = "DEMO DATA: Leave request #{$number}";

            if (DB::table('leave_requests')->where('reason', $reason)->exists()) {
                continue;
            }

            $requestedDate = $start->copy()->addDays(3 + $index * 8);
            $leaveStart = $requestedDate->copy()->addDays(4);
            $leaveEnd = $leaveStart->copy()->addDays($index % 2);
            if ($leaveEnd->greaterThan($today)) {
                $leaveEnd = $today->copy();
            }

            $status = match (true) {
                $index < 4 => LeaveRequest::PENDING,
                $index < 7 => LeaveRequest::APPROVED,
                default => LeaveRequest::REJECTED,
            };
            $decidedAt = $status === LeaveRequest::PENDING
                ? null
                : $requestedDate->copy()->addDay()->setTime(10, 0);

            DB::table('leave_requests')->insert([
                'user_id' => $employee->user_id,
                'employee_code' => $employee->employee_id,
                'employee_name' => $employee->name,
                'department' => $employee->department,
                'type' => $this->leaveTypeFor($employee, $types, $index),
                'requested_date' => $requestedDate->toDateString(),
                'start_date' => $leaveStart->toDateString(),
                'end_date' => $leaveEnd->toDateString(),
                'reason' => $reason,
                'leave_balance' => 'Demo balance',
                'status' => $status,
                'employee_archived' => false,
                'admin_archived' => false,
                'rejection_reason' => $status === LeaveRequest::REJECTED
                    ? 'DEMO DATA: Sample rejection for workflow testing.'
                    : null,
                'decided_by' => $status === LeaveRequest::PENDING ? null : $adminId,
                'decided_at' => $decidedAt,
                'hr_status' => $status === LeaveRequest::PENDING ? LeaveRequest::PENDING : $status,
                'hr_decided_by' => $status === LeaveRequest::PENDING ? null : $hrId,
                'hr_decided_at' => $decidedAt,
                'admin_status' => $status === LeaveRequest::PENDING ? LeaveRequest::PENDING : $status,
                'admin_decided_by' => $status === LeaveRequest::PENDING ? null : $adminId,
                'admin_decided_at' => $decidedAt,
                'created_at' => $requestedDate->setTime(9, 0),
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        return $inserted;
    }

    private function leaveTypeFor(Employee $employee, array $types, int $index): string
    {
        $status = strtolower(str_replace('-', ' ', (string) $employee->employment_status));

        return $status === 'non regular'
            ? config('leave.non_regular.type')
            : $types[$index % count($types)];
    }

    private function seedTimeCorrections($employees, Carbon $start, Carbon $today, ?int $hrId): int
    {
        $attendance = Attendance::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('date', [$start->toDateString(), $today->toDateString()])
            ->orderBy('date')
            ->get()
            ->groupBy('employee_id');

        $inserted = 0;

        foreach ($employees as $index => $employee) {
            $number = $index + 1;
            $note = "DEMO DATA: Time correction request #{$number}";

            if (DB::table('time_corrections')->where('note', $note)->exists()) {
                continue;
            }

            $employeeAttendance = $attendance->get($employee->id, collect());
            if ($employeeAttendance->isEmpty()) {
                continue;
            }

            $record = $employeeAttendance->get($index % $employeeAttendance->count());
            $date = $record->date->copy();
            $schedule = AttendanceSchedule::for($employee->position, $date);
            $type = $index % 2 === 0 ? 'Time In' : 'Time Out';
            $incorrect = $type === 'Time In' ? $record->time_in : $record->time_out;
            $correct = $type === 'Time In' ? $schedule->start : $schedule->end;
            if ($incorrect === $correct) {
                $correct = Carbon::parse($correct)->addMinutes($type === 'Time In' ? 15 : -15)->format('H:i');
            }

            $hrRejected = in_array($index, [3, 8], true);
            $hrApproved = in_array($index, [1, 5, 7], true);
            $reviewedAt = $date->copy()->addDay()->setTime(11, 0);

            DB::table('time_corrections')->insert([
                'employee_id' => $employee->id,
                'correction_date' => $date->toDateString(),
                'correction_type' => $type,
                'incorrect_time' => $incorrect,
                'correct_time' => $correct,
                'note' => $note,
                'proof_path' => null,
                'admin_status' => $hrRejected ? TimeCorrection::REJECTED : TimeCorrection::PENDING,
                'admin_reviewed_at' => $hrRejected ? $reviewedAt : null,
                'admin_reviewed_by' => null,
                'hr_status' => $hrRejected
                    ? TimeCorrection::REJECTED
                    : ($hrApproved ? TimeCorrection::APPROVED : TimeCorrection::PENDING),
                'hr_reviewed_at' => ($hrRejected || $hrApproved) ? $reviewedAt : null,
                'hr_reviewed_by' => ($hrRejected || $hrApproved) ? $hrId : null,
                'applied_at' => null,
                'rejection_reason' => $hrRejected
                    ? 'DEMO DATA: Sample HR rejection for workflow testing.'
                    : null,
                'created_at' => $date->copy()->setTime(9, 0),
                'updated_at' => now(),
            ]);
            $inserted++;
        }

        return $inserted;
    }

    private function seedPayroll($employees, Carbon $start, Carbon $today, ?int $hrId, ?int $adminId): array
    {
        $steps = DB::table('payroll_steps')->orderBy('step_no')->get()->keyBy('step_no');
        if ($steps->isEmpty()) {
            throw new \RuntimeException('Cannot generate demo payroll because no payroll steps are configured.');
        }

        $attendance = Attendance::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('archived', false)
            ->whereBetween('date', [$start->toDateString(), $today->toDateString()])
            ->orderBy('date')
            ->get()
            ->groupBy('employee_id');

        $payrollInserted = 0;
        $payslipsInserted = 0;
        $now = now();

        $months = CarbonPeriod::create(
            $start->copy()->startOfMonth(),
            '1 month',
            $today->copy()->startOfMonth(),
        );

        foreach ($months as $month) {
            foreach (['15th', '30th'] as $cutoff) {
                [$periodStart, $periodEnd] = $this->cutoffRange($month, $cutoff);
                if ($periodStart->greaterThan($today)) {
                    continue;
                }

                $payDate = $cutoff === '15th'
                    ? $month->copy()->day(15)
                    : $month->copy()->endOfMonth();
                $transferDate = $month->copy()->startOfMonth()->toDateString();

                foreach ($employees as $employee) {
                    $metrics = $this->attendanceMetrics(
                        $employee,
                        $attendance->get($employee->id, collect()),
                        $periodStart->max($start),
                        $periodEnd->min($today),
                    );
                    $stepNo = (int) ($employee->step_no ?: 1);
                    $step = $steps->get($stepNo) ?? $steps->first();
                    $dailyWage = (float) $step->daily_wage;
                    $pay = $this->calculatePay($employee, $metrics, $dailyWage, $cutoff);
                    $createdAt = $payDate->copy()->startOfDay();

                    $payrollExists = DB::table('payroll_records')
                        ->where('employee_id', $employee->id)
                        ->where('period', $month->format('Y-m'))
                        ->where('cutoff', $cutoff)
                        ->exists();

                    if (! $payrollExists) {
                        DB::table('payroll_records')->insert([
                            'employee_id' => $employee->id,
                            'period' => $month->format('Y-m'),
                            'cutoff' => $cutoff,
                            'step_no' => $stepNo,
                            'days_worked' => $metrics['days_worked'],
                            'hours_worked' => $metrics['hours_worked'],
                            'late_minutes' => $metrics['late_minutes'],
                            'honorarium' => $pay['honorarium'],
                            'sss' => 0,
                            'sss_loan' => 0,
                            'philhealth' => 0,
                            'philhealth_loan' => 0,
                            'peraa_ceap' => 0,
                            'peraa_ceap_loan' => 0,
                            'hdmf' => 0,
                            'hdmf_loan' => 0,
                            'cash_advance' => 0,
                            'created_at' => $createdAt,
                            'updated_at' => $now,
                        ]);
                        $payrollInserted++;
                    }

                    if ($periodEnd->greaterThan($today)) {
                        continue;
                    }

                    $payslipExists = DB::table('payslips')
                        ->where('employee_id', $employee->id)
                        ->where('transfer_date', $transferDate)
                        ->where('cutoff', $cutoff)
                        ->exists();

                    if ($payslipExists) {
                        continue;
                    }

                    $reviewedAt = $payDate->copy()->addDay()->setTime(12, 0);
                    $releasedAt = $payDate->copy()->addDays(2)->setTime(16, 0);

                    DB::table('payslips')->insert([
                        'employee_id' => $employee->id,
                        'cutoff' => $cutoff,
                        'transfer_date' => $transferDate,
                        'step_name' => $step->name,
                        'days_worked' => $metrics['days_worked'],
                        'hours_worked' => $pay['hours_worked'],
                        'late_minutes' => $metrics['late_minutes'],
                        'basic_pay' => $pay['basic_pay'],
                        'daily_wage' => $dailyWage,
                        'honorarium' => $pay['honorarium'],
                        'gross_pay' => $pay['gross_pay'],
                        'malasakitan' => 50,
                        'late_deduction' => $pay['late_deduction'],
                        'absences_deduction' => 0,
                        'sss' => $pay['sss'],
                        'sss_loan' => 0,
                        'philhealth' => $pay['philhealth'],
                        'philhealth_loan' => 0,
                        'peraa_ceap' => $pay['peraa_ceap'],
                        'peraa_ceap_loan' => 0,
                        'hdmf' => $pay['hdmf'],
                        'hdmf_loan' => 0,
                        'cash_advance' => 0,
                        'total_deductions' => $pay['total_deductions'],
                        'net_pay' => $pay['net_pay'],
                        'sent_to_admin_at' => $payDate->copy()->startOfDay(),
                        'admin_status' => 'Approved',
                        'admin_reviewed_at' => $reviewedAt,
                        'admin_reviewed_by' => $adminId,
                        'hr_status' => 'Approved',
                        'hr_reviewed_at' => $reviewedAt,
                        'hr_reviewed_by' => $hrId,
                        'released_at' => $releasedAt,
                        'created_at' => $createdAt,
                        'updated_at' => $now,
                    ]);
                    $payslipsInserted++;
                }
            }
        }

        return [$payrollInserted, $payslipsInserted];
    }

    private function attendanceMetrics(Employee $employee, $records, Carbon $start, Carbon $end): array
    {
        $daysWorked = 0.0;
        $hoursWorked = 0.0;
        $lateMinutes = 0;

        foreach ($records as $record) {
            if ($record->date->lt($start) || $record->date->gt($end)
                || $record->status === Attendance::NWD_REVIEW) {
                continue;
            }

            $date = $record->date->copy()->startOfDay();
            $schedule = AttendanceSchedule::for($employee->position, $date);
            $timeIn = AttendanceSchedule::toCarbon($date, $record->time_in);
            $timeOut = AttendanceSchedule::toCarbon($date, $record->time_out);

            $daysWorked += Attendance::computeDaysWorked($record->status);
            $hoursWorked += $timeIn && $timeOut
                ? $schedule->hoursWorked($timeIn, $timeOut)
                : (float) $record->hours_worked;
            $lateMinutes += $schedule->lateMinutes($timeIn);
        }

        return [
            'days_worked' => round($daysWorked, 2),
            'hours_worked' => round($hoursWorked, 2),
            'late_minutes' => $lateMinutes,
        ];
    }

    private function calculatePay(Employee $employee, array $metrics, float $dailyWage, string $cutoff): array
    {
        $days = $metrics['days_worked'];
        $hours = min($metrics['hours_worked'], $days * 8);
        $basicPay = $days > 0 ? $dailyWage * $days * min(max(($hours / $days) / 8, 0), 1) : 0;
        $honorarium = strtolower((string) $employee->department) === 'administrative' ? 1924.31 : 0;
        $grossPay = $basicPay + $honorarium;
        $lateDeduction = $metrics['late_minutes'] * 1.80;
        $monthlyWage = $dailyWage * 26;
        $is30th = $cutoff === '30th';
        $sss = $is30th ? $this->sssContribution($monthlyWage) : 0;
        $philhealth = $is30th ? $monthlyWage * 0.025 : 0;
        $peraa = $is30th ? $monthlyWage * 0.04 : 0;
        $hdmf = $is30th ? 200 : 0;
        $totalDeductions = 50 + $lateDeduction + $sss + $philhealth + $peraa + $hdmf;

        return [
            'hours_worked' => round($hours, 2),
            'basic_pay' => round($basicPay, 2),
            'honorarium' => round($honorarium, 2),
            'gross_pay' => round($grossPay, 2),
            'late_deduction' => round($lateDeduction, 2),
            'sss' => round($sss, 2),
            'philhealth' => round($philhealth, 2),
            'peraa_ceap' => round($peraa, 2),
            'hdmf' => round($hdmf, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_pay' => round($grossPay - $totalDeductions, 2),
        ];
    }

    private function sssContribution(float $monthlyWage): float
    {
        if ($monthlyWage < 12750) {
            return 0;
        }

        return min(1000, 650 + floor(($monthlyWage - 12750) / 500) * 25);
    }

    private function cutoffRange(Carbon $month, string $cutoff): array
    {
        if ($cutoff === '15th') {
            return [
                $month->copy()->subMonthNoOverflow()->day(26)->startOfDay(),
                $month->copy()->day(10)->endOfDay(),
            ];
        }

        return [
            $month->copy()->day(11)->startOfDay(),
            $month->copy()->day(25)->endOfDay(),
        ];
    }
}
