@if ($paginator->lastPage() > 1)
    <nav class="mt-4 inline-flex items-center" role="navigation" aria-label="Pagination Navigation">
        <ul class="inline-flex items-center space-x-1 text-sm">
            {{-- Previous --}}
            <li>
                <a href="?page={{ $paginator->currentPage() - 1 }}" class="px-3 py-1 rounded-md bg-white border text-slate-600 prev-page {{ $paginator->currentPage() <= 1 ? 'opacity-50 pointer-events-none' : '' }}" aria-label="Previous">Previous</a>
            </li>

            @foreach (range(1, $paginator->lastPage()) as $page)
                @if ($page == $paginator->currentPage())
                    <li>
                        <span class="px-3 py-1 rounded-md bg-[#6da651] text-white font-semibold">{{ $page }}</span>
                    </li>
                @else
                    <li>
                        <a href="?page={{ $page }}" class="px-3 py-1 rounded-md bg-white border text-slate-600 page-link">{{ $page }}</a>
                    </li>
                @endif
            @endforeach

            {{-- Next --}}
            <li>
                <a href="?page={{ $paginator->currentPage() + 1 }}" class="px-3 py-1 rounded-md bg-white border text-slate-600 next-page {{ $paginator->currentPage() >= $paginator->lastPage() ? 'opacity-50 pointer-events-none' : '' }}" aria-label="Next">Next</a>
            </li>
        </ul>
    </nav>
@endif
