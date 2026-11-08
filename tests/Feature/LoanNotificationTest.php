<?php

use App\Models\Employee;
use App\Models\LoanRequest;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['Admin', 'Accounting', 'Employee', 'HR'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
});

function createLoanTestEmployee(User $user): Employee
{
    return Employee::create([
        'user_id' => $user->id,
        'employee_id' => 'LOAN-'.$user->id,
        'name' => $user->name,
        'position' => 'Teacher',
        'department' => 'elementary',
        'role_type' => 'teaching',
        'email' => 'loan-'.$user->id.'@example.com',
        'birthday' => '1990-01-01',
        'address' => 'Test address',
        'emergency_name' => 'Emergency Contact',
        'emergency_contact' => '09123456789',
    ]);
}

function createLoanTestRequest(Employee $employee): LoanRequest
{
    return LoanRequest::create([
        'employee_id' => $employee->id,
        'loan_type' => 'Cash Advance',
        'amount' => 1500,
        'reason' => 'Emergency expenses',
        'requested_at' => now(),
    ]);
}

test('new loan submissions notify HR, Accounting, and Admin', function () {
    $employeeUser = User::factory()->create();
    $employeeUser->assignRole('Employee');
    $employee = createLoanTestEmployee($employeeUser);
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $accounting = User::factory()->create();
    $accounting->assignRole('Accounting');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($employeeUser)
        ->post(route('loan.loan-employee.store'), [
            'loan_type' => 'Cash Advance',
            'amount' => 1500,
            'reason' => 'Emergency expenses',
        ])
        ->assertRedirect(route('loans.employee.index'));

    expect(LoanRequest::where('employee_id', $employee->id)->count())->toBe(1);
    foreach ([$hr, $admin] as $recipient) {
        $notification = $recipient->notifications()->first();
        expect($notification)->not->toBeNull()
            ->and($notification->data['title'])->toBe('New loan request')
            ->and($notification->data['message'])->toContain('Emergency expenses')
            ->and($notification->data['url'])->toBe(route('loans.index'));
    }

    expect($accounting->notifications()->first()->data['url'])->toBe(route('loans-accounting.index'));
});

test('loan approval advances from HR to Accounting to Admin before notifying the employee', function () {
    $employeeUser = User::factory()->create();
    $employee = createLoanTestEmployee($employeeUser);
    $loan = createLoanTestRequest($employee);
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $accounting = User::factory()->create();
    $accounting->assignRole('Accounting');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($accounting)
        ->patch(route('loans.decide', $loan), ['decision' => 'approved'])
        ->assertSessionHasErrors('decision');

    $this->actingAs($hr)
        ->patch(route('loans.decide', $loan), ['decision' => 'approved'])
        ->assertSessionHasNoErrors();

    expect($loan->fresh()->hr_status)->toBe('approved')
        ->and($accounting->notifications()->count())->toBe(1)
        ->and($admin->notifications()->count())->toBe(1)
        ->and($accounting->notifications()->first()->data['url'])->toBe(route('loans-accounting.index'))
        ->and($admin->notifications()->first()->data['url'])->toBe(route('loans.index'))
        ->and($employeeUser->notifications()->count())->toBe(0);

    $this->actingAs($hr)
        ->patch(route('loans.decide', $loan), ['decision' => 'approved'])
        ->assertSessionHasErrors('decision');

    $this->actingAs($admin)
        ->patch(route('loans.decide', $loan), ['decision' => 'approved'])
        ->assertSessionHasErrors('decision');

    $this->actingAs($accounting)
        ->patch(route('loans.decide', $loan), ['decision' => 'approved'])
        ->assertSessionHasNoErrors();

    expect($loan->fresh()->accounting_status)->toBe('approved')
        ->and($loan->fresh()->admin_status)->toBe('pending')
        ->and($employeeUser->notifications()->count())->toBe(0)
        ->and($admin->notifications()->count())->toBe(2)
        ->and($admin->notifications()->get()->pluck('data')->pluck('title')->contains('Accounting approved a loan request'))->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('loans.decide', $loan), ['decision' => 'approved'])
        ->assertSessionHasNoErrors();

    expect($loan->fresh()->admin_status)->toBe('approved')
        ->and($employeeUser->notifications()->count())->toBe(1)
        ->and($employeeUser->notifications()->first()->data['title'])->toBe('Loan request approved')
        ->and($employeeUser->notifications()->first()->data['url'])->toBe(route('loans.employee.index'));

    $this->actingAs($admin)
        ->patch(route('loans.decide', $loan), ['decision' => 'approved'])
        ->assertSessionHasErrors('decision');

    expect($employeeUser->notifications()->count())->toBe(1)
        ->and(LoanRequest::fullyApproved()->whereKey($loan->id)->exists())->toBeTrue();
});

test('HR rejection sends the reason to Accounting, Admin, and the employee once', function () {
    $employeeUser = User::factory()->create();
    $employee = createLoanTestEmployee($employeeUser);
    $loan = createLoanTestRequest($employee);
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $accounting = User::factory()->create();
    $accounting->assignRole('Accounting');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($hr)
        ->patch(route('loans.decide', $loan), [
            'decision' => 'rejected',
            'reason' => 'Missing supporting documents.',
        ])
        ->assertSessionHasNoErrors();

    foreach ([$accounting, $admin, $employeeUser] as $recipient) {
        expect($recipient->notifications()->count())->toBe(1)
            ->and($recipient->notifications()->first()->data['message'])->toContain('Missing supporting documents.');
    }

    $rejectedLoan = $loan->fresh();
    expect($employeeUser->notifications()->first()->data['title'])->toBe('Loan request rejected')
        ->and($rejectedLoan->hr_rejection_reason)->toBe('Missing supporting documents.')
        ->and($rejectedLoan->accounting_status)->toBe('rejected')
        ->and($rejectedLoan->accounting_rejection_reason)->toBe('Missing supporting documents.')
        ->and($rejectedLoan->admin_status)->toBe('rejected')
        ->and($rejectedLoan->admin_rejection_reason)->toBe('Missing supporting documents.')
        ->and($rejectedLoan->effectiveStatus('accounting'))->toBe('rejected')
        ->and($rejectedLoan->effectiveStatus('admin'))->toBe('rejected');
});

test('Admin cannot decide a loan request before Accounting has approved it', function () {
    $employeeUser = User::factory()->create();
    $loan = createLoanTestRequest(createLoanTestEmployee($employeeUser));
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->patch(route('loans.decide', $loan), ['decision' => 'approved'])
        ->assertSessionHasErrors('decision');

    expect($loan->fresh()->hr_status)->toBe('pending')
        ->and($loan->fresh()->admin_status)->toBe('pending');
});

test('Accounting rejection ends the workflow and notifies the employee with the reason', function () {
    $employeeUser = User::factory()->create();
    $loan = createLoanTestRequest(createLoanTestEmployee($employeeUser));
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $accounting = User::factory()->create();
    $accounting->assignRole('Accounting');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($hr)
        ->patch(route('loans.decide', $loan), ['decision' => 'approved'])
        ->assertSessionHasNoErrors();

    $this->actingAs($accounting)
        ->patch(route('loans.decide', $loan), [
            'decision' => 'rejected',
            'reason' => 'Missing supporting documents.',
        ])
        ->assertSessionHasNoErrors();

    expect($loan->fresh()->accounting_status)->toBe('rejected')
        ->and($loan->fresh()->admin_status)->toBe('rejected')
        ->and($loan->fresh()->admin_rejection_reason)->toBe('Missing supporting documents.')
        ->and($loan->fresh()->effectiveStatus('admin'))->toBe('rejected')
        ->and($admin->notifications()->count())->toBe(2)
        ->and($admin->notifications()->get()->pluck('data')->pluck('title')->contains('Accounting rejected a loan request'))->toBeTrue()
        ->and($admin->notifications()->get()->pluck('data')->pluck('message')->first(fn ($message) => str_contains($message, 'Missing supporting documents.')))->not->toBeNull()
        ->and($employeeUser->notifications()->count())->toBe(1)
        ->and($employeeUser->notifications()->first()->data['title'])->toBe('Loan request rejected')
        ->and($employeeUser->notifications()->first()->data['message'])->toContain('Missing supporting documents.');
});
