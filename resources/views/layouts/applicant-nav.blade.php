{{-- resources/views/layouts/applicant-nav.blade.php (top navigation: Applicant and Employee) --}}
@php
    $user = auth()->user();

    // Spatie role name: Employee or Applicant
    $role = $user->getRoleNames()->first() ?? 'No Role';

    $items = [
        'dashboard'        => ['label' => 'Dashboard',                  'icon' => 'fa-table-cells',         'route' => 'dashboard',              'match' => 'dashboard'],
        'attendance'       => ['label' => 'Attendance Monitoring',      'icon' => 'fa-calendar-check',      'route' => 'attendance.index',       'match' => 'attendance.*'],
        'time-corrections' => ['label' => 'Time Correction Management', 'icon' => 'fa-clock-rotate-left',   'route' => 'time-corrections.index', 'match' => 'time-corrections.*'],
        'leaves'           => ['label' => 'Leave Management',           'icon' => 'fa-calendar-days',       'route' => 'leaves.index',           'match' => 'leaves.*'],
        'payroll'          => ['label' => 'Payroll Management',         'icon' => 'fa-wallet',              'route' => 'payroll.index',          'match' => 'payroll.*'],
        'loans'            => ['label' => 'Financial Loan Management',  'icon' => 'fa-hand-holding-dollar', 'route' => 'loans.index',            'match' => 'loans.*'],
        'application'      => ['label' => 'My Application',             'icon' => 'fa-file-signature',      'route' => 'applications.index',     'match' => 'applications.*'],
    ];

    $roleMenus = [
        'Applicant' => [
            'dashboard', 'application',
        ],
     
    ];

    // Unknown role or no role: Dashboard only
    $menu = collect($roleMenus[$role] ?? ['dashboard'])
        ->map(fn ($key) => $items[$key])
        ->all();
@endphp

<nav
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    class="sticky top-0 z-30 bg-white shadow-[0_1px_8px_rgba(0,0,0,0.08)]"
>
    <div class="mx-auto flex max-w-[1920px] items-center gap-4 px-4 py-4 sm:px-8 lg:px-12 lg:py-5">

        {{-- Logo and school name --}}
<<<<<<< HEAD
        <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center gap-2 sm:gap-4">
=======
        <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-4">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            <img
                src="{{ asset('Image/SMMS Logo.png') }}"
                alt="School Logo"
                class="h-[45px] w-[45px] shrink-0 object-contain lg:h-[60px] lg:w-[60px]"
            >
<<<<<<< HEAD
            <span class="min-w-0 truncate text-[clamp(15px,2.2vw,30px)] font-extrabold leading-tight text-[#b22222]">
=======
            <span class="text-[clamp(18px,2.2vw,30px)] font-extrabold leading-tight text-[#b22222]">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                St. Mary Magdalene School
            </span>
        </a>

    

        {{-- Log out (desktop) --}}
        <form method="POST" action="{{ route('logout') }}" class="ml-auto hidden shrink-0 lg:block">
            @csrf
            <button
                type="submit"
                class="cursor-pointer rounded-lg border border-[#b22222] bg-[#b22222] px-5 py-3 text-base font-bold text-white transition-all duration-300 hover:border-white hover:bg-white hover:text-[#b22222] hover:shadow-[0_8px_20px_rgba(178,34,34,0.35)]"
            >
                Log Out
            </button>
        </form>

        {{-- Hamburger (mobile only) --}}
        <button
            type="button"
            @click="open = !open"
            class="ml-auto text-[#b22222] lg:hidden"
<<<<<<< HEAD
            :aria-expanded="open.toString()"
            aria-controls="applicant-mobile-menu"
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            aria-label="Toggle menu"
        >
            <i class="fa-solid text-xl" :class="open ? 'fa-xmark' : 'fa-bars'"></i>
        </button>
    </div>

    {{-- Mobile dropdown --}}
    <div
        x-show="open"
<<<<<<< HEAD
        id="applicant-mobile-menu"
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        x-transition
        x-cloak
        class="max-h-[80vh] overflow-y-auto border-t border-gray-100 px-3 py-3 lg:hidden"
    >
<<<<<<< HEAD
        <nav class="grid gap-1" aria-label="Applicant menu">
            @foreach ($menu as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <a
                    href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                    @click="open = false"
                    @class([
                        'flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition',
                        'bg-red-50 text-[#b22222]' => $active,
                        'text-gray-700 hover:bg-red-50 hover:text-[#b22222]' => ! $active,
                    ])
                >
                    <i class="fa-solid {{ $item['icon'] }} w-5 text-center" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
=======

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        {{-- Log out (mobile) --}}
        <form method="POST" action="{{ route('logout') }}" class="mt-3 border-t border-gray-100 pt-3">
            @csrf
            <button
                type="submit"
                class="w-full cursor-pointer rounded-lg border border-[#b22222] bg-[#b22222] px-5 py-3 text-base font-bold text-white transition-all duration-300 hover:bg-white hover:text-[#b22222]"
            >
                Log Out
            </button>
        </form>
    </div>
</nav>

<nav  x-data="{ open: false }"
    @keydown.escape.window="open = false"
<<<<<<< HEAD
    class="sticky hidden bg-white shadow-[0_1px_8px_rgba(0,0,0,0.08)] lg:block"
=======
    class="sticky  bg-white shadow-[0_1px_8px_rgba(0,0,0,0.08)]"
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    >
  <div class="mx-auto flex max-w-[1920px] items-center gap-4 px-4 py-4 sm:px-8 lg:px-12 lg:py-5">
     {{-- Desktop links (scrolls sideways if too long, scrollbar hidden) --}}
        <div
            class="hidden min-w-0 flex-1 items-center gap-1 overflow-x-auto lg:flex [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        >
            @foreach ($menu as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <a
                    href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                    class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition {{ $active ? 'bg-[#b22222] text-white' : 'text-gray-700 hover:bg-red-50 hover:text-[#b22222]' }}"
                >
                    <i class="fa-solid {{ $item['icon'] }}"></i>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
        


    </div>


</nav>
 