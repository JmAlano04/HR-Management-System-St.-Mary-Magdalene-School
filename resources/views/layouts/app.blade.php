{{-- resources/views/layouts/app.blade.php --}}
@php
    $user = Auth::user();

    // Uses Spatie roles, same as your dashboard
    $role = $user->getRoleNames()->first() ?? 'No Role';

    // "Daniel Reyes" -> "DR"
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->take(2)
        ->implode('');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'HR Management System') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Icons (Font Awesome) used by the sidebar -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
     

    </head>
    <body class="font-sans antialiased">
        {{-- sidebarOpen controls the sidebar on mobile (shared with layouts.navigation) --}}
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-gray-100">

            @auth

                {{-- Applicant at Employee: top navigation --}}
                @hasanyrole('Applicant|Employee')
                    @include('layouts.applicant-nav')
                @endhasanyrole

                {{-- Admin, HR, Accounting: left sidebar navigation --}}
                @hasanyrole('Admin|HR|Accounting')
                    @include('layouts.navigation')
          
         

            {{-- Everything on the right; lg:pl-64 leaves room for the fixed sidebar --}}
            <div class="lg:pl-64">

                <!-- Top bar: hamburger (mobile) + page heading on the left, profile dropdown on the right -->
                <div class="max-w-7xl mx-auto flex items-center justify-between gap-4 px-4 py-6 sm:px-6 lg:px-8">

                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" @click="sidebarOpen = true" class="text-red-900 lg:hidden" aria-label="Open menu">
                            <i class="fa-solid fa-bars text-xl"></i>
                        </button>

                        <!-- Page Heading -->
                        @isset($header)
                            <div class="min-w-0">
                                {{ $header }}
                            </div>
                        @endisset
                    </div>

                    <!-- Profile dropdown -->
                    <x-dropdown align="right" width="48"  content-classes=" p-2 bg-white">
                        <x-slot name="trigger">
                            <button type="button"
                                    class="inline-flex items-center gap-3 mr-[70px] rounded-full border border-gray-200 bg-white py-2 pl-2 pr-4 text-lg shadow-sm transition hover:bg-gray-50 focus:outline-none">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-red-700 text-sm font-bold text-white">
                                    {{ $initials }}
                                </span>

                                <span class="hidden text-left leading-tight sm:block">
                                    <span class="block font-semibold text-gray-900">{{ $user->name }}</span>
                                    <span class="block text-xs text-gray-500">
                                       {{ $user->getRoleNames()->first() ?? 'No role assigned' }}
                                    </span>
                                </span>

                                <i class="fa-solid fa-chevron-down text-xs text-gray-500"></i>
                            </button>
                        </x-slot>

                        <x-slot name="content">{{-- resources/views/layouts/navigation.blade.php (left sidebar, menu depends on the user's role) --}}
@php
    $user = auth()->user();

    // Spatie role name, e.g. Admin, HR, Accounting, Employee, Applicant
    $role = $user->getRoleNames()->first() ?? 'No Role';

    /*
     * All possible menu items, defined once.
     * 'route' = route name, 'match' = pattern used to highlight the active link.
     * If a route does not exist yet the link falls back to "#" so nothing crashes.
     */
   $items = [
        'dashboard'        => ['label' => 'Dashboard',                  'icon' => 'fa-table-cells',         'route' => 'dashboard',              'match' => 'dashboard'],
        'employees'        => ['label' => 'Employee Management',        'icon' => 'fa-users',               'route' => 'employees.index',        'match' => 'employees.*'],
        'attendance'       => ['label' => 'Attendance Monitoring',      'icon' => 'fa-calendar-check',      'route' => 'attendance.index',       'match' => 'attendance.*'],
        'time-corrections' => ['label' => 'Time Correction Management', 'icon' => 'fa-clock-rotate-left',   'route' => 'time-corrections.index', 'match' => 'time-corrections.*'],
        'leaves'           => ['label' => 'Leave Management',           'icon' => 'fa-calendar-days',       'route' => 'leaves.index',           'match' => 'leaves.*'],
        'onboarding'       => ['label' => 'Onboarding Management',     'icon' => 'fa-user-plus',           'route' => 'onboarding.index',       'match' => 'onboarding.*'],
        'payroll'          => ['label' => 'Payroll Management',         'icon' => 'fa-wallet',              'route' => 'payroll.index',          'match' => 'payroll.*'],
        'loans'            => ['label' => 'Financial Loan Management',  'icon' => 'fa-hand-holding-dollar', 'route' => 'loans.index',            'match' => 'loans.*'],
        'offboarding'      => ['label' => 'Offboarding Management',     'icon' => 'fa-user-slash',          'route' => 'offboarding.index',      'match' => 'offboarding.*'],
        'Document Management' => ['label' => 'Document Management',     'icon'  => 'fa-folder-open',          'route' => 'documents.index',      'match' => 'documents.*'],
        'reports'          => ['label' => 'Reports',                    'icon' => 'fa-file',                'route' => 'reports.index',          'match' => 'reports.*'],
        'application'      => ['label' => 'My Application',             'icon' => 'fa-file-signature',      'route' => 'applications.index',     'match' => 'applications.*'],
        
        
         // Accounting-only items
        'payroll-processing' => ['label' => 'Payroll Processing',        'icon' => 'fa-peso-sign',         'route' => 'payroll-accounting.index',          'match' => 'payroll-accounting.*'],
        'salary-monitoring'  => ['label' => 'Salary Monitoring',         'icon' => 'fa-receipt',           'route' => 'salary-monitoring.index','match' => 'salary-monitoring.*'],
        'salary-records'     => ['label' => 'Salary Record Management',  'icon' => 'fa-file-invoice',      'route' => 'salary-records.index',   'match' => 'salary-records.*'],
        'loans-accounting' => ['label' => 'Financial Loan Management',                   'icon' => 'fa-hand-holding-dollar',        'route' => 'loans-accounting.index',          'match' => 'loans-accounting.*'],
        'accounting-reports' => ['label' => 'Reports',                   'icon' => 'fa-chart-line',        'route' => 'reports.index',          'match' => 'reports.*'],
 
        ];

    /*
     * Which items each role can see. Edit these lists to change a role's menu.
     */
    $roleMenus = [
        'Admin' => [
            'dashboard', 'employees', 'attendance', 'time-corrections', 'leaves',
            'onboarding', 'payroll', 'loans', 'offboarding', 'reports',
        ],
        'HR' => [
            'dashboard', 'employees', 'attendance', 'time-corrections', 'leaves',
            'onboarding', 'payroll', 'loans', 'Document Management','offboarding', 'reports',
        ],
        'Accounting' => [
            'dashboard', 'payroll-processing', 'salary-monitoring', 'salary-records', 'loans-accounting', 'accounting-reports',
        ],
        'Employee' => [
            'dashboard', 'attendance', 'time-corrections', 'leaves', 'payroll', 'loans',
        ],
        'Applicant' => [
            'dashboard', 'application',
        ],
        
    ];

    // Unknown role or no role: Dashboard only
    $menu = collect($roleMenus[$role] ?? ['dashboard'])
        ->map(fn ($key) => $items[$key])
        ->all();
@endphp

{{-- Dark overlay behind the sidebar on mobile --}}
<div
    x-show="sidebarOpen"
    x-transition.opacity
    @click="sidebarOpen = false"
    class="fixed inset-0 z-30 bg-black/40 lg:hidden"
    style="display: none;"
></div>

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    @keydown.escape.window="sidebarOpen = false"
    class="fixed inset-y-0 left-0 z-40 flex w-90 flex-col overflow-y-auto border-r border-red-100 bg-red-50 transition-transform duration-200 lg:translate-x-0 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
>
    {{-- Close button (mobile only) --}}
    <button
        type="button"
        @click="sidebarOpen = false"
        class="absolute right-3 top-3 text-red-900 lg:hidden"
        aria-label="Close menu"
    >
        <i class="fa-solid fa-xmark text-xl"></i>
    </button>

    {{-- Logo and school name --}}
    <div class="flex flex-col items-center px-4 py-6">
        <a href="{{ route('dashboard') }}">
            <img src="{{ asset('image/SMMS Logo.png') }}" alt="School Logo" class="h-20 w-auto">
        </a>

        <h2 class="mt-4 text-center text-base font-bold text-gray-900">
            St. Mary Magdalene School
        </h2>
    </div>

    {{-- Links --}}
    <nav class="flex-1 space-y-1">
        @foreach ($menu as $item)
            <x-sidebar-link
                :href="Route::has($item['route']) ? route($item['route']) : '#'"
                :icon="$item['icon']"
                :active="request()->routeIs($item['match'])"
            >
                {{ $item['label'] }}
            </x-sidebar-link>
        @endforeach
    </nav>
</aside>
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <x-dropdown-link href="#">
                                {{ __('Help') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

      @endhasanyrole
    @endauth
                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>