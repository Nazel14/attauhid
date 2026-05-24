@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $window = 1; // berapa halaman di sekitar current page
        
        // Hitung range tengah
        $start = max(1, $currentPage - $window);
        $end = min($lastPage, $currentPage + $window);
    @endphp

    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-center gap-1.5 flex-wrap">

        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center gap-1 px-3 py-2 bg-gray-900 border border-gray-800 text-gray-600 text-xs font-bold rounded-lg opacity-50 cursor-not-allowed">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               class="inline-flex items-center gap-1 px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 hover:border-gray-600 text-gray-300 hover:text-white text-xs font-bold rounded-lg transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
        @endif

        {{-- First page + ellipsis --}}
        @if ($start > 1)
            <a href="{{ $paginator->url(1) }}"
               class="inline-flex items-center justify-center min-w-[34px] px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 hover:border-gray-600 text-gray-300 hover:text-white text-xs font-bold rounded-lg transition-all">
                1
            </a>
            @if ($start > 2)
                <span class="px-1 text-gray-600 text-xs">...</span>
            @endif
        @endif

        {{-- Pages around current --}}
        @for ($page = $start; $page <= $end; $page++)
            @if ($page == $currentPage)
                <span aria-current="page"
                      class="inline-flex items-center justify-center min-w-[34px] px-3 py-2 bg-gradient-to-br from-indigo-500 to-purple-600 border border-indigo-500 text-white text-xs font-bold rounded-lg shadow-lg shadow-indigo-900/40">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $paginator->url($page) }}"
                   class="inline-flex items-center justify-center min-w-[34px] px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 hover:border-gray-600 text-gray-300 hover:text-white text-xs font-bold rounded-lg transition-all">
                    {{ $page }}
                </a>
            @endif
        @endfor

        {{-- Ellipsis + Last page --}}
        @if ($end < $lastPage)
            @if ($end < $lastPage - 1)
                <span class="px-1 text-gray-600 text-xs">...</span>
            @endif
            <a href="{{ $paginator->url($lastPage) }}"
               class="inline-flex items-center justify-center min-w-[34px] px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 hover:border-gray-600 text-gray-300 hover:text-white text-xs font-bold rounded-lg transition-all">
                {{ $lastPage }}
            </a>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               class="inline-flex items-center gap-1 px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 hover:border-gray-600 text-gray-300 hover:text-white text-xs font-bold rounded-lg transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        @else
            <span class="inline-flex items-center gap-1 px-3 py-2 bg-gray-900 border border-gray-800 text-gray-600 text-xs font-bold rounded-lg opacity-50 cursor-not-allowed">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </span>
        @endif

    </nav>
@endif