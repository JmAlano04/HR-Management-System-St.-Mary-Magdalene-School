{{-- resources/views/layouts/navigation.blade.php (left sidebar, menu depends on the user's role) --}}
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
            'dashboard', 'payroll-processing', 'salary-monitoring', 'salary-records', 'loans', 'accounting-reports',
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