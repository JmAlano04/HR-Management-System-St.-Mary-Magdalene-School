<?php
// app/Http/Controllers/ProfileController.php  (replaces Breeze's; replaces MyAccountController too)

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * GET /profile  (profile.edit)
     * People with an employee record get the full My Account form;
     * users without one (e.g. applicants) keep Breeze's name/email form.
     */
    public function edit(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('profile.edit', [
            'user'     => $request->user(),
            'employee' => $employee,
            'stepName' => $employee ? $this->stepName($employee) : null,
        ]);
    }

    /**
     * PATCH /profile  (profile.update)
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->employee) {
            return $this->updateAccount($request);
        }

        // Stock Breeze behaviour for users without an employee record
        $user->fill(app(ProfileUpdateRequest::class)->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * DELETE /profile  (profile.destroy)
     * Stock Breeze method, except employees can't delete their account here:
     * their payroll, attendance and HR records hang off it.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort_if($request->user()->employee !== null, 403);

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Employees edit their personal details. Employee ID, position, department,
     * role type and employment status stay HR-managed.
     */
    public function updateAccount(Request $request): RedirectResponse
    {
        $user     = $request->user();
        $employee = $user->employee;
        $data     = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'birthday'          => ['required', 'date'],
            'gender'            => ['nullable', 'string'],
            'civil_status'      => ['nullable', 'string'],
            'phone'             => ['nullable', 'string'],
            'address'           => ['nullable', 'string'],
            'emergency_name'    => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string'],
            'education'         => ['nullable', 'string'],
            'sss_number'        => ['nullable', 'string'],
            'philhealth_number' => ['nullable', 'string'],
            'pagibig_number'    => ['nullable', 'string'],
            'tin'               => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($user, $employee, $data) {
            $employee->update([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'birthday'          => $data['birthday'],
                'gender'            => $data['gender'] ?? null,
                'civil_status'      => $data['civil_status'] ?? null,
                'phone'             => $data['phone'] ?? null,
                // These three columns are NOT NULL in the database
                'address'           => $data['address'] ?? '',
                'emergency_name'    => $data['emergency_name'] ?? '',
                'emergency_contact' => $data['emergency_contact'] ?? '',
                'education'         => $data['education'] ?? null,
                'sss_number'        => $data['sss_number'] ?? null,
                'philhealth_number' => $data['philhealth_number'] ?? null,
                'pagibig_number'    => $data['pagibig_number'] ?? null,
                'tin'               => $data['tin'] ?? null,
            ]);

            // The email is the login, so keep the users row in step (like Breeze, re-verify on change)
            $user->fill(['name' => $data['name'], 'email' => $data['email']]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();
        });

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /** Latest payroll step name (payroll_records -> payroll_steps), falling back to the latest payslip. */
    private function stepName(Employee $employee): ?string
    {
        $stepNo = DB::table('payroll_records')
            ->where('employee_id', $employee->id)
            ->orderByDesc('period')
            ->orderByDesc('cutoff')
            ->value('step_no');

        if ($stepNo) {
            return DB::table('payroll_steps')->where('step_no', $stepNo)->value('name') ?: "Step {$stepNo}";
        }

        return DB::table('payslips')
            ->where('employee_id', $employee->id)
            ->latest('id')
            ->value('step_name');
    }
}