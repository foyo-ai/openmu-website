@extends('layouts.app')

@section('title', $guide->title())
@section('meta_description', $guide->excerpt())
@section('og_type', 'article')
@if($guide->cover_image)
    @section('og_image', $guide->cover_image)
@endif

@php($wide = $guide->category === 'gear')

@section('content')
    <div class="container py-5">
        <a href="{{ route('guides.index') }}" class="small text-muted">← {{ __('guide.back') }}</a>

        <div class="d-flex align-items-center gap-2 mt-3 mb-3">
            @if($guide->icon)
                <span class="mu-feature-icon" style="font-size:1.5rem;margin:0"><i class="fa-solid fa-{{ $guide->icon }}"></i></span>
            @endif
            <h1 class="mb-0">{{ $guide->title() }}</h1>
        </div>

        {{-- Wide, table-heavy pages (gear) use the full width; siblings become a top nav bar. --}}
        @if($wide)
            @if($siblings->isNotEmpty())
                <div class="mu-sibling-nav mb-4">
                    <span class="text-muted small me-1">{{ __('guide.more_in') }}:</span>
                    @foreach($siblings as $s)
                        <a href="{{ route('guides.show', $s) }}" class="mu-chip">
                            @if($s->icon)<i class="fa-solid fa-{{ $s->icon }} me-1"></i>@endif{{ $s->title() }}
                        </a>
                    @endforeach
                </div>
            @endif

            @if($guide->cover_image)
                <img src="{{ $guide->cover_image }}" class="img-fluid rounded mb-4" alt="{{ $guide->title() }}">
            @endif

            <div class="guide-body">{!! $guide->body() !!}</div>
            <div class="text-muted small mt-4">{{ __('guide.updated_on', ['date' => $guide->updated_at?->format('d/m/Y')]) }}</div>
        @else
            <div class="row g-4 mt-1">
                <article class="col-lg-8">
                    @if($guide->cover_image)
                        <img src="{{ $guide->cover_image }}" class="img-fluid rounded mb-4" alt="{{ $guide->title() }}">
                    @endif

                    {{-- Trusted HTML authored by GMs (see Guide model / admin editor). --}}
                    <div class="guide-body">{!! $guide->body() !!}</div>

                    <div class="text-muted small mt-4">{{ __('guide.updated_on', ['date' => $guide->updated_at?->format('d/m/Y')]) }}</div>
                </article>

                @if($siblings->isNotEmpty())
                    <aside class="col-lg-4">
                        <div class="card p-3 sticky-top" style="top:90px">
                            <div class="fw-semibold mb-2">{{ __('guide.more_in') }}: {{ __('guide.cat_' . $guide->category) }}</div>
                            <ul class="list-unstyled mb-0">
                                @foreach($siblings as $s)
                                    <li class="mb-1">
                                        <a href="{{ route('guides.show', $s) }}" class="text-decoration-none">
                                            @if($s->icon)<i class="fa-solid fa-{{ $s->icon }} me-1 text-warning"></i>@endif
                                            {{ $s->title() }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </aside>
                @endif
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
    // Class filter chips: toggle rows of the target table by their data-classes (OR logic).
    document.querySelectorAll('.mu-filter').forEach(function (bar) {
        var table = document.querySelector(bar.getAttribute('data-target'));
        if (!table) return;
        var selected = new Set();
        bar.querySelectorAll('.mu-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                var c = chip.getAttribute('data-class');
                if (selected.has(c)) { selected.delete(c); chip.classList.remove('active'); }
                else { selected.add(c); chip.classList.add('active'); }
                table.querySelectorAll('tbody tr[data-classes]').forEach(function (row) {
                    if (selected.size === 0) { row.style.display = ''; return; }
                    var cs = (row.getAttribute('data-classes') || '').split('|');
                    var show = false;
                    selected.forEach(function (s) { if (cs.indexOf(s) >= 0) show = true; });
                    row.style.display = show ? '' : 'none';
                });
            });
        });
    });
    </script>
    @endpush
@endsection
