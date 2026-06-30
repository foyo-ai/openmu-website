@extends('layouts.app')

@section('title', __('news.title'))
@section('meta_description', __('news.subtitle'))

@section('content')
    <div class="container py-5">
        <h1 class="mu-section-title mb-2">{{ __('news.title') }}</h1>
        <p class="text-muted">{{ __('news.subtitle') }}</p>

        @if($news->isEmpty())
            <p class="text-muted mt-4">{{ __('news.empty') }}</p>
        @else
            <div class="row g-4 mt-1">
                @foreach($news as $post)
                    <div class="col-md-6">
                        <a href="{{ route('news.show', $post) }}" class="card mu-feature h-100 text-decoration-none">
                            @if($post->cover_image)
                                <img src="{{ $post->cover_image }}" class="card-img-top" alt="" style="height:200px;object-fit:cover">
                            @endif
                            <div class="card-body">
                                <div class="small text-muted mb-1">{{ optional($post->published_at)->format('d/m/Y') }}</div>
                                <h2 class="h5">{{ $post->title() }}</h2>
                                <p class="text-muted mb-0">{{ $post->excerpt() }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">{{ $news->links() }}</div>
        @endif
    </div>
@endsection
