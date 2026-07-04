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
            <p class="mu-hero-sub">{{ __('home.hero_subtitle') }}</p>

            <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                @foreach([
                    ['icon' => 'scale-balanced', 'k' => 'badge_nop2w'],
                    ['icon' => 'download', 'k' => 'badge_install'],
                    ['icon' => 'users', 'k' => 'badge_community'],
                ] as $b)
                    <span class="badge rounded-pill" style="background:rgba(233,196,106,.12);color:#e9c46a;border:1px solid rgba(233,196,106,.3);font-weight:500;padding:.4rem .7rem">
                        <i class="fa-solid fa-{{ $b['icon'] }} me-1"></i>{{ __('home.' . $b['k']) }}
                    </span>
                @endforeach
            </div>

            <div class="d-flex gap-2 justify-content-center mt-4 flex-wrap">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4">{{ __('home.create_account') }}</a>
                @else
                    <a href="{{ route('character.index') }}" class="btn btn-primary btn-lg px-4">{{ __('nav.characters') }}</a>
                @endguest
                <a href="{{ route('download') }}" class="btn btn-outline-light btn-lg px-4">
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
                ['icon' => 'scale-balanced', 'k' => 'nop2w'],
                ['icon' => 'download', 'k' => 'install'],
                ['icon' => 'bolt', 'k' => 'rates'],
                ['icon' => 'moon', 'k' => 'offline'],
            ] as $f)
                <div class="col-md-6 col-lg-3">
                    <div class="card mu-feature p-4 h-100">
                        <div class="mu-feature-icon mb-3"><i class="fa-solid fa-{{ $f['icon'] }}"></i></div>
                        <h3 class="h5">{{ __('home.feature_' . $f['k']) }}</h3>
                        <p class="text-muted mb-0">{{ __('home.feature_' . $f['k'] . '_desc') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Character classes --}}
    <section class="container pb-2">
        <h2 class="mu-section-title mb-1">{{ __('home.classes_title') }}</h2>
        <p class="text-muted">{{ __('home.classes_subtitle') }}</p>
        <div class="row g-3">
            @foreach([
                ['icon' => 'shield-halved', 'name' => 'Dark Knight'],
                ['icon' => 'hat-wizard',    'name' => 'Dark Wizard'],
                ['icon' => 'bullseye',      'name' => 'Fairy Elf'],
                ['icon' => 'wand-sparkles', 'name' => 'Magic Gladiator'],
                ['icon' => 'crown',         'name' => 'Dark Lord'],
                ['icon' => 'book-skull',    'name' => 'Summoner'],
                ['icon' => 'hand-fist',     'name' => 'Rage Fighter'],
            ] as $c)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card mu-feature p-3 text-center h-100">
                        <div class="mu-feature-icon mb-2" style="font-size:1.6rem"><i class="fa-solid fa-{{ $c['icon'] }}"></i></div>
                        <div class="fw-semibold">{{ $c['name'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Top players preview --}}
    <section class="container py-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <h2 class="mu-section-title mb-0">{{ __('home.ranking_preview_title') }}</h2>
            <a href="{{ route('rankings.index') }}" class="small">{{ __('home.ranking_preview_more') }} →</a>
        </div>
        <div class="card p-0">
            <div class="table-responsive">
                <table class="table table-hover mu-rank-table align-middle mb-0">
                    <thead><tr>
                        <th class="ps-3">{{ __('ranking.rank') }}</th><th>{{ __('ranking.name') }}</th>
                        <th>{{ __('ranking.class') }}</th><th class="text-end">{{ __('ranking.resets') }}</th>
                        <th class="text-end pe-3">{{ __('ranking.level') }}</th>
                    </tr></thead>
                    <tbody>
                    @forelse($topPlayers as $row)
                        <tr>
                            <td class="ps-3 mu-rank-pos">{{ $row['rank'] }}</td>
                            <td class="fw-semibold">{{ $row['name'] }}</td>
                            <td class="text-muted">{{ $row['className'] }}</td>
                            <td class="text-end">{{ number_format($row['resets']) }}</td>
                            <td class="text-end pe-3">{{ number_format($row['level']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('home.ranking_empty') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
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
