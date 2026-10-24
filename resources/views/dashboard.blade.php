{{-- resources/views/dashboard.blade.php --}}
@php
    $user = auth()->user();

    $role = $user->getRoleNames()->first() ?? 'No Role';

@endphp

@if (in_array($role, ['Admin', 'HR', 'Accounting','Employee']))
<x-app-layout>


    {{-- Page heading --}}
    <x-slot name="header">
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight ml-[80px] text-red-700">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="pb-12  ml-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
            
                        {{-- Role-specific message --}}
                        @switch($role)
                            @case('Admin')
                          
                                   @include('partial.admin-dashboard')
                       
                                @break

                            @case('HR')
                                <p class="mt-2 text-blue-700">
                                    @include('partial.admin-dashboard')
                                </p>
                                @break

                            @case('Accounting')
                            
                                <p class="mt-2 text-purple-700">
                                    @include('partial.accounting-dashboard')
                                </p>
                                @break

                            @case('Employee')
                                <p class="mt-2 text-green-700">
                                    @include('partial.Employee.employees-dashboard')
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
@else

<x-app-layout>



                        {{-- Role-specific message --}}
                        @switch($role)

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
                 


</x-app-layout>

@endif