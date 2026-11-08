@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-3">

        {{-- Mobile: Previous / Next lang --}}
        <div class="flex flex-1 justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="cursor-not-allowed rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-400 ring-1 ring-gray-200">{!! __('pagination.previous') !!}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-200 transition hover:bg-red-50 hover:text-[#b22222]">{!! __('pagination.previous') !!}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-200 transition hover:bg-red-50 hover:text-[#b22222]">{!! __('pagination.next') !!}</a>
            @else
                <span class="cursor-not-allowed rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-400 ring-1 ring-gray-200">{!! __('pagination.next') !!}</span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <p class="text-sm text-gray-600">
                {!! __('Showing') !!}
                @if ($paginator->firstItem())
                    <span class="font-semibold">{{ $paginator->firstItem() }}</span>
                    {!! __('to') !!}
                    <span class="font-semibold">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                {!! __('of') !!}
                <span class="font-semibold">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>

            <span class="inline-flex items-center gap-1">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" class="cursor-not-allowed rounded-lg px-3 py-2 text-sm text-gray-300">&lsaquo;</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
                       class="rounded-lg px-3 py-2 text-sm text-gray-600 transition hover:bg-red-50 hover:text-[#b22222]">&lsaquo;</a>
                @endif

                {{-- Page numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 py-2 text-sm text-gray-400">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="min-w-[38px] rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-bold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                   class="min-w-[38px] rounded-lg bg-white px-3 py-2 text-center text-sm font-semibold text-gray-700 ring-1 ring-gray-200 transition hover:bg-red-50 hover:text-[#b22222]">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
                       class="rounded-lg px-3 py-2 text-sm text-gray-600 transition hover:bg-red-50 hover:text-[#b22222]">&rsaquo;</a>
                @else
                    <span aria-disabled="true" class="cursor-not-allowed rounded-lg px-3 py-2 text-sm text-gray-300">&rsaquo;</span>
                @endif
            </span>
        </div>
    </nav>
@endif