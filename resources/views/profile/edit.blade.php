
{{-- resources/views/profile/edit.blade.php --}}
<x-app-layout>
  
        <div
            class="mx-auto w-full max-w-7xl space-y-4 px-3
                   sm:space-y-6 sm:px-6
                   lg:px-8 xl:px-10"
        >
        

            {{-- Account information --}}
            <section
                class="min-w-0 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200
                       sm:rounded-2xl sm:p-6 lg:p-8"
            >
            
                    <div class="w-full min-w-0 max-w-4xl">
                        @include('profile.partials.update-account-form')
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
    
</x-app-layout>
