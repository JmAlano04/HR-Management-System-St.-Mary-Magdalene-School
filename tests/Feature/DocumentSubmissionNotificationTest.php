<?php

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\SubmissionBox;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['Employee', 'HR'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
});

function createDocumentNotificationEmployee(User $user): Employee
{
    return Employee::create([
        'user_id' => $user->id,
        'employee_id' => 'DOCUMENT-'.$user->id,
        'name' => $user->name,
        'position' => 'Teacher',
        'department' => 'elementary',
        'role_type' => 'teaching',
        'email' => 'document-'.$user->id.'@example.com',
        'birthday' => '1990-01-01',
        'address' => 'Test address',
        'emergency_name' => 'Emergency Contact',
        'emergency_contact' => '09123456789',
    ]);
}

test('creating a submission box notifies the assigned employee', function () {
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $employeeUser = User::factory()->create();
    $employeeUser->assignRole('Employee');
    $employee = createDocumentNotificationEmployee($employeeUser);

    $this->actingAs($hr)
        ->post(route('boxes.store'), [
            'name' => 'NBI Clearance',
            'type' => 'NBI',
            'employee_id' => $employee->id,
        ])
        ->assertRedirect(route('documents.index'));

    $notification = $employeeUser->notifications()->first();

    expect($employeeUser->notifications()->count())->toBe(1)
        ->and($notification->data['title'])->toBe('Documents requested')
        ->and($notification->data['message'])->toContain('NBI Clearance')
        ->and($notification->data['url'])->toBe(route('documents.employee.index'));
});

test('document upload notifies HR with employee, file, and submission date', function () {
    Storage::fake('local');
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $employeeUser = User::factory()->create();
    $employeeUser->assignRole('Employee');
    $employee = createDocumentNotificationEmployee($employeeUser);
    $box = SubmissionBox::create([
        'name' => 'NBI Clearance',
        'type' => 'NBI',
    ]);

    $this->actingAs($employeeUser)
        ->post(route('employee.documents.store'), [
            'submission_box_id' => $box->id,
            'file' => UploadedFile::fake()->createWithContent('clearance.pdf', 'test document'),
        ])
        ->assertSessionHasNoErrors();

    $message = $hr->notifications()->first()->data['message'];

    expect($hr->notifications()->count())->toBe(1)
        ->and($message)->toContain($employee->name)
        ->and($message)->toContain('clearance.pdf')
        ->and($message)->toContain('NBI Clearance')
        ->and($message)->toContain(now()->format('M d, Y'))
        ->and($hr->notifications()->first()->data['from'])->toBe($employeeUser->name);

    $document = EmployeeDocument::firstOrFail();
    $this->actingAs($hr)
        ->patch(route('hr.documents.decide'), [
            'document_id' => $document->id,
            'decision' => EmployeeDocument::APPROVED,
        ])
        ->assertSessionHasNoErrors();

    expect($employeeUser->notifications()->count())->toBe(1)
        ->and($employeeUser->notifications()->first()->data['title'])->toBe('Submitted document approved')
        ->and($employeeUser->notifications()->first()->data['message'])->toContain('DOC-'.$document->id)
        ->and($employeeUser->notifications()->first()->data['from'])->toBe($hr->name);
});
