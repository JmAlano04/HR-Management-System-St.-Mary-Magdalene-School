<?php

use App\Models\Employee;
use App\Models\SchoolEmployee;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

it('reads legacy plaintext employee data and continues encrypting new values', function (): void {
    $employee = new Employee;
    $employee->setRawAttributes([
        'id'                => 1,
        'employee_id'       => 'EMP-001',
        'name'              => 'Maria Santos',
        'position'          => 'Teacher',
        'department'        => 'elementary',
        'role_type'         => 'Teaching Staff',
        'email'             => 'maria@example.com',
        'birthday'          => '1990-01-01',
        'address'           => '123 School Road',
        'emergency_name'    => 'Juan Santos',
        'emergency_contact' => '09123456789',
    ], true);

    expect($employee->toCard())
        ->toHaveKey('address', '123 School Road')
        ->toHaveKey('emergency_name', 'Juan Santos')
        ->toHaveKey('emergency_contact', '09123456789');

    $employee->address = '456 New Road';

    expect($employee->getAttributes()['address'])
        ->not->toBe('456 New Road')
        ->and($employee->address)->toBe('456 New Road');
});

it('reads legacy plaintext with the school employee model', function (): void {
    $employee = new SchoolEmployee;
    $employee->setRawAttributes(['address' => '123 School Road'], true);

    expect($employee->address)->toBe('123 School Road');
});

it('omits invalid encrypted employee data and logs the decryption failure', function (): void {
    $payload = json_decode(base64_decode(Crypt::encryptString('private data')), true);
    $payload['mac'] = str_repeat('0', strlen($payload['mac']));

    $employee = new Employee;
    $employee->setRawAttributes([
        'id'      => 123,
        'address' => base64_encode(json_encode($payload)),
    ], true);

    Log::shouldReceive('warning')
        ->once()
        ->with('Unable to decrypt employee data; omitting the affected value.', [
            'model'     => Employee::class,
            'model_id'  => 123,
            'attribute' => 'address',
            'error'     => 'The MAC is invalid.',
        ]);

    expect($employee->address)->toBeNull();
});
