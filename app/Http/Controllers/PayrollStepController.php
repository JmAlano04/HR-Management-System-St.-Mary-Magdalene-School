<?php

namespace App\Http\Controllers;

use App\Models\PayrollStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollStepController extends Controller
{
    /** Add a new step (e.g. Step 31). */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeHr();

        // Errors go to the "step" bag so they don't mix with the employee form errors
        $data = $request->validateWithBag('step', [
            'step_no'    => ['required', 'integer', 'min:1', 'max:999', 'unique:payroll_steps,step_no'],
            'name'       => ['required', 'string', 'max:255'],
            'daily_wage' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        PayrollStep::create($data);

        return redirect()->route('employees.index')
            ->with('success', 'Step added.')
            ->with('steps_open', true);
    }

    /** Update the name / daily wage of a step. The step number stays fixed because employees use it. */
    public function update(Request $request, PayrollStep $payrollStep): RedirectResponse
    {
        $this->authorizeHr();

        $data = $request->validateWithBag('step', [
            'name'       => ['required', 'string', 'max:255'],
            'daily_wage' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        $payrollStep->update($data);

        return redirect()->route('employees.index')
            ->with('success', $payrollStep->name . ' updated.')
            ->with('steps_open', true);
    }

    /** Same rule as the employee page: only HR can change data. */
    private function authorizeHr(): void
    {
        $user = Auth::user();
        abort_unless(
            $user !== null
                && is_callable([$user, 'hasRole'])
                && call_user_func([$user, 'hasRole'], 'HR'),
            403
        );
    }
}