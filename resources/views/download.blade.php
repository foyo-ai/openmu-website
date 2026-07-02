@extends('layouts.app')

@section('title', __('download.title'))
@section('meta_description', __('download.subtitle'))

@section('content')
    <div class="container py-5" style="max-width: 900px">
        <div class="text-center mb-4">
            <h1 class="mu-section-title mb-3">{{ __('download.title') }}</h1>
            <p class="text-muted mx-auto" style="max-width:40rem">{{ __('download.subtitle') }}</p>

            @if($installerUrl)
                <a href="{{ $installerUrl }}" class="btn btn-primary btn-lg px-5 mt-2" download>
                    <i class="fa-solid fa-download me-2"></i>{{ __('download.button') }}
                </a>
                <div class="small text-muted mt-2">{{ __('download.button_note') }}</div>
            @endif

            @guest
                <div class="small mt-3">
                    {{ __('download.no_account') }}
                    <a href="{{ route('register') }}">{{ __('download.register') }}</a>
                </div>
            @endguest
        </div>

        <div class="card mu-feature p-4 mb-4">
            <div class="d-flex align-items-start gap-3">
                <span class="mu-feature-icon"><i class="fa-solid fa-bolt"></i></span>
                <div>
                    <h2 class="h5 mb-1">{{ __('download.oneclick_title') }}</h2>
                    <p class="text-muted mb-0">{{ __('download.oneclick_desc') }}</p>
                </div>
            </div>
        </div>

        <h2 class="h4 mb-3"><i class="fa-solid fa-list-ol text-warning me-2"></i>{{ __('download.steps_title') }}</h2>
        <ol class="mu-steps mb-4">
            <li>{{ __('download.step1') }}</li>
            <li>{{ __('download.step2') }}</li>
            <li>{{ __('download.step3') }}</li>
            <li>{{ __('download.step4') }}</li>
        </ol>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card mu-feature h-100 p-4">
                    <h2 class="h5 mb-3"><i class="fa-solid fa-desktop text-warning me-2"></i>{{ __('download.req_title') }}</h2>
                    <ul class="mb-0 text-muted">
                        <li>{{ __('download.req_os') }}</li>
                        <li>{{ __('download.req_space') }}</li>
                        <li>{{ __('download.req_net') }}</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mu-feature h-100 p-4">
                    <h2 class="h5 mb-3"><i class="fa-solid fa-server text-warning me-2"></i>{{ __('download.connect_title') }}</h2>
                    <p class="text-muted small mb-2">{{ __('download.connect_desc') }}</p>
                    <div class="mu-stat">
                        <div class="mu-stat-value" style="font-size:1.15rem">{{ $connectHost }}<span class="text-muted">:{{ $connectPort }}</span></div>
                        <div class="mu-stat-label">Server</div>
                    </div>
                </div>
            </div>
        </div>

        @if($discordUrl && $discordUrl !== 'https://discord.gg/')
            <div class="card mu-feature p-4 text-center">
                <h2 class="h5 mb-2">{{ __('download.help_title') }}</h2>
                <p class="text-muted mb-3">{{ __('download.help_desc') }}</p>
                <div>
                    <a href="{{ $discordUrl }}" class="btn btn-outline-light" target="_blank" rel="noopener">
                        <i class="fa-brands fa-discord me-2"></i>{{ __('download.help_button') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
