<x-app-layout>

     {{-- Browser Tab Title --}}
    <x-slot name="title">
        Admin Dashboard
    </x-slot>

    <x-slot name="header" name="title">
        
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>

            {{-- Role Badge --}}
            @php
                $user = auth()->user();

                $role = $user->getRoleNames()->first() ?? 'No Role';

                $roleColors = [
                    'Admin' => 'bg-red-100 text-red-800',
                    'HR' => 'bg-blue-100 text-blue-800',
                    'Employee' => 'bg-green-100 text-green-800',
                    'Accounting' => 'bg-purple-100 text-purple-800',
                ];

                $badgeColor = $roleColors[$role]
                    ?? 'bg-gray-100 text-gray-800';
            @endphp

            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $badgeColor }}">
                {{ $role }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <h3 class="text-2xl font-bold mb-2">
                        Welcome, {{ auth()->user()->name }}!
                    </h3>

                    <p class="text-gray-600 mb-4">
                        {{ __("You're logged in!") }}
                    </p>

                    {{-- User Information --}}
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <p class="text-sm text-gray-500">
                            Your Role
                        </p>

                        <p class="text-lg font-semibold mt-1">
                            {{ $role }}
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
```