{{-- resources/views/components/sidebar-link.blade.php --}}
@props(['active' => false, 'icon' => 'fa-circle'])

@php
    $classes = $active
        ? 'bg-[#c60204] text-white font-semibold rounded-lg ml-3 mr-3'
        : 'text-red-900 hover:bg-[#c60204] hover:text-white rounded-lg ml-3 mr-3';
@endphp

<a {{ $attributes->merge(['class' => "flex items-center gap-4 px-6 py-3 text-sm font-medium transition $classes"]) }}>
    <i class="fa-solid {{ $icon }} w-4 text-center"></i>
    <span>{{ $slot }}</span>
</a>