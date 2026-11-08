{{-- resources/views/dashboard.blade.php --}}
@php
    $user = auth()->user();

    $role = $user->getRoleNames()->first() ?? 'No Role';

@endphp

<x-app-layout>


    {{-- Page heading --}}
    <x-slot name="header">
<<<<<<< HEAD
           <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-4xl font-extrabold leading-tight ml-0 lg:ml-[80px] text-red-700">
=======
           <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-4xl font-extrabold leading-tight ml-[50px] text-red-700">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
         {{ __('Financial Loan Management') }}
        </h2>
    </x-slot>

<<<<<<< HEAD
    <div class="pb-12 ml-4 -mt-8">
=======
    <div class="pb-12  ml-4">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
            
                        {{-- Role-specific message --}}
                        @switch($role)
                            @case('Admin')
                          
                                   @include('partial.loan-dashboard')
                       
                                @break

                            @case('HR')
<<<<<<< HEAD
                                @include('partial.loan-dashboard')
=======
                                <p class="mt-2 text-blue-700">
                                    @include('partial.loan-dashboard')
                                </p>
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                                @break

                            @case('Employee')
                                <p class="mt-2 text-green-700">
                                    @include('partial.Employee.loan-employees')
                                </p>
                                @break

                            @case('Accounting')
                                <p class="mt-2 text-purple-700">
                                     @include('partial.Accounting.loan-accounting')
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