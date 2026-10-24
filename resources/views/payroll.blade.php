{{-- resources/views/dashboard.blade.php --}}
@php
    $user = auth()->user();

    $role = $user->getRoleNames()->first() ?? 'No Role';

@endphp

<x-app-layout>


    

    
   @auth
    <x-slot name="title">
        @if (auth()->user()->hasRole('Admin'))
            {{-- Page heading --}}
            <x-slot name="header">
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight ml-[130px]  text-red-700">
                    {{ __('Payroll Management') }}
                </h2>
            </x-slot>

        @elseif (auth()->user()->hasRole('HR'))
                 {{-- Page heading --}}
            <x-slot name="header">
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight ml-[130px]  text-red-700">
                    {{ __('Payroll Management') }}
                </h2>
            </x-slot>
        @elseif (auth()->user()->hasRole('Accounting'))
                 {{-- Page heading --}}
            <x-slot name="header">
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight ml-[130px]  text-red-700">
                    {{ __('Payroll Management') }}
                </h2>
            </x-slot>
        @elseif (auth()->user()->hasRole('Employee'))
                 {{-- Page heading --}}
            <x-slot name="header">
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight ml-[90px]  text-red-700">
                    {{ __('Payslip') }}
                </h2>
            </x-slot>
        @else
            Dashboard
        @endif
    </x-slot>
  @endauth


    <div class="pb-12  ml-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
            
                        {{-- Role-specific message --}}
                        @switch($role)
                            @case('Admin')
                          
                                   @include('partial.payroll-dashboard')
                       
                                @break

                            @case('HR')
                                <p class="mt-2 text-blue-700">
                                    @include('partial.payroll-dashboard')
                                </p>
                                @break

                            @case('Employee')
                                <p class="mt-2 text-green-700">
                                    @include('partial.Employee.payslip-employee')
                                </p>
                                @break

                            @case('Accounting')
                                <p class="mt-2 text-purple-700">
                                    @include('partial.accounting-dashboard')
                                </p>
                                @break

                            @case('Applicant')
                                <p class="mt-2 text-yellow-700">
                                    @include('partial.applicant-dashboard')
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
</x-app-layout>