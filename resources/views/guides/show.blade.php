@extends('layouts.app')

@section('title', $guide->title())
@section('meta_description', $guide->excerpt())
@section('og_type', 'article')
@if($guide->cover_image)
    @section('og_image', $guide->cover_image)
@endif

@section('content')
    <div class="container py-5">
        <a href="{{ route('guides.index') }}" class="small text-muted">← {{ __('guide.back') }}</a>

        <div class="row g-4 mt-1">
            <article class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-3">
                    @if($guide->icon)
                        <span class="mu-feature-icon" style="font-size:1.5rem;margin:0"><i class="fa-solid fa-{{ $guide->icon }}"></i></span>
                    @endif
                    <h1 class="mb-0">{{ $guide->title() }}</h1>
                </div>

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
    </div>
@endsection
