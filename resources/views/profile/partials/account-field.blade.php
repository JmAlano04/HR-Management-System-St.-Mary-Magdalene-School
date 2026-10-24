{{--
    resources/views/profile/partials/account-field.blade.php

    One form field that shows plain text in view mode and an input in edit mode.
    Needs the parent Alpine scope (`editing`).

    Params: key (dot name for old()/errors), label, value
    Optional: inputName (input name attribute, defaults to key), type, wide, options (value => label),
              multiline, placeholder, attrs
--}}
@php
    $type        = $type ?? 'text';
    $wide        = $wide ?? false;
    $options     = $options ?? null;
    $attrs       = $attrs ?? '';
    $inputName   = $inputName ?? $key;
    $placeholder = $placeholder ?? '';
    $multiline   = $multiline ?? false;

    $current  = old($key, $value ?? '');
    $hasValue = filled($current);
    $display  = $hasValue ? ($options[$current] ?? $current) : 'Not provided';

    if ($hasValue && $type === 'date') {
        $display = \Illuminate\Support\Carbon::parse($current)->format('F j, Y');
    }

    $id      = 'acc_' . str_replace(['.', '[', ']'], '_', $key);
    $control = 'w-full min-h-[44px] rounded-[9px] border border-gray-300 bg-white px-3 py-2.5 font-normal text-gray-900 placeholder:text-gray-400 focus:border-[#b22222] focus:outline-none focus:ring-[3px] focus:ring-[#b22222]/15';
@endphp

<label for="{{ $id }}" class="grid content-start gap-2 text-[0.9rem] font-bold {{ $wide ? 'col-span-full' : '' }}">
    {{ $label }}

    {{-- View mode --}}
    <span x-show="!editing"
        class="min-h-6 whitespace-pre-wrap break-words text-[0.95rem] font-normal leading-normal {{ $hasValue ? 'text-gray-900' : 'italic text-gray-400' }}">{{ $display }}</span>

    {{-- Edit mode --}}
    @if ($options)
        <select id="{{ $id }}" name="{{ $inputName }}" x-show="editing" x-cloak class="{{ $control }}" {!! $attrs !!}>
            <option value="">{{ $placeholder }}</option>
            @foreach ($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @elseif ($multiline)
        <textarea id="{{ $id }}" name="{{ $inputName }}" rows="3" x-show="editing" x-cloak
            placeholder="{{ $placeholder }}" class="{{ $control }} resize-y" {!! $attrs !!}>{{ $current }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $inputName }}" value="{{ $current }}" x-show="editing" x-cloak
            placeholder="{{ $placeholder }}" class="{{ $control }}" {!! $attrs !!}>
    @endif

    @error($key)
        <span class="text-xs font-semibold text-red-600">{{ $message }}</span>
    @enderror
</label>