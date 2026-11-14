<?php

use App\Models\Employee;
use App\Models\TimeCorrection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

function createTimeCorrectionProofEmployee(): User
{
    Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('Employee');

    Employee::create([
        'user_id' => $user->id,
        'employee_id' => 'PROOF-'.$user->id,
        'name' => $user->name,
        'position' => 'Teacher',
        'department' => 'elementary',
        'role_type' => 'teaching',
        'email' => 'proof-'.$user->id.'@example.com',
        'birthday' => '1990-01-01',
        'address' => 'Test address',
        'emergency_name' => 'Emergency Contact',
        'emergency_contact' => '09123456789',
        'employment_status' => 'regular',
    ]);

    return $user;
}

function timeCorrectionProofRequestData(array $overrides = []): array
{
    return array_merge([
        'correction_date' => now()->toDateString(),
        'correction_type' => 'Time In',
        'incorrect_time' => '07:30',
        'correct_time' => '08:00',
        'note' => 'The logbook confirms my arrival time.',
    ], $overrides);
}

test('employee can upload an image proof with a time correction request', function () {
    $employee = createTimeCorrectionProofEmployee();
    $filename = 'IMG_8636_cleanup.png';

    $this->actingAs($employee)
        ->post(route('time-corrections.store'), timeCorrectionProofRequestData([
            'proof' => UploadedFile::fake()->image($filename),
        ]))
        ->assertRedirect(route('time-corrections.employee.index'))
        ->assertSessionHasNoErrors();

    $correction = TimeCorrection::firstOrFail();

    expect($correction->proof_path)->toStartWith('timecorrection/')
        ->and($correction->proof_original_name)->toBe($filename)
        ->and(File::isFile(public_path($correction->proof_path)))->toBeTrue();

    Role::firstOrCreate(['name' => 'HR', 'guard_name' => 'web']);
    $hr = User::factory()->create();
    $hr->assignRole('HR');

    $this->actingAs($hr)
        ->get(route('proof', ['timeCorrection' => $correction]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Content-Disposition', 'inline; filename='.$filename);

    File::delete(public_path($correction->proof_path));
});

test('time correction proof must be a supported image', function () {
    $employee = createTimeCorrectionProofEmployee();

    $this->actingAs($employee)
        ->post(route('time-corrections.store'), timeCorrectionProofRequestData([
            'proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('proof');

    expect(TimeCorrection::count())->toBe(0);
});
