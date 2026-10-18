{{-- resources/views/dashboard.blade.php --}}
<x-app-layout>

    {{-- Browser tab title --}}
    <x-slot name="title">
        Financial Loan Dashboard
    </x-slot>

    {{-- Page heading --}}
    <x-slot name="header">
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-2xl font-extrabold leading-tight ml-[130px] text-red-700">
            {{ __('Financial Loan Management') }}
        </h2>
    </x-slot>

    @php
        $role = auth()->user()->getRoleNames()->first() ?? 'No Role';
    @endphp

    <div class="pb-12 ml-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Role-specific dashboard --}}
                    @switch($role)
                        @case('Admin')
                            @include('partial.offboarding-dashboard')
                            @break

                        @case('HR')
                            @include('partial.offboarding-dashboard')
                            @break

                        @case('Accounting')
                            @include('partial.accounting-dashboard')
                            @break

                        @case('Employee')
                            @include('partial.employee-dashboard')
                            @break

                        @case('Applicant')
                            @include('partial.applicant-dashboard')
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
</x-app-layout>