{{-- resources/views/layouts/trade-pagination.blade.php --}}

@props(['paginator', 'showInfo' => true, 'maxLinks' => 7])

<style>
    .trade-pagination-wrapper {
        width: 100%;
        padding: 2.5rem 1.5rem;
        background: var(--bg-card);
        border-top: 1px solid var(--border-color);
    }

    .trade-pagination-container {
        max-width: 1100px;
        margin: 0 auto;
    }

    .trade-pagination-info {
        text-align: center;
        margin-bottom: 2rem;
    }

    .trade-pagination-count {
        font-size: 0.875rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    .trade-pagination-count strong {
        color: var(--text-color);
        font-weight: 700;
    }

    .trade-pagination-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .trade-pagination-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: var(--bg-body);
        color: var(--text-color);
        border: 1px solid var(--border-color);
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .trade-pagination-btn:hover:not(.trade-pagination-btn-disabled) {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
    }

    .trade-pagination-btn-disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .trade-pagination-btn i {
        font-size: 1rem;
    }

    .trade-pagination-pages {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin: 0 0.5rem;
    }

    .trade-pagination-page {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 0.5rem;
        border-radius: 8px;
        background: var(--bg-body);
        color: var(--text-color);
        border: 1px solid var(--border-color);
        font-size: 0.9375rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .trade-pagination-page:hover:not(.trade-pagination-page-active) {
        background: var(--bg-card);
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .trade-pagination-page-active {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
        cursor: default;
    }

    .trade-pagination-ellipsis {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        color: var(--text-muted);
        font-weight: 700;
        user-select: none;
    }

    @media (max-width: 768px) {
        .trade-pagination-wrapper {
            padding: 2rem 1rem;
        }

        .trade-pagination-info {
            margin-bottom: 1.5rem;
        }

        .trade-pagination-count {
            font-size: 0.8125rem;
        }

        .trade-pagination-btn,
        .trade-pagination-page {
            width: 36px;
            height: 36px;
            min-width: 36px;
        }

        .trade-pagination-page {
            font-size: 0.875rem;
        }
    }

    @media (max-width: 480px) {
        .trade-pagination-wrapper {
            padding: 1.5rem 0.75rem;
        }

        .trade-pagination-pages {
            margin: 0 0.25rem;
            gap: 0.125rem;
        }

        .trade-pagination-btn,
        .trade-pagination-page {
            width: 34px;
            height: 34px;
            min-width: 34px;
        }

        .trade-pagination-page {
            font-size: 0.8125rem;
            padding: 0 0.375rem;
        }
    }
</style>

@if ($paginator->hasPages())
    <div class="trade-pagination-wrapper">
        <div class="trade-pagination-container">

            @if ($showInfo)
                <div class="trade-pagination-info">
                    <p class="trade-pagination-count">
                        Página <strong>{{ $paginator->currentPage() }}</strong> de
                        <strong>{{ $paginator->lastPage() }}</strong>
                        <span style="margin: 0 0.5rem;">•</span>
                        <strong>{{ $paginator->total() }}</strong> {{ Str::plural('resultado', $paginator->total()) }}
                    </p>
                </div>
            @endif

            <nav class="trade-pagination-nav" role="navigation" aria-label="Paginação">

                @if ($paginator->onFirstPage())
                    <span class="trade-pagination-btn trade-pagination-btn-disabled" aria-disabled="true">
                        <i class="bi bi-chevron-left"></i>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="trade-pagination-btn" rel="prev"
                        aria-label="Página anterior">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                @endif

                <div class="trade-pagination-pages">
                    @php
                        $currentPage = $paginator->currentPage();
                        $lastPage = $paginator->lastPage();

                        $halfMaxLinks = floor($maxLinks / 2);
                        $from = max($currentPage - $halfMaxLinks, 1);
                        $to = min($currentPage + $halfMaxLinks, $lastPage);

                        if ($currentPage <= $halfMaxLinks) {
                            $to = min($maxLinks, $lastPage);
                        }
                        if ($currentPage > $lastPage - $halfMaxLinks) {
                            $from = max($lastPage - $maxLinks + 1, 1);
                        }
                    @endphp

                    @if ($from > 1)
                        <a href="{{ $paginator->url(1) }}" class="trade-pagination-page" aria-label="Página 1">
                            1
                        </a>
                        @if ($from > 2)
                            <span class="trade-pagination-ellipsis">...</span>
                        @endif
                    @endif

                    @for ($page = $from; $page <= $to; $page++)
                        @if ($page == $currentPage)
                            <span class="trade-pagination-page trade-pagination-page-active" aria-current="page"
                                aria-label="Página {{ $page }}">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $paginator->url($page) }}" class="trade-pagination-page"
                                aria-label="Página {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endfor

                    @if ($to < $lastPage)
                        @if ($to < $lastPage - 1)
                            <span class="trade-pagination-ellipsis">...</span>
                        @endif
                        <a href="{{ $paginator->url($lastPage) }}" class="trade-pagination-page"
                            aria-label="Página {{ $lastPage }}">
                            {{ $lastPage }}
                        </a>
                    @endif
                </div>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="trade-pagination-btn" rel="next"
                        aria-label="Próxima página">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <span class="trade-pagination-btn trade-pagination-btn-disabled" aria-disabled="true">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                @endif

            </nav>

        </div>
    </div>
@endif
