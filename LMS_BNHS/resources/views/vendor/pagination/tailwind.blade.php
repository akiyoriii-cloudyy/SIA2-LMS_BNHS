@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" style="display:flex; justify-content:center; align-items:center; gap:6px; margin-top:1rem; flex-wrap:wrap;">

        {{-- Pagination Elements (numbers only) --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span style="display:inline-flex; align-items:center; justify-content:center; min-width:36px; height:36px; padding:0 0.5rem; border:1px solid #dee2e6; border-radius:6px; background:#f8f9fa; color:#6c757d; font-size:0.875rem; cursor:default;">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" style="display:inline-flex; align-items:center; justify-content:center; min-width:36px; height:36px; padding:0 0.5rem; border:1px solid #007bff; border-radius:6px; background:#007bff; color:white; font-size:0.875rem; font-weight:600; cursor:default;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:36px; height:36px; padding:0 0.5rem; border:1px solid #dee2e6; border-radius:6px; background:white; color:#495057; font-size:0.875rem; font-weight:500; text-decoration:none; transition:all 0.2s;" onmouseover="this.style.background='#007bff'; this.style.color='white'; this.style.borderColor='#007bff';" onmouseout="this.style.background='white'; this.style.color='#495057'; this.style.borderColor='#dee2e6';">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

    </nav>
@endif
