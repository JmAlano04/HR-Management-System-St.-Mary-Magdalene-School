{{-- resources/views/layouts/navigation.blade.php (left sidebar) --}}
@php
    /*
     * Menu items. 'route' is the route name, 'match' is the pattern used to
     * highlight the active link. If a route does not exist yet, the link
     * falls back to "#" so the page does not crash. Rename these to match
     * your real routes.
     */
    $menu = [
        ['label' => 'Dashboard',                 'icon' => 'fa-table-cells',          'route' => 'dashboard',          'match' => 'dashboard'],
        ['label' => 'Employee Management',       'icon' => 'fa-users',                'route' => 'employees.index',    'match' => 'employees.*'],
        ['label' => 'Attendance Monitoring',     'icon' => 'fa-calendar-check',       'route' => 'attendance.index',   'match' => 'attendance.*'],
        ['label' => 'Time Correction Management','icon' => 'fa-clock-rotate-left',    'route' => 'time-corrections.index', 'match' => 'time-corrections.*'],
        ['label' => 'Leave Management',          'icon' => 'fa-calendar-days',        'route' => 'leaves.index',       'match' => 'leaves.*'],
        ['label' => 'Onboarding Management',     'icon' => 'fa-user-plus',            'route' => 'onboarding.index',   'match' => 'onboarding.*'],
        ['label' => 'Payroll Management',        'icon' => 'fa-wallet',               'route' => 'payroll.index',      'match' => 'payroll.*'],
        ['label' => 'Financial Loan Management', 'icon' => 'fa-hand-holding-dollar',  'route' => 'loans.index',        'match' => 'loans.*'],
        ['label' => 'Offboarding Management',    'icon' => 'fa-user-slash',           'route' => 'offboarding.index',  'match' => 'offboarding.*'],
        ['label' => 'Report',    'icon' => 'fa-user-slash',           'route' => 'offboarding.index',  'match' => 'offboarding.*'],
    ];
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
    class="fixed inset-y-0 left-0 z-40 flex w-90 flex-col border-r border-red-100 bg-red-50 transition-transform duration-200 lg:translate-x-0"
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