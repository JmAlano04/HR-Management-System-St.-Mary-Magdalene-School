{{-- resources/views/dashboard.blade.php --}}
@php
    $user = auth()->user();

    $role = $user->getRoleNames()->first() ?? 'No Role';

    $roleColors = [
        'Admin'      => 'bg-red-100 text-red-800',
        'HR'         => 'bg-blue-100 text-blue-800',
        'Employee'   => 'bg-green-100 text-green-800',
        'Accounting' => 'bg-purple-100 text-purple-800',
        'Applicant'  => 'bg-yellow-100 text-yellow-800',
    ];

    $badgeColor = $roleColors[$role] ?? 'bg-gray-100 text-gray-800';
@endphp

<x-app-layout>

    {{-- Browser tab title --}}
    <x-slot name="title">
        Admin Dashboard
    </x-slot>

    {{-- Page heading (the profile dropdown now lives in layouts/app.blade.php) --}}
    <x-slot name="header">
        <h2 class="text-5xl font-extrabold leading-tight text-red-700">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <h3 class="text-2xl font-bold mb-2">
                        Welcome, {{ $user->name }}!
                    </h3>

                    <p class="text-gray-600 mb-4">
                        {{ __("You're logged in!") }}
                    </p>

                    {{-- User information --}}
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <p class="text-sm text-gray-500">
                            Your Role
                        </p>

                        <p class="mt-1">
                            <span class="inline-block rounded-full px-3 py-1 text-lg font-semibold {{ $badgeColor }}">
                                {{ $role }}
                            </span>
                        </p>

                        {{-- Role-specific message --}}
                        @switch($role)
                            @case('Admin')
                                <p class="mt-2 text-red-700">
                                    You have administrator access.
                                </p>
                                @break

                            @case('HR')
                                <p class="mt-2 text-blue-700">
                                    Welcome to the HR Management Dashboard.
                                </p>
                                @break

                            @case('Employee')
                                <p class="mt-2 text-green-700">
                                    Welcome to your Employee Dashboard.
                                </p>
                                @break

                            @case('Accounting')
                                <p class="mt-2 text-purple-700">
                                    Welcome to the Accounting Dashboard.
                                </p>
                                @break

                            @case('Applicant')
                                <p class="mt-2 text-yellow-700">
                                    You are currently an applicant. Please wait for further instructions.
                                </p>
                                @break

                            @default
                                <p class="mt-2 text-gray-600">
                                    No role has been assigned to your account.
                                </p>
                        @endswitch
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>