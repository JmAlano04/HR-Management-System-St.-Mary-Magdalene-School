<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\NonWorkingDay;
use App\Models\SchoolEmployee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NonWorkingDayController extends Controller
{
    /** GET /non-working-days  (route: non-working-days.index) — JSON list. */
    public function index(): JsonResponse
    {
        return response()->json([
            'days' => NonWorkingDay::orderBy('date')->get()->map(fn (NonWorkingDay $d) => [
                'id'         => $d->id,
                'date'       => $d->date->format('Y-m-d'),
                'name'       => $d->name,
                'category'   => $d->category,
                'pay_type'   => $d->pay_type,
                'paid_days'  => (float) $d->paid_days,
                'paid_hours' => (float) $d->paid_hours,
            ]),
        ]);
    }

    /** POST /non-working-days  (route: hr.non-working-days.store) */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('nwd', [
            'date'       => ['required', 'date', 'unique:non_working_days,date'],
            'name'       => ['required', 'string', 'max:100'],
            'category'   => ['required', Rule::in(NonWorkingDay::CATEGORIES)],
            'pay_type'   => ['required', Rule::in([NonWorkingDay::PAID, NonWorkingDay::UNPAID])],
            'paid_days'  => ['nullable', 'numeric', 'min:0', 'max:1'],
            'paid_hours' => ['nullable', 'numeric', 'min:0', 'max:8'],
        ], [
            'date.unique' => 'A non-working day is already configured for this date.',
        ]);

        $isPaid = $data['pay_type'] === NonWorkingDay::PAID;

        DB::transaction(function () use ($data, $isPaid) {
            $nonWorkingDay = NonWorkingDay::create([
                'date'       => $data['date'],
                'name'       => trim($data['name']),
                'category'   => $data['category'],
                'pay_type'   => $data['pay_type'],
                'paid_days'  => $isPaid ? ($data['paid_days'] ?? 1) : 0,
                'paid_hours' => $isPaid ? ($data['paid_hours'] ?? 8) : 0,
            ]);

            $employeeIds = SchoolEmployee::query()->pluck('id');
            $existing = Attendance::query()
                ->whereDate('date', $nonWorkingDay->date)
                ->get(['id', 'employee_id', 'time_in', 'time_out']);
            $existingEmployeeIds = $existing->pluck('employee_id');
            $holidayStatus = $nonWorkingDay->isPaid()
                ? Attendance::PAID_HOLIDAY
                : Attendance::NON_WORKING_DAY;

            Attendance::query()
                ->whereDate('date', $nonWorkingDay->date)
                ->where('archived', false)
                ->whereNull('time_in')
                ->whereNull('time_out')
                ->where('status', Attendance::ABSENT)
                ->update(['status' => $holidayStatus]);

            $loggedRecordIds = $existing
                ->filter(fn (Attendance $attendance) => $attendance->time_in || $attendance->time_out)
                ->pluck('id');

            if ($loggedRecordIds->isNotEmpty()) {
                Attendance::query()
                    ->whereIn('id', $loggedRecordIds)
                    ->where('archived', false)
                    ->update(['status' => Attendance::NWD_REVIEW]);
            }

            $rows = $employeeIds
                ->diff($existingEmployeeIds)
                ->map(fn ($employeeId) => [
                    'employee_id' => $employeeId,
                    'date' => $nonWorkingDay->date->toDateString(),
                    'time_in' => null,
                    'time_out' => null,
                    'hours_worked' => null,
                    'status' => $holidayStatus,
                    'archived' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            foreach ($rows->chunk(100) as $chunk) {
                Attendance::insertOrIgnore($chunk->all());
            }
        });

        return back()->with('success', 'Non-working day saved and attendance logged for all employees.');
    }

    /** DELETE /non-working-days/{nonWorkingDay}  (route: non-working-days.destroy) */
    public function destroy(NonWorkingDay $nonWorkingDay): RedirectResponse
    {
        DB::transaction(function () use ($nonWorkingDay) {
            Attendance::query()
                ->whereDate('date', $nonWorkingDay->date)
                ->where('archived', false)
                ->whereIn('status', Attendance::NON_WORKING_STATUSES)
                ->whereNull('time_in')
                ->whereNull('time_out')
                ->delete();

            $nonWorkingDay->delete();
        });

        return back()->with('success', 'Non-working day removed.');
    }
}