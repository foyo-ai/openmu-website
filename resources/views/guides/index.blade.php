@extends('layouts.app')

@section('title', __('guide.title'))
@section('meta_description', __('guide.subtitle'))

@section('content')
    <div class="container py-5">
        <h1 class="mu-section-title mb-1">{{ __('guide.title') }}</h1>
        <p class="text-muted">{{ __('guide.subtitle') }}</p>

        @php
            // Fixed display order of category sections.
            $order = ['general', 'class', 'gear', 'wings', 'stats'];
        @endphp

        @if($guides->isEmpty())
            <p class="text-muted mt-4">{{ __('guide.empty') }}</p>
        @else
            @foreach($order as $cat)
                @php($items = $guides[$cat] ?? collect())
                @if($items->isNotEmpty())
                    <section class="mt-5">
                        <h2 class="h4 mb-3"><i class="fa-solid fa-angle-right text-warning me-2"></i>{{ __('guide.cat_' . $cat) }}</h2>
                        <div class="row g-3">
                            @foreach($items as $g)
                                <div class="{{ $cat === 'class' ? 'col-6 col-md-4 col-lg-3' : 'col-md-6 col-lg-4' }}">
                                    <a href="{{ route('guides.show', $g) }}" class="card mu-feature h-100 text-decoration-none">
                                        @if($g->cover_image)
                                            <img src="{{ $g->cover_image }}" class="card-img-top" alt="" style="height:130px;object-fit:cover">
                                        @endif
                                        <div class="card-body">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                @if($g->icon)
                                                    <span class="mu-feature-icon" style="font-size:1.15rem;margin:0"><i class="fa-solid fa-{{ $g->icon }}"></i></span>
                                                @endif
                                                <h3 class="h6 mb-0">{{ $g->title() }}</h3>
                                            </div>
                                            @if($cat !== 'class')
                                                <p class="text-muted small mb-0">{{ $g->excerpt() }}</p>
                                            @endif
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        @endif
    </div>
@endsection
