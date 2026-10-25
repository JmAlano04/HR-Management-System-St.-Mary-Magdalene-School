<?php

namespace App\Http\Controllers;

use App\Models\NonWorkingDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        NonWorkingDay::create([
            'date'       => $data['date'],
            'name'       => trim($data['name']),
            'category'   => $data['category'],
            'pay_type'   => $data['pay_type'],
            'paid_days'  => $isPaid ? ($data['paid_days'] ?? 1) : 0,
            'paid_hours' => $isPaid ? ($data['paid_hours'] ?? 8) : 0,
        ]);

        return back()->with('success', 'Non-working day saved.');
    }

    /** DELETE /non-working-days/{nonWorkingDay}  (route: non-working-days.destroy) */
    public function destroy(NonWorkingDay $nonWorkingDay): RedirectResponse
    {
        $nonWorkingDay->delete();

        return back()->with('success', 'Non-working day removed.');
    }
}