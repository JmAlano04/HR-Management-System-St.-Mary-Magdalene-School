
{{-- resources/views/layouts/navigation.blade.php --}}

@php
    $user = auth()->user();

    // Get the user's Spatie role.
    $role = $user?->getRoleNames()->first() ?? 'No Role';

    /*
    |--------------------------------------------------------------------------
    | Sidebar Menu Items
    |--------------------------------------------------------------------------
    */

    $items = [

        // General Dashboard
        'dashboard' => [
            'label' => 'Dashboard',
            'icon'  => 'fa-table-cells',
            'route' => 'dashboard',
            'match' => 'dashboard',
        ],

        // Employee Dashboard
        'employee-dashboard' => [
            'label' => 'Dashboard',
            'icon'  => 'fa-table-cells',
            'route' => 'employee-dashboard',
            'match' => 'employee-dashboard',
        ],

        // Employee Management
        'employees' => [
            'label' => 'Employee Management',
            'icon'  => 'fa-users',
            'route' => 'employees.index',
            'match' => 'employees.*',
        ],

        // Attendance
        'attendance' => [
            'label' => 'Attendance Monitoring',
            'icon'  => 'fa-calendar-check',
            'route' => 'attendance.index',
            'match' => 'attendance.*',
        ],

        // Time Corrections
        'time-corrections' => [
            'label' => 'Time Correction Management',
            'icon'  => 'fa-clock-rotate-left',
            'route' => 'time-corrections.index',
            'match' => 'time-corrections.*',
        ],

        // Leaves
        'leaves' => [
            'label' => 'Leave Management',
            'icon'  => 'fa-calendar-days',
            'route' => 'leaves.index',
            'match' => 'leaves.*',
        ],

        // Onboarding
        'onboarding' => [
            'label' => 'Onboarding Management',
            'icon'  => 'fa-user-plus',
            'route' => 'onboarding.index',
            'match' => 'onboarding.*',
        ],

        // Payroll
        'payroll' => [
            'label' => 'Payroll Management',
            'icon'  => 'fa-wallet',
            'route' => 'payroll.index',
            'match' => 'payroll.*',
        ],

        // Loans
        'loans' => [
            'label' => 'Financial Loan Management',
            'icon'  => 'fa-hand-holding-dollar',
            'route' => 'loans.index',
            'match' => 'loans.*',
        ],

        // Offboarding
        'offboarding' => [
            'label' => 'Offboarding Management',
            'icon'  => 'fa-user-slash',
            'route' => 'offboarding.index',
            'match' => 'offboarding.*',
        ],

        // Documents
        'documents' => [
            'label' => 'Document Management',
            'icon'  => 'fa-folder-open',
            'route' => 'documents.index',
            'match' => 'documents.*',
        ],

        // Reports
        'reports' => [
            'label' => 'Reports',
            'icon'  => 'fa-file',
            'route' => 'reports.index',
            'match' => 'reports.*',
        ],

        // Applicant
        'application' => [
            'label' => 'My Application',
            'icon'  => 'fa-file-signature',
            'route' => 'applications.index',
            'match' => 'applications.*',
        ],

        // Accounting
        'payroll-processing' => [
            'label' => 'Payroll Processing',
            'icon'  => 'fa-peso-sign',
            'route' => 'payroll-accounting.index',
            'match' => 'payroll-accounting.*',
        ],

        'salary-monitoring' => [
            'label' => 'Salary Monitoring',
            'icon'  => 'fa-receipt',
            'route' => 'salary-monitoring.index',
            'match' => 'salary-monitoring.*',
        ],

        'salary-records' => [
            'label' => 'Salary Record Management',
            'icon'  => 'fa-file-invoice',
            'route' => 'salary-records.index',
            'match' => 'salary-records.*',
        ],

        'loans-accounting' => [
            'label' => 'Financial Loan Management',
            'icon'  => 'fa-hand-holding-dollar',
            'route' => 'loans-accounting.index',
            'match' => 'loans-accounting.*',
        ],

        'accounting-reports' => [
            'label' => 'Reports',
            'icon'  => 'fa-chart-line',
            'route' => 'reports.index',
            'match' => 'reports.*',
        ],

        /*
        |--------------------------------------------------------------------------
        | Employee-Only Menu Items
        |--------------------------------------------------------------------------
        */

        'school-employees' => [
            'label' => 'School Employees',
            'icon'  => 'fa-users',
            'route' => 'employee.school-employees',
            'match' => 'employee.school-employees',
        ],

        'my-attendance' => [
            'label' => 'Attendance',
            'icon'  => 'fa-calendar-check',
            'route' => 'attendance.index',
            'match' => 'attendance.*',
        ],

        'my-time-corrections' => [
            'label' => 'Time Corrections',
            'icon'  => 'fa-clock-rotate-left',
            'route' => 'time-corrections.index',
            'match' => 'time-corrections.*',
        ],

        'my-leaves' => [
            'label' => 'Leaves Request',
            'icon'  => 'fa-calendar-days',
            'route' => 'leaves.index',
            'match' => 'leaves.*',
        ],

        'my-loans' => [
            'label' => 'Financial Loans Request',
            'icon'  => 'fa-hand-holding-dollar',
            'route' => 'loans.index',
            'match' => 'loans.*',
        ],

        'my-offboarding' => [
            'label' => 'Offboarding Request',
            'icon'  => 'fa-user-slash',
            'route' => 'offboarding.index',
            'match' => 'offboarding.*',
        ],

        'my-payslips' => [
            'label' => 'Payslip',
            'icon'  => 'fa-file-invoice-dollar',
            'route' => 'salary-records.index',
            'match' => 'salary-records.*',
        ],

        'my-documents' => [
            'label' => 'Documents',
            'icon'  => 'fa-folder-open',
            'route' => 'documents.index',
            'match' => 'documents.*',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | Menus by Role
    |--------------------------------------------------------------------------
    */

    $roleMenus = [

        'Admin' => [
            'dashboard',
            'employees',
            'attendance',
            'time-corrections',
            'leaves',
            'onboarding',
            'payroll',
            'loans',
            'offboarding',
            'documents',
            'reports',
        ],

        'HR' => [
            'dashboard',
            'employees',
            'attendance',
            'time-corrections',
            'leaves',
            'onboarding',
            'payroll',
            'loans',
            'documents',
            'offboarding',
            'reports',
        ],

        'Accounting' => [
            'dashboard',
            'payroll-processing',
            'salary-monitoring',
            'salary-records',
            'loans-accounting',
            'accounting-reports',
        ],

        'Employee' => [
            'employee-dashboard',
            'school-employees',
            'my-attendance',
            'my-time-corrections',
            'my-leaves',
            'my-loans',
            'my-offboarding',
            'my-payslips',
            'my-documents',
        ],

        'Applicant' => [
            'dashboard',
            'application',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | Build Menu Safely
    |--------------------------------------------------------------------------
    */

    $menuKeys = $roleMenus[$role] ?? ['dashboard'];

    $menu = collect($menuKeys)
        ->map(fn ($key) => $items[$key] ?? null)
        ->filter()
        ->values()
        ->all();

    /*
    |--------------------------------------------------------------------------
    | Choose the Dashboard Link for the Logo
    |--------------------------------------------------------------------------
    */

    $homeRoute = (
        $role === 'Employee'
        && Route::has('employee-dashboard')
    )
        ? 'employee-dashboard'
        : 'dashboard';

    $homeUrl = Route::has($homeRoute)
        ? route($homeRoute)
        : url('/');
@endphp

{{-- Mobile Sidebar Overlay --}}
<div
    x-show="sidebarOpen"
    x-transition.opacity
    @click="sidebarOpen = false"
    class="fixed inset-0 z-30 bg-black/40 lg:hidden"
    style="display: none;"
></div>

{{-- Sidebar --}}
<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    @keydown.escape.window="sidebarOpen = false"
    class="fixed inset-y-0 left-0 z-40 flex w-90 flex-col
           overflow-y-auto border-r border-red-100 bg-red-50
           transition-transform duration-200 lg:translate-x-0
           [-ms-overflow-style:none] [scrollbar-width:none]
           [&::-webkit-scrollbar]:hidden"
>
    {{-- Mobile Close Button --}}
    <button
        type="button"
        @click="sidebarOpen = false"
        class="absolute right-3 top-3 text-red-900 lg:hidden"
        aria-label="Close menu"
    >
        <i class="fa-solid fa-xmark text-xl"></i>
    </button>

    {{-- School Logo and Name --}}
    <div class="flex flex-col items-center px-4 py-6">
        <a href="{{ $homeUrl }}">
            <img
                src="{{ asset('image/SMMS Logo.png') }}"
                alt="School Logo"
                class="h-20 w-auto"
            >
        </a>

        <h2 class="mt-4 text-center text-base font-bold text-gray-900">
            St. Mary Magdalene School
        </h2>
    </div>

    {{-- Navigation Links --}}
    <nav class="flex-1 space-y-1">
        @foreach ($menu as $item)
            <x-sidebar-link
                :href="Route::has($item['route'])
                    ? route($item['route'])
                    : '#'"
                :icon="$item['icon']"
                :active="request()->routeIs($item['match'])"
            >
                {{ $item['label'] }}
            </x-sidebar-link>
        @endforeach
    </nav>
</aside>
