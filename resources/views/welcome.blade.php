@extends('layouts.app')

@section('title', __('home.hero_title'))

@section('content')
    {{-- Hero --}}
    <section class="mu-hero">
        <div class="container position-relative">
            <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 mb-3">
                <span class="d-inline-flex align-items-center gap-2 small text-uppercase" style="letter-spacing:.12em">
                    <span class="mu-online-dot {{ $serverStatus['online'] ? '' : 'is-off' }}"></span>
                    <span class="text-muted">
                        {{ $serverStatus['online'] ? __('home.server_online') : __('home.server_offline') }}
                        @if(!is_null($serverStatus['players']))
                            · {{ $serverStatus['players'] }} {{ __('home.players_online') }}
                        @endif
                    </span>
                </span>
                @include('partials.clock')
            </div>

            <h1 class="mu-hero-title text-gradient-gold">{{ __('home.hero_title') }}</h1>
            <p class="mu-hero-sub">
                {{ __('home.hero_subtitle', ['season' => config('server.season'), 'version' => config('server.version')]) }}
            </p>

            <div class="d-flex gap-2 justify-content-center mt-4 flex-wrap">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4">{{ __('home.create_account') }}</a>
                @else
                    <a href="{{ route('character.index') }}" class="btn btn-primary btn-lg px-4">{{ __('nav.characters') }}</a>
                @endguest
                <a href="{{ collect(config('server.downloads'))->first() ?? '#' }}" class="btn btn-outline-light btn-lg px-4" target="_blank" rel="noopener">
                    <i class="fa-solid fa-download me-2"></i>{{ __('home.download_game') }}
                </a>
            </div>

            {{-- Rates --}}
            <div class="row g-3 justify-content-center mt-5">
                @foreach([
                    'exp' => $rates['exp'],
                    'master_exp' => $rates['master_exp'],
                    'drop' => $rates['drop'],
                    'max_reset' => $rates['max_reset'],
                ] as $key => $val)
                    <div class="col-6 col-md-3">
                        <div class="mu-stat">
                            <div class="mu-stat-value">{{ $val }}</div>
                            <div class="mu-stat-label">{{ __('home.' . $key) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="container py-5">
        <h2 class="mu-section-title mb-4">{{ __('home.features_title') }}</h2>
        <div class="row g-4">
            @foreach([
                ['icon' => 'shield-halved', 'k' => 'balanced'],
                ['icon' => 'calendar-day', 'k' => 'events'],
                ['icon' => 'users', 'k' => 'community'],
            ] as $f)
                <div class="col-md-4">
                    <div class="card mu-feature p-4">
                        <div class="mu-feature-icon mb-3"><i class="fa-solid fa-{{ $f['icon'] }}"></i></div>
                        <h3 class="h5">{{ __('home.feature_' . $f['k']) }}</h3>
                        <p class="text-muted mb-0">{{ __('home.feature_' . $f['k'] . '_desc') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Latest news --}}
    <section class="container pb-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <h2 class="mu-section-title mb-0">{{ __('home.news_title') }}</h2>
            <a href="{{ route('news.index') }}" class="small">{{ __('home.news_more') }} →</a>
        </div>

        @if($latestNews->isEmpty())
            <p class="text-muted">{{ __('home.news_empty') }}</p>
        @else
            <div class="row g-4">
                @foreach($latestNews as $post)
                    <div class="col-md-4">
                        <a href="{{ route('news.show', $post) }}" class="card mu-feature h-100 text-decoration-none">
                            @if($post->cover_image)
                                <img src="{{ $post->cover_image }}" class="card-img-top" alt="" style="height:160px;object-fit:cover">
                            @endif
                            <div class="card-body">
                                <div class="small text-muted mb-1">{{ optional($post->published_at)->format('d/m/Y') }}</div>
                                <h3 class="h6">{{ $post->title() }}</h3>
                                <p class="text-muted small mb-0">{{ $post->excerpt() }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
