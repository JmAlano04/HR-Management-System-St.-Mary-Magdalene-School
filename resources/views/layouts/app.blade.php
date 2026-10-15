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

            {{-- Left sidebar navigation --}}
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
                                    class="inline-flex items-center gap-3 mr-[110px] rounded-full border border-gray-200 bg-white py-2 pl-2 pr-4 text-lg shadow-sm transition hover:bg-gray-50 focus:outline-none">
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

                        <x-slot name="content">
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

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>