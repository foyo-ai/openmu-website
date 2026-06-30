<!doctype html>
@php($locale = app()->getLocale())
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO --}}
    <title>@yield('title', config('app.name')) — muss6.org</title>
    <meta name="description" content="@yield('meta_description', __('seo.default_description'))">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Twitter --}}
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('meta_description', __('seo.default_description'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
    <meta name="twitter:card" content="summary_large_image">

    {{-- hreflang alternates --}}
    @if(class_exists(\Mcamara\LaravelLocalization\Facades\LaravelLocalization::class))
        @foreach(\Mcamara\LaravelLocalization\Facades\LaravelLocalization::getSupportedLocales() as $code => $props)
            <link rel="alternate" hreflang="{{ $code }}"
                  href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($code) }}">
        @endforeach
        <link rel="alternate" hreflang="x-default"
              href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL(config('app.locale')) }}">
    @endif

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
    <div id="app" class="d-flex flex-column min-vh-100">
        <nav class="navbar navbar-expand-lg navbar-dark mu-navbar sticky-top">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}">
                    MUSS<span class="brand-accent">6</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                        aria-controls="mainNav" aria-expanded="false" aria-label="{{ __('nav.toggle') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">{{ __('nav.home') }}</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('rankings.index') }}">{{ __('nav.rankings') }}</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('news.index') }}">{{ __('nav.news') }}</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#download">{{ __('nav.download') }}</a></li>
                    </ul>

                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        {{-- Language switcher --}}
                        @if(class_exists(\Mcamara\LaravelLocalization\Facades\LaravelLocalization::class))
                            <li class="nav-item mu-lang me-lg-3">
                                @foreach(\Mcamara\LaravelLocalization\Facades\LaravelLocalization::getSupportedLocales() as $code => $props)
                                    <a href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($code) }}"
                                       class="{{ app()->getLocale() === $code ? 'active' : '' }}"
                                       hreflang="{{ $code }}">{{ strtoupper($code) }}</a>@if(!$loop->last)<span class="text-muted">/</span>@endif
                                @endforeach
                            </li>
                        @endif

                        @guest
                            <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">{{ __('nav.login') }}</a></li>
                            @if (Route::has('register'))
                                <li class="nav-item"><a class="btn btn-primary btn-sm ms-lg-2" href="{{ route('register') }}">{{ __('nav.register') }}</a></li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="userDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ Auth::user()->LoginName }}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="{{ route('character.index') }}">{{ __('nav.characters') }}</a></li>
                                    <li><a class="dropdown-item" href="{{ route('account.edit') }}">{{ __('nav.account') }}</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            {{ __('nav.logout') }}
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                                    </li>
                                </ul>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="flex-grow-1">
            <div class="container">
                @foreach (['success' => 'success', 'danger' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $key => $type)
                    @if (session('alert-' . $key))
                        <div class="alert alert-{{ $type }} mt-3 mb-0" role="alert">{{ session('alert-' . $key) }}</div>
                    @endif
                @endforeach
            </div>

            @yield('content')
        </main>

        <footer class="mu-footer mt-auto">
            <div class="container">
                <div class="row gy-3">
                    <div class="col-md-6">
                        <div class="navbar-brand fs-4">MUSS<span class="brand-accent">6</span></div>
                        <p class="mb-0 small">{{ __('footer.tagline') }}</p>
                    </div>
                    <div class="col-md-6 text-md-end small">
                        <a class="me-3" href="{{ route('rankings.index') }}">{{ __('nav.rankings') }}</a>
                        <a class="me-3" href="{{ route('news.index') }}">{{ __('nav.news') }}</a>
                        <a href="{{ route('home') }}#download">{{ __('nav.download') }}</a>
                        <div class="mt-2 text-muted">© {{ date('Y') }} muss6.org</div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    @stack('scripts')
</body>
</html>
