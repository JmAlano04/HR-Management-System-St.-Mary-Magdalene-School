<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

test('a submitted application notifies HR and Admin with applicant and application details', function () {
    Storage::fake('public');

    $applicantRole = Role::firstOrCreate(['name' => 'Applicant', 'guard_name' => 'web']);
    $hrRole = Role::firstOrCreate(['name' => 'HR', 'guard_name' => 'web']);
    $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

    $applicant = User::factory()->create();
    $applicant->assignRole($applicantRole);
    $hr = User::factory()->create();
    $hr->assignRole($hrRole);
    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    $this->actingAs($applicant)
        ->post(route('applications.store'), [
            'first_name' => 'Alex',
            'middle_name' => 'Marie',
            'last_name' => 'Applicant',
            'email' => 'alex.applicant@example.com',
            'phone' => '09123456789',
            'address_line' => '12 Sample Street',
            'barangay' => 'Central',
            'city_municipality' => 'Manila',
            'province' => 'Metro Manila',
            'zip_code' => '1000',
            'birth_date' => '1990-01-01',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'education' => "Bachelor's Degree",
            'position' => 'Teaching Position',
            'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
            'coverletter' => UploadedFile::fake()->create('cover-letter.pdf', 100, 'application/pdf'),
            'appletter' => UploadedFile::fake()->create('application-letter.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('applications.index'));

    $this->assertDatabaseCount('notifications', 2);
    $this->assertDatabaseHas('applications', [
        'first_name' => 'Alex',
        'middle_name' => 'Marie',
        'last_name' => 'Applicant',
    ]);

    foreach ([$hr, $admin] as $recipient) {
        $notification = $recipient->notifications()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->data['title'])->toBe('New applicant')
            ->and($notification->data['message'])->toContain('Alex Marie Applicant')
            ->and($notification->data['message'])->toContain('Teaching Position (Teaching)')
            ->and($notification->data['message'])->toContain('alex.applicant@example.com')
            ->and($notification->data['message'])->toContain('09123456789')
            ->and($notification->data['message'])->toContain("Bachelor's Degree")
            ->and($notification->data['message'])->toContain('resume.pdf')
            ->and($notification->data['url'])->toBe(route('onboarding.index'));
    }

    $this->actingAs($hr)
        ->get(route('employees.index'))
        ->assertOk()
        ->assertSee('Alex Marie Applicant')
        ->assertSee('Alex')
        ->assertSee('Marie')
        ->assertSee('Applicant')
        ->assertSee('1990-01-01')
        ->assertSee('Female')
        ->assertSee('12 Sample Street');

    $this->actingAs($hr)
        ->post(route('employees.store'), [
            'employee_source' => 'applicant',
            'user_id' => $applicant->id,
            'first_name' => 'Edited',
            'middle_name' => 'New',
            'last_name' => 'Name',
            'suffix' => '',
            'employee_id' => '',
            'position' => 'Teacher',
            'department' => 'seniorhigh',
            'role_type' => 'Teaching Staff',
            'employment_status' => 'regular',
            'step_no' => 1,
            'email' => '',
            'birthday' => '1990-01-01',
            'gender' => 'Female',
            'address_line' => '12 Sample Street',
            'address_landmark' => '',
            'barangay' => 'Central',
            'city_municipality' => 'Manila',
            'province' => 'Metro Manila',
            'zip_code' => '1000',
            'emergency_name' => 'Emergency Contact',
            'emergency_contact' => '09123456789',
        ])
        ->assertRedirect(route('employees.index'));

    $this->assertDatabaseHas('employees', [
        'user_id' => $applicant->id,
        'name' => 'Edited New Name',
        'email' => 'ename@smms.edu',
    ]);

    expect($applicant->fresh()->name)->toBe('Edited New Name');
});
