{{-- resources/views/profile/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold leading-tight text-red-700 sm:text-3xl lg:text-4xl">
            {{ __('My Account') }}
        </h2>
        <p class="mt-2 text-sm text-gray-600">
            {{ __('View and update your personal information.') }}
        </p>
    </x-slot>

    <div class="px-20">
        <div class="mx-auto w-full max-w-7xl space-y-4 px-4 sm:space-y-6 sm:px-6 lg:space-y-8 lg:px-8">

            {{-- Account information --}}
            <section
                class="min-w-0 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200
                       sm:rounded-2xl sm:p-6 lg:p-8"
            >
                <div class="w-full min-w-0 max-w-4xl">
                    @if ($employee)
                        @include('profile.partials.update-account-form')
                    @else
                        @include('profile.partials.update-profile-information-form')
                    @endif
                </div>
            </section>

            {{-- Password settings --}}
            <section
                class="min-w-0 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200
                       sm:rounded-2xl sm:p-6 lg:p-8"
            >
                <div class="w-full min-w-0 max-w-2xl">
                    @include('profile.partials.update-password-form')
                </div>
            </section>

            {{-- Account deletion: only for users without an employee record --}}
            @unless ($employee)
                <section
                    class="min-w-0 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200
                           sm:rounded-2xl sm:p-6 lg:p-8"
                >
                    <div class="w-full min-w-0 max-w-2xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </section>
            @endunless

        </div>
    </div>
</x-app-layout>