@extends('layouts.app')

@section('title', $news->title())
@section('meta_description', $news->excerpt())
@section('og_type', 'article')
@if($news->cover_image)
    @section('og_image', $news->cover_image)
@endif

@push('head')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $news->title(),
        'datePublished' => optional($news->published_at)->toIso8601String(),
        'dateModified' => optional($news->updated_at)->toIso8601String(),
        'image' => $news->cover_image ? [$news->cover_image] : [],
        'mainEntityOfPage' => route('news.show', $news),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <article class="container py-5" style="max-width: 820px">
        <a href="{{ route('news.index') }}" class="small text-muted">← {{ __('news.back') }}</a>

        <h1 class="mt-3 mb-2">{{ $news->title() }}</h1>
        <div class="text-muted small mb-4">
            {{ __('news.published_on', ['date' => optional($news->published_at)->format('d/m/Y')]) }}
        </div>

        @if($news->cover_image)
            <img src="{{ $news->cover_image }}" class="img-fluid rounded mb-4" alt="{{ $news->title() }}">
        @endif

        {{-- GM-authored posts may be rich HTML (headings, lists, images) or plain text.
             Render HTML as-is (trusted content); escape + line-break plain text. --}}
        <div class="news-body guide-body">
            @if(\Illuminate\Support\Str::contains($news->body(), '</'))
                {!! $news->body() !!}
            @else
                {!! nl2br(e($news->body())) !!}
            @endif
        </div>
    </article>
@endsection
