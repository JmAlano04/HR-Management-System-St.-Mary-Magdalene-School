<?php

use App\Models\Employee;
use App\Models\OffboardingCase;
use App\Models\User;
use App\Services\HrmsNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['Admin', 'Employee', 'HR'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
});

function createOffboardingNotificationEmployee(User $user): Employee
{
    return Employee::create([
        'user_id' => $user->id,
        'employee_id' => 'OFFBOARDING-'.$user->id,
        'name' => $user->name,
        'position' => 'Teacher',
        'department' => 'elementary',
        'role_type' => 'teaching',
        'email' => 'offboarding-'.$user->id.'@example.com',
        'birthday' => '1990-01-01',
        'address' => 'Test address',
        'emergency_name' => 'Emergency Contact',
        'emergency_contact' => '09123456789',
    ]);
}

test('offboarding request, submitted documents, handoff, and final approval notify the right people', function () {
    Storage::fake('local');
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $employeeUser = User::factory()->create();
    $employeeUser->assignRole('Employee');
    $employee = createOffboardingNotificationEmployee($employeeUser);

    $this->actingAs($employeeUser)
        ->post(route('employee.offboarding.store'), [
            'last_working_day' => now()->addMonth()->toDateString(),
            'employee_message' => 'Thank you.',
        ])
        ->assertRedirect(route('offboarding.employee.index'));

    $case = OffboardingCase::where('employee_id', $employee->id)->firstOrFail();
    expect($hr->notifications()->count())->toBe(1)
        ->and($hr->notifications()->first()->data['title'])->toBe('New offboarding request');

    $this->actingAs($hr)
        ->patch(route('offboarding.requirements', $case), [
            'requirements_text' => "Letter of Intent\nDICES Exit Interview",
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($hr)
        ->patch(route('offboarding.interviews.complete', $case), [
            'label' => 'DICES Exit Interview',
        ])
        ->assertSessionHasNoErrors();

    expect($employeeUser->notifications()->latest()->first()->data['title'])
        ->toBe('Offboarding interview clearance completed')
        ->and($employeeUser->notifications()->latest()->first()->data['message'])
        ->toContain('DICES Exit Interview');

    $this->actingAs($employeeUser)
        ->post(route('employee.offboarding.documents.store', $case), [
            'documents' => [
                'Letter of Intent' => UploadedFile::fake()->createWithContent('letter.pdf', 'test document'),
            ],
        ])
        ->assertSessionHasNoErrors();

    expect($hr->notifications()->count())->toBe(2)
        ->and($hr->notifications()->latest()->first()->data['title'])->toBe('Offboarding requirement submitted')
        ->and($hr->notifications()->latest()->first()->data['message'])->toContain($employee->name, 'Letter of Intent');

    $document = $case->documents()->firstOrFail();
    $this->actingAs($hr)
        ->patch(route('offboarding.documents.review', [$case, $document]), [
            'decision' => 'approved',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($hr)
        ->patch(route('offboarding.forward', $case))
        ->assertSessionHasNoErrors();

    expect($admin->notifications()->count())->toBe(1)
        ->and($admin->notifications()->first()->data['title'])->toBe('Offboarding needs final approval');

    $this->actingAs($admin)
        ->patch(route('offboarding.approve', $case))
        ->assertSessionHasNoErrors();

    expect($hr->notifications()->count())->toBe(3)
        ->and($hr->notifications()->latest()->first()->data['title'])->toBe('Admin approved an offboarding request')
        ->and($employeeUser->notifications()->pluck('data')->pluck('title'))
        ->toContain('Offboarding completed');
});

test('HR can reject an offboarding request and the employee is notified once with the reason', function () {
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $employeeUser = User::factory()->create();
    $employee = createOffboardingNotificationEmployee($employeeUser);
    $case = OffboardingCase::create([
        'employee_id' => $employee->id,
        'reason' => 'Resignation',
        'last_working_day' => now()->addMonth()->toDateString(),
        'status' => 'Open',
        'progress' => 10,
    ]);

    $this->actingAs($hr)
        ->patch(route('offboarding.reject', $case), [
            'reason' => 'The request needs clarification.',
        ])
        ->assertSessionHasNoErrors();

    HrmsNotifier::offboardingHrRejected($case->fresh(), 'The request needs clarification.');

    expect($case->fresh()->status)->toBe('Rejected')
        ->and($employeeUser->notifications()->count())->toBe(1)
        ->and($employeeUser->notifications()->first()->data['message'])
        ->toContain('rejected by HR', 'The request needs clarification.');
});
